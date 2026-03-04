<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\LlmServiceInterface;
use Illuminate\Support\Facades\Http;

final class OpenAILlmService implements LlmServiceInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'gpt-4o-mini'
    ) {}

    public function generate(string $prompt, ?string $systemPrompt = null, int $maxTokens = 500): string
    {
        $messages = [];
        if ($systemPrompt !== null && $systemPrompt !== '') {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $response = Http::withToken($this->apiKey)
            ->timeout(30)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => $messages,
                'max_tokens' => $maxTokens,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('OpenAI chat failed: ' . $response->body());
        }
        $content = $response->json('choices.0.message.content');
        return is_string($content) ? trim($content) : '';
    }
}
