<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\EmbeddingServiceInterface;
use Illuminate\Support\Facades\Http;

final class CohereEmbeddingService implements EmbeddingServiceInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'embed-english-v3.0'
    ) {}

    /**
     * @return array<float>
     */
    public function embed(string $text, ?string $inputType = null): array
    {
        $inputType = $inputType ?? 'search_document';
        $response = Http::withToken($this->apiKey)
            ->timeout(30)
            ->post('https://api.cohere.ai/v1/embed', [
                'model' => $this->model,
                'texts' => [$text],
                'input_type' => $inputType,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Cohere embedding failed: ' . $response->body());
        }

        $embeddings = $response->json('embeddings');
        if (! is_array($embeddings) || empty($embeddings)) {
            return [];
        }
        $first = $embeddings[0] ?? [];
        return is_array($first) ? $first : [];
    }

    /**
     * @param array<string> $texts
     * @return array<array<float>>
     */
    public function embedMany(array $texts): array
    {
        if (empty($texts)) {
            return [];
        }
        $response = Http::withToken($this->apiKey)
            ->timeout(60)
            ->post('https://api.cohere.ai/v1/embed', [
                'model' => $this->model,
                'texts' => $texts,
                'input_type' => 'search_document',
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Cohere embedding failed: ' . $response->body());
        }

        $embeddings = $response->json('embeddings', []);
        $result = [];
        foreach ($embeddings as $emb) {
            $result[] = is_array($emb) ? $emb : [];
        }
        return $result;
    }
}
