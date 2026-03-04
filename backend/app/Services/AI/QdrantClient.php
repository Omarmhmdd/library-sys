<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

final class QdrantClient
{
    public function __construct(
        private readonly string $url,
        private readonly string $collection,
        private readonly int $vectorSize,
        private readonly ?string $apiKey = null
    ) {}

    /** Get collection info (e.g. points_count). Returns null if collection does not exist. */
    public function collectionInfo(): ?array
    {
        $res = $this->request('get', "/collections/{$this->collection}");
        if (! $res->successful()) {
            return null;
        }
        return $res->json();
    }

    public function ensureCollection(): void
    {
        $res = $this->request('get', "/collections/{$this->collection}");
        if (! $res->successful()) {
            if ($res->status() !== 404) {
                throw new \RuntimeException('Qdrant collection check failed: ' . $res->body());
            }
            $create = $this->request('put', "/collections/{$this->collection}", [
                'vectors' => [
                    'size' => $this->vectorSize,
                    'distance' => 'Cosine',
                ],
            ]);
            if (! $create->successful()) {
                throw new \RuntimeException('Qdrant create collection failed: ' . $create->body());
            }
        }
        $this->ensureBookIdIndex();
    }

    /** Create payload index for book_id so filter works in search. */
    private function ensureBookIdIndex(): void
    {
        $res = $this->request('put', "/collections/{$this->collection}/index", [
            'field_name' => 'book_id',
            'field_schema' => 'integer',
        ]);
        if (! $res->successful() && $res->status() !== 400) {
            throw new \RuntimeException('Qdrant create book_id index failed: ' . $res->body());
        }
    }

    public function upsert(int $pointId, array $vector, array $payload = []): void
    {
        $body = [
            'points' => [
                [
                    'id' => $pointId,
                    'vector' => $vector,
                    'payload' => array_merge($payload, ['book_id' => $pointId]),
                ],
            ],
            'wait' => true,
        ];
        $res = $this->request('put', "/collections/{$this->collection}/points", $body);
        if (! $res->successful()) {
            throw new \RuntimeException('Qdrant upsert failed: ' . $res->body());
        }
    }

    /**
     * @param array<float> $vector
     * @param float $minScore minimum similarity score (e.g. 0.25) to include
     * @return array<array{id: int, score: float}>
     */
    public function search(array $vector, int $limit = 5, ?int $excludeBookId = null, float $minScore = 0.25): array
    {
        $body = [
            'vector' => $vector,
            'limit' => $limit,
            'with_payload' => true,
        ];
        if ($excludeBookId !== null) {
            $body['filter'] = [
                'must_not' => [
                    ['key' => 'book_id', 'match' => ['value' => $excludeBookId]],
                ],
            ];
        }
        $res = $this->request('post', "/collections/{$this->collection}/points/search", $body);
        if (! $res->successful()) {
            throw new \RuntimeException('Qdrant search failed: ' . $res->body());
        }
        $points = $res->json('result', []);
        $out = [];
        foreach ($points as $p) {
            $id = $p['id'] ?? null;
            $score = (float) ($p['score'] ?? 0);
            if ($id !== null && (int) $id !== $excludeBookId && $score >= $minScore) {
                $out[] = ['id' => (int) $id, 'score' => $score];
                if (count($out) >= $limit) {
                    break;
                }
            }
        }
        return $out;
    }

    public function delete(int $pointId): void
    {
        $res = $this->request('post', "/collections/{$this->collection}/points/delete", [
            'points' => [$pointId],
            'wait' => true,
        ]);
        if (! $res->successful()) {
            throw new \RuntimeException('Qdrant delete failed: ' . $res->body());
        }
    }

    private function request(string $method, string $path, array $data = []): \Illuminate\Http\Client\Response
    {
        $url = rtrim($this->url, '/') . $path;
        $req = Http::timeout(30)->acceptJson();
        if ($this->apiKey) {
            $req = $req->withHeaders(['api-key' => $this->apiKey]);
        }
        if ($method === 'get') {
            return $req->get($url);
        }
        return $req->$method($url, $data);
    }
}
