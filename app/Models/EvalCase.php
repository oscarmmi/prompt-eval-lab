<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvalCase extends Model
{
    protected $fillable = ['eval_suite_id', 'input', 'expect_contains', 'label'];

    /** @return BelongsTo<EvalSuite, $this> */
    public function suite(): BelongsTo
    {
        return $this->belongsTo(EvalSuite::class, 'eval_suite_id');
    }

    /** @return HasMany<EvalTrace, $this> */
    public function traces(): HasMany
    {
        return $this->hasMany(EvalTrace::class);
    }
}
