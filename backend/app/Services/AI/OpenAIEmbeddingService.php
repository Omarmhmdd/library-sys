<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\EmbeddingServiceInterface;
use Illuminate\Support\Facades\Http;

final class OpenAIEmbeddingService implements EmbeddingServiceInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'text-embedding-3-small'
    ) {}

    /**
     * @return array<float>
     */
    public function embed(string $text, ?string $inputType = null): array
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(30)
            ->post('https://api.openai.com/v1/embeddings', [
                'model' => $this->model,
                'input' => $text,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('OpenAI embedding failed: ' . $response->body());
        }

        $data = $response->json('data.0.embedding');
        return is_array($data) ? $data : [];
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
            ->post('https://api.openai.com/v1/embeddings', [
                'model' => $this->model,
                'input' => $texts,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('OpenAI embedding failed: ' . $response->body());
        }

        $items = $response->json('data', []);
        $result = [];
        foreach ($items as $item) {
            $emb = $item['embedding'] ?? [];
            $result[] = is_array($emb) ? $emb : [];
        }
        return $result;
    }
}
