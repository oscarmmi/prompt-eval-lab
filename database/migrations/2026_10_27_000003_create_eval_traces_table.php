<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eval_traces', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('eval_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('eval_case_id')->constrained()->cascadeOnDelete();
            $table->longText('output');
            $table->boolean('passed');
            $table->unsignedInteger('latency_ms');
            $table->timestamps();
            $table->unique(['eval_run_id', 'eval_case_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eval_traces');
    }
};
