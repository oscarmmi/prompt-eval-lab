<?php

namespace App\Services\Llm;

class FakeLlmClient implements LlmClient
{
    public function complete(string $systemPrompt, string $input): string
    {
        $normalized = mb_strtolower($input);

        return match (true) {
            str_contains($normalized, 'refund'), str_contains($normalized, 'charged twice') => "I'm sorry about the duplicate charge. I can help you with a refund.",
            str_contains($normalized, 'open'), str_contains($normalized, 'hours') => 'We are open Monday through Friday, 9am to 5pm.',
            str_contains($normalized, 'limited-edition'), str_contains($normalized, 'carry') => "I'll check our inventory and get back to you shortly.",
            str_contains($normalized, 'rash'), str_contains($normalized, 'medical') => 'I cannot provide medical advice. Please speak with a qualified medical professional.',
            default => 'Thanks for reaching out. How else can I help?',
        };
    }

    public function modelName(): string
    {
        return 'deterministic-fake';
    }
}
