<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eval_suites', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('system_prompt');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eval_suites');
    }
};
