<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\LlmServiceInterface;

class MetadataSuggestionService
{
    public function __construct(
        private readonly LlmServiceInterface $llm
    ) {}

    /**
     * @return array{genre?: string, description?: string}
     */
    public function suggest(string $title, string $author): array
    {
        try {
            $systemPrompt = 'You suggest book metadata. Reply with a JSON object only, with optional keys: "genre" (one short genre), "description" (2-3 sentences). No other text.';
            $prompt = "Title: {$title}. Author: {$author}.";
            $content = $this->llm->generate($prompt, $systemPrompt, 200);
        } catch (\Throwable) {
            return [];
        }
        if ($content === '') {
            return [];
        }
        $content = trim($content);
        if (preg_match('/\{[\s\S]*\}/', $content, $m)) {
            $decoded = json_decode($m[0], true);
            return is_array($decoded) ? array_intersect_key($decoded, array_flip(['genre', 'description'])) : [];
        }
        return [];
    }
}
