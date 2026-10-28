<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvalTrace extends Model
{
    protected $fillable = ['eval_run_id', 'eval_case_id', 'output', 'passed', 'latency_ms'];

    protected function casts(): array
    {
        return ['passed' => 'boolean', 'latency_ms' => 'integer'];
    }

    /** @return BelongsTo<EvalRun, $this> */
    public function run(): BelongsTo
    {
        return $this->belongsTo(EvalRun::class, 'eval_run_id');
    }

    /** @return BelongsTo<EvalCase, $this> */
    public function evalCase(): BelongsTo
    {
        return $this->belongsTo(EvalCase::class, 'eval_case_id');
    }
}
