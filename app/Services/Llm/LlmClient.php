<?php

namespace App\Services\Llm;

interface LlmClient
{
    public function complete(string $systemPrompt, string $input): string;

    public function modelName(): string;
}
