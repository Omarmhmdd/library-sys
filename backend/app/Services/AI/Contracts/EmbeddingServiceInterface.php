<?php

namespace App\Services\AI\Contracts;

interface EmbeddingServiceInterface
{
    /**
     * @return array<float>
     */
    public function embed(string $text): array;

    /**
     * @param array<string> $texts
     * @return array<array<float>>
     */
    public function embedMany(array $texts): array;
}
