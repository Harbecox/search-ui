<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SearchApiService
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $token,
    ) {}

    /**
     * @return array{query: string, hits: array, took_ms: float, reranked: bool}
     */
    public function search(
        string $query,
        int $limit = 10,
        ?string $category = null,
        ?float $priceMin = null,
        ?float $priceMax = null,
        array $sourceKeys = [],
    ): array {
        $filters = array_filter([
            'category'   => $category,
            'price_min'  => $priceMin,
            'price_max'  => $priceMax,
            'source_keys' => $sourceKeys ?: null,
        ]);

        try {
            $response = Http::withToken($this->token)
                ->timeout(30)
                ->post("{$this->baseUrl}/search", [
                    'query'        => $query,
                    'limit'        => $limit,
                    'use_reranker' => false,
                    'filters'      => empty($filters) ? null : $filters,
                ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Search service unavailable: ' . $e->getMessage());
        }

        if ($response->failed()) {
            throw new RuntimeException('Search API error ' . $response->status() . ': ' . $response->body());
        }

        return $response->json();
    }
}
