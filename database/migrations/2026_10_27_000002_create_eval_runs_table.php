<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eval_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('eval_suite_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('passed')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->string('model_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eval_runs');
    }
};
