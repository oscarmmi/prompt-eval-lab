<?php

namespace App\Services\Llm;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiLlmClient implements LlmClient
{
    public function complete(string $systemPrompt, string $input): string
    {
        $model = config('services.gemini.model');
        $key = config('services.gemini.key');

        $response = Http::timeout(12)
            ->acceptJson()
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}", [
                'systemInstruction' => [
                    'parts' => [['text' => $systemPrompt]],
                ],
                'contents' => [[
                    'role' => 'user',
                    'parts' => [['text' => $input]],
                ]],
                'generationConfig' => ['temperature' => 0],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Gemini request failed with status '.$response->status().'.');
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('Gemini returned an empty response.');
        }

        return trim($text);
    }

    public function modelName(): string
    {
        return (string) config('services.gemini.model');
    }
}
