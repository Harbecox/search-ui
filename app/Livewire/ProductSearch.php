<?php

namespace App\Livewire;

use App\Models\Product;
use App\Services\SearchApiService;
use Livewire\Attributes\Url;
use Livewire\Component;

class ProductSearch extends Component
{
    #[Url(as: 'q')]
    public string $query = '';

    public string $priceMin = '';
    public string $priceMax = '';
    public bool $smartSearch = true;

    public array $results = [];
    public ?array $parsedQuery = null;
    public float $tookMs = 0;
    public bool $searched = false;
    public ?string $error = null;

    public function mount(): void
    {
        if ($this->query !== '') {
            $this->search();
        }
    }

    public function search(): void
    {
        $this->error       = null;
        $this->results     = [];
        $this->parsedQuery = null;

        if (trim($this->query) === '') {
            return;
        }

        try {
            $service = new SearchApiService(
                baseUrl: config('services.search_api.url'),
                token:   config('services.search_api.token'),
            );

            $response = $service->search(
                query:          $this->query,
                limit:          (int) config('services.search_api.default_limit', 10),
                priceMin:       $this->priceMin !== '' ? (float) $this->priceMin : null,
                priceMax:       $this->priceMax !== '' ? (float) $this->priceMax : null,
                useQueryParser: $this->smartSearch,
            );

            $this->tookMs      = round($response['took_ms'], 1);
            $this->searched    = true;
            $this->parsedQuery = $response['parsed_query'] ?? null;

            $hits = $response['hits'] ?? [];

            if (empty($hits)) {
                return;
            }

            // Собрать ID по всем источникам
            $idsBySource = [];
            foreach ($hits as $hit) {
                [$source, $id] = explode(':', $hit['global_id'], 2);
                $idsBySource[$source][$id] = $hit['score'];
            }

            // Загрузить products из БД
            $products = collect();
            if (!empty($idsBySource['products'])) {
                $products = Product::whereIn('id', array_keys($idsBySource['products']))
                    ->get()
                    ->keyBy('id');
            }

            // Собрать результаты в порядке из поиска
            $this->results = collect($hits)
                ->map(function ($hit) use ($products) {
                    [$source, $id] = explode(':', $hit['global_id'], 2);
                    return [
                        'score'   => round($hit['score'], 4),
                        'source'  => $source,
                        'product' => $source === 'products' ? $products->get($id) : null,
                    ];
                })
                ->filter(fn($item) => $item['product'] !== null)
                ->values()
                ->toArray();

        } catch (\RuntimeException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function clear(): void
    {
        $this->query       = '';
        $this->priceMin    = '';
        $this->priceMax    = '';
        $this->results     = [];
        $this->parsedQuery = null;
        $this->searched    = false;
        $this->error       = null;
    }

    public function render()
    {
        return view('livewire.product-search')
            ->layout('layouts.app', ['title' => 'Поиск товаров']);
    }
}
