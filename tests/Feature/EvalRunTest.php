<?php

use App\Models\EvalRun;
use App\Models\EvalSuite;
use App\Services\Llm\FakeLlmClient;
use App\Services\Llm\LlmClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use RuntimeException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed();
    config()->set('services.gemini.key', null);
});

it('uses the deterministic fake client when no Gemini key is configured', function (): void {
    expect(app(LlmClient::class))->toBeInstanceOf(FakeLlmClient::class);
});

it('runs the seeded suite and persists four traces with three passes and one failure', function (): void {
    $suite = EvalSuite::where('name', 'Support tone')->firstOrFail();

    $response = $this->postJson(route('eval-runs.store'), ['eval_suite_id' => $suite->id]);

    $response->assertOk()
        ->assertJsonPath('run.passed', 3)
        ->assertJsonPath('run.failed', 1)
        ->assertJsonPath('run.model_name', 'deterministic-fake')
        ->assertJsonCount(4, 'run.traces');

    $run = EvalRun::with('traces')->firstOrFail();
    expect($run->traces)->toHaveCount(4)
        ->and($run->traces->where('passed', true))->toHaveCount(3)
        ->and($run->traces->where('passed', false))->toHaveCount(1);
});

it('creates a new run and fresh traces each time the suite is rerun', function (): void {
    $suite = EvalSuite::where('name', 'Support tone')->firstOrFail();

    $firstResponse = $this->postJson(route('eval-runs.store'), ['eval_suite_id' => $suite->id]);
    $secondResponse = $this->postJson(route('eval-runs.store'), ['eval_suite_id' => $suite->id]);

    $firstResponse->assertOk();
    $secondResponse->assertOk();
    expect(EvalRun::count())->toBe(2)
        ->and(EvalRun::first()->traces()->count())->toBe(4)
        ->and(EvalRun::latest('id')->first()->traces()->count())->toBe(4);
});

it('allows a guest to submit an evaluation', function (): void {
    $suite = EvalSuite::where('name', 'Support tone')->firstOrFail();

    $this->postJson(route('eval-runs.store'), ['eval_suite_id' => $suite->id])->assertOk();
});

it('matches expected content without case sensitivity', function (): void {
    $suite = EvalSuite::where('name', 'Support tone')->firstOrFail();
    $suite->cases()->where('label', 'Refund apology')->update(['expect_contains' => "I'M SORRY"]);

    $response = $this->postJson(route('eval-runs.store'), ['eval_suite_id' => $suite->id]);

    $response->assertOk()
        ->assertJsonPath('run.traces.0.passed', true);
});

it('stores a failed trace instead of returning a server error when the provider throws', function (): void {
    $this->app->bind(LlmClient::class, function (): LlmClient {
        return new class implements LlmClient
        {
            public function complete(string $systemPrompt, string $input): string
            {
                throw new RuntimeException('Provider unavailable');
            }

            public function modelName(): string
            {
                return 'unavailable-provider';
            }
        };
    });
    $suite = EvalSuite::where('name', 'Support tone')->firstOrFail();

    $response = $this->postJson(route('eval-runs.store'), ['eval_suite_id' => $suite->id]);

    $response->assertOk()
        ->assertJsonPath('run.passed', 0)
        ->assertJsonPath('run.failed', 4)
        ->assertJsonPath('run.traces.0.output', 'The model did not return a response. Check the provider configuration and try again.');
});

it('renders the evaluation desk for a guest', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Welcome')
            ->where('suites.0.name', 'Support tone')
            ->where('suites.0.cases_count', 4)
            ->where('latestRun', null));
});
