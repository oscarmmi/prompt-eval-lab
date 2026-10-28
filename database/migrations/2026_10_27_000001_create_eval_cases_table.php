<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eval_cases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('eval_suite_id')->constrained()->cascadeOnDelete();
            $table->text('input');
            $table->string('expect_contains');
            $table->string('label');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eval_cases');
    }
};
