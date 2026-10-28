<?php

use App\Http\Controllers\EvalRunController;
use App\Http\Controllers\EvalSuiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EvalSuiteController::class, 'index'])->name('home');
Route::post('/eval-runs', EvalRunController::class)->name('eval-runs.store');
