<?php

namespace App\Http\Controllers;

use App\Models\EvalSuite;
use App\Services\Eval\EvalRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EvalRunController extends Controller
{
    public function __invoke(Request $request, EvalRunner $runner): JsonResponse
    {
        $validated = $request->validate([
            'eval_suite_id' => ['required', 'integer', 'exists:eval_suites,id'],
        ]);

        $suite = EvalSuite::query()->whereKey($validated['eval_suite_id'])->firstOrFail();
        $run = $runner->run($suite);

        return response()->json([
            'run' => [
                'id' => $run->id,
                'passed' => $run->passed,
                'failed' => $run->failed,
                'model_name' => $run->model_name,
                'created_at' => $run->created_at?->toIso8601String(),
                'traces' => $run->traces->map(fn ($trace): array => [
                    'id' => $trace->id,
                    'output' => $trace->output,
                    'passed' => $trace->passed,
                    'latency_ms' => $trace->latency_ms,
                    'eval_case' => [
                        'id' => $trace->evalCase->id,
                        'label' => $trace->evalCase->label,
                        'input' => $trace->evalCase->input,
                        'expect_contains' => $trace->evalCase->expect_contains,
                    ],
                ]),
            ],
        ]);
    }
}
