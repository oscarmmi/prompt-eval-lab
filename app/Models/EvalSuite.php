<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvalSuite extends Model
{
    protected $fillable = ['name', 'system_prompt'];

    /** @return HasMany<EvalCase, $this> */
    public function cases(): HasMany
    {
        return $this->hasMany(EvalCase::class);
    }

    /** @return HasMany<EvalRun, $this> */
    public function runs(): HasMany
    {
        return $this->hasMany(EvalRun::class);
    }
}
