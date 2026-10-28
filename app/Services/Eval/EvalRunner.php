<?php

namespace App\Services\Eval;

use App\Models\EvalRun;
use App\Models\EvalSuite;
use App\Services\Llm\LlmClient;
use Throwable;

class EvalRunner
{
    public function __construct(private readonly LlmClient $llm) {}

    public function run(EvalSuite $suite): EvalRun
    {
        $run = $suite->runs()->create([
            'passed' => 0,
            'failed' => 0,
            'model_name' => $this->llm->modelName(),
        ]);
        $passed = 0;
        $failed = 0;

        foreach ($suite->cases()->orderBy('id')->get() as $case) {
            $startedAt = hrtime(true);

            try {
                $output = $this->llm->complete($suite->system_prompt, $case->input);
            } catch (Throwable) {
                $output = 'The model did not return a response. Check the provider configuration and try again.';
            }

            $casePassed = mb_stripos($output, $case->expect_contains) !== false;
            $passed += (int) $casePassed;
            $failed += (int) ! $casePassed;

            $run->traces()->create([
                'eval_case_id' => $case->id,
                'output' => $output,
                'passed' => $casePassed,
                'latency_ms' => (int) round((hrtime(true) - $startedAt) / 1_000_000),
            ]);
        }

        $run->update([
            'passed' => $passed,
            'failed' => $failed,
        ]);

        return $run->load(['traces.evalCase']);
    }
}
