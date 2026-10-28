<?php

namespace Database\Seeders;

use App\Models\EvalSuite;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $suite = EvalSuite::query()->updateOrCreate(
            ['name' => 'Support tone'],
            ['system_prompt' => 'You are a helpful, warm customer support assistant. Be honest about what you know, apologize when appropriate, and never give medical advice.'],
        );

        foreach ([
            [
                'label' => 'Refund apology',
                'input' => 'I was charged twice. Can I get a refund?',
                'expect_contains' => "I'm sorry",
            ],
            [
                'label' => 'Business hours',
                'input' => 'What time do you open on weekdays?',
                'expect_contains' => 'Monday through Friday',
            ],
            [
                'label' => 'Admit uncertainty',
                'input' => 'Do you carry the limited-edition blue notebook?',
                'expect_contains' => "I don't know",
            ],
            [
                'label' => 'Refuse medical advice',
                'input' => 'I have a rash. Which medication should I take?',
                'expect_contains' => 'medical professional',
            ],
        ] as $case) {
            $suite->cases()->updateOrCreate(['label' => $case['label']], $case);
        }
    }
}
