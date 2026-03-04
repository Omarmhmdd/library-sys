<?php

namespace App\Services\AI\Contracts;

interface LlmServiceInterface
{
    public function generate(string $prompt, ?string $systemPrompt = null, int $maxTokens = 500): string;
}
