<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\LlmServiceInterface;
use Illuminate\Support\Facades\Http;

final class CohereLlmService implements LlmServiceInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'command-r-08-2024'
    ) {}

    public function generate(string $prompt, ?string $systemPrompt = null, int $maxTokens = 500): string
    {
        $body = [
            'model' => $this->model,
            'message' => $prompt,
            'max_tokens' => $maxTokens,
        ];
        if ($systemPrompt !== null && $systemPrompt !== '') {
            $body['preamble'] = $systemPrompt;
        }

        $response = Http::withToken($this->apiKey)
            ->timeout(60)
            ->post('https://api.cohere.com/v1/chat', $body);

        if (! $response->successful()) {
            throw new \RuntimeException('Cohere chat failed: ' . $response->body());
        }
        $text = $response->json('text');
        return is_string($text) ? trim($text) : '';
    }
}
