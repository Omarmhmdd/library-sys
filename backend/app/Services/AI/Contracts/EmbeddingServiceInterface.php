<?php

namespace App\Services\AI\Contracts;

interface EmbeddingServiceInterface
{
    /**
     * @return array<float>
     * @param string|null $inputType Optional: for Cohere use 'search_document' or 'search_query'
     */
    public function embed(string $text, ?string $inputType = null): array;

    /**
     * @param array<string> $texts
     * @return array<array<float>>
     */
    public function embedMany(array $texts): array;
}
