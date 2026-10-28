<?php

namespace App\Http\Controllers;

use App\Models\EvalSuite;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EvalSuiteController extends Controller
{
    public function index(Request $request): Response
    {
        $suites = EvalSuite::query()
            ->withCount('cases')
            ->orderBy('name')
            ->get(['id', 'name', 'system_prompt']);

        $selectedSuite = $suites->firstWhere('id', (int) $request->query('suite')) ?? $suites->first();
        $latestRun = $selectedSuite?->runs()
            ->with('traces.evalCase')
            ->latest('id')
            ->first();

        return Inertia::render('Welcome', [
            'suites' => $suites,
            'selectedSuiteId' => $selectedSuite?->id,
            'latestRun' => $latestRun,
        ]);
    }
}
