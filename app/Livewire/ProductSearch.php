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

    public array $results = [];
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
        $this->error = null;
        $this->results = [];

        if (trim($this->query) === '') {
            return;
        }

        try {
            $service = new SearchApiService(
                baseUrl: config('services.search_api.url'),
                token:   config('services.search_api.token'),
            );

            $response = $service->search(
                query:    $this->query,
                limit:    (int) config('services.search_api.default_limit', 10),
                priceMin: $this->priceMin !== '' ? (float) $this->priceMin : null,
                priceMax: $this->priceMax !== '' ? (float) $this->priceMax : null,
            );

            $this->tookMs   = round($response['took_ms'], 1);
            $this->searched = true;

            $hits = $response['hits'] ?? [];

            if (empty($hits)) {
                return;
            }

            // Извлечь ID из global_id вида "products:123"
            $idOrder = [];
            foreach ($hits as $hit) {
                [$source, $id] = explode(':', $hit['global_id'], 2);
                if ($source === 'products') {
                    $idOrder[$id] = $hit['score'];
                }
            }

            // Загрузить из БД и сохранить порядок из поиска
            $products = Product::whereIn('id', array_keys($idOrder))->get()->keyBy('id');

            $this->results = collect($idOrder)
                ->map(fn($score, $id) => [
                    'score'   => round($score, 4),
                    'product' => $products->get($id),
                ])
                ->filter(fn($item) => $item['product'] !== null)
                ->values()
                ->toArray();

        } catch (\RuntimeException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function clear(): void
    {
        $this->query    = '';
        $this->priceMin = '';
        $this->priceMax = '';
        $this->results  = [];
        $this->searched = false;
        $this->error    = null;
    }

    public function render()
    {
        return view('livewire.product-search')
            ->layout('layouts.app', ['title' => 'Поиск товаров']);
    }
}
