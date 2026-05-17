<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Slami;
use App\Services\SearchApiService;
use Livewire\Attributes\Url;
use Livewire\Component;

class ProductSearch extends Component
{
    #[Url(as: 'q')]
    public string $query = '';

    public bool $smartSearch = true;

    // Топ-100 — сериализованные массивы, не модели
    public array $allResults = [];

    // Активные фильтры
    public string $filterPriceMin = '';
    public string $filterPriceMax = '';
    public array  $filterSources  = [];
    public array  $filterAttrs    = [];

    // UI
    public ?array  $parsedQuery = null;
    public float   $tookMs      = 0;
    public bool    $searched    = false;
    public ?string $error       = null;

    public function mount(): void
    {
        if ($this->query !== '') $this->search();
    }

    public function search(): void
    {
        $this->error       = null;
        $this->allResults  = [];
        $this->parsedQuery = null;
        $this->resetFilters();

        if (trim($this->query) === '') return;

        try {
            $service = new SearchApiService(
                baseUrl: config('services.search_api.url'),
                token:   config('services.search_api.token'),
            );

            $response = $service->search(
                query:          $this->query,
                limit:          100,
                useQueryParser: $this->smartSearch,
            );

            $this->tookMs      = round($response['took_ms'], 1);
            $this->searched    = true;
            $this->parsedQuery = $response['parsed_query'] ?? null;

            $hits = $response['hits'] ?? [];
            if (empty($hits)) return;

            $idsBySource = [];
            foreach ($hits as $hit) {
                [$source, $id] = explode(':', $hit['global_id'], 2);
                $idsBySource[$source][$id] = $hit['score'];
            }

            $products = collect();
            if (!empty($idsBySource['products'])) {
                $products = Product::whereIn('id', array_keys($idsBySource['products']))->get()->keyBy('id');
            }

            $slamis = collect();
            if (!empty($idsBySource['slamis'])) {
                $slamis = Slami::whereIn('id', array_keys($idsBySource['slamis']))->get()->keyBy('id');
            }

            $results = [];
            foreach ($hits as $hit) {
                [$source, $id] = explode(':', $hit['global_id'], 2);

                $model = match($source) {
                    'products' => $products->get($id),
                    'slamis'   => $slamis->get($id),
                    default    => null,
                };
                if (!$model) continue;

                // Сериализуем модель в простой массив — Livewire не любит Eloquent в state
                $results[] = [
                    'score'       => round($hit['score'], 4),
                    'source'      => $source,
                    'attrs'       => self::extractAttrs($model, $source),
                    'title'       => (string)($model->title ?? $model->name ?? ''),
                    'price'       => (float)($model->price ?? $model->price_retail ?? 0),
                    'sku'         => $model->sku ?? $model->code ?? null,
                    'description' => $model->description ?? $model->preview_text ?? null,
                    'image'       => $model->first_image ?? null,
                    'url'         => $model->product_url ?? null,
                ];
            }

            $this->allResults = $results;

        } catch (\RuntimeException $e) {
            $this->error = $e->getMessage();
        }
    }

    // ── Фасеты (зависимые: каждая группа считается без своего фильтра) ───────

    public function getFacets(): array
    {
        if (empty($this->allResults)) return [];

        // Цена и источник — из полностью отфильтрованных результатов
        $filtered = $this->getFilteredResults();
        $prices   = collect($filtered)->pluck('price')->filter();
        $sources  = collect($filtered)->groupBy('source')->map->count()->toArray();

        // Собираем все имена атрибутов
        $allAttrNames = [];
        foreach ($this->allResults as $item) {
            foreach (array_keys($item['attrs']) as $name) {
                $allAttrNames[$name] = true;
            }
        }

        // Для каждой группы атрибутов считаем значения БЕЗ своего фильтра
        $attrFacets = [];
        foreach (array_keys($allAttrNames) as $attrName) {
            $values = [];
            foreach ($this->allResults as $item) {
                if (!$this->passesFiltersExcept($item, $attrName)) continue;
                $val = $item['attrs'][$attrName] ?? null;
                if ($val !== null && $val !== '') {
                    $values[$val] = ($values[$val] ?? 0) + 1;
                }
            }

            $hasActiveFilter = !empty($this->filterAttrs[$attrName] ?? []);

            // Показывать если: ≥2 уникальных значения (или есть активный фильтр) и ≤30 значений
            if (!$hasActiveFilter && count($values) < 2) continue;
            if (array_sum($values) < 2) continue;
            if (count($values) > 30) continue;

            arsort($values);
            $attrFacets[$attrName] = $values;
        }

        uasort($attrFacets, fn($a, $b) => array_sum($b) <=> array_sum($a));

        return [
            'price_min' => $prices->min(),
            'price_max' => $prices->max(),
            'sources'   => $sources,
            'attrs'     => $attrFacets,
        ];
    }

    // ── Фильтрация ───────────────────────────────────────────────────────────

    public function getFilteredResults(): array
    {
        return collect($this->allResults)
            ->filter(fn($item) => $this->passesFiltersExcept($item, null))
            ->values()
            ->toArray();
    }

    // Проверить товар на соответствие всем фильтрам, кроме $exceptAttr
    private function passesFiltersExcept(array $item, ?string $exceptAttr): bool
    {
        if ($this->filterPriceMin !== '' && $item['price'] < (float)$this->filterPriceMin) return false;
        if ($this->filterPriceMax !== '' && $item['price'] > (float)$this->filterPriceMax) return false;
        if (!empty($this->filterSources) && !in_array($item['source'], $this->filterSources)) return false;

        foreach ($this->filterAttrs as $name => $selected) {
            if ($name === $exceptAttr) continue; // пропускаем свою группу
            if (empty($selected)) continue;
            if (!in_array($item['attrs'][$name] ?? null, $selected)) return false;
        }

        return true;
    }

    // ── Управление фильтрами ─────────────────────────────────────────────────

    public function toggleAttr(string $name, string $value): void
    {
        $current = $this->filterAttrs[$name] ?? [];
        if (in_array($value, $current)) {
            $current = array_values(array_diff($current, [$value]));
        } else {
            $current[] = $value;
        }
        $this->filterAttrs[$name] = $current;
    }

    public function toggleSource(string $value): void
    {
        if (in_array($value, $this->filterSources)) {
            $this->filterSources = array_values(array_diff($this->filterSources, [$value]));
        } else {
            $this->filterSources[] = $value;
        }
    }

    public function resetFilters(): void
    {
        $this->filterPriceMin = '';
        $this->filterPriceMax = '';
        $this->filterSources  = [];
        $this->filterAttrs    = [];
    }

    public function clear(): void
    {
        $this->query       = '';
        $this->allResults  = [];
        $this->parsedQuery = null;
        $this->searched    = false;
        $this->error       = null;
        $this->resetFilters();
    }

    // ── Атрибуты из модели ───────────────────────────────────────────────────

    public static function extractAttrs(mixed $model, string $source): array
    {
        $attrs = [];
        if ($source === 'products') {
            // $model->attributes — внутренний массив Eloquent, нужен getAttribute()
            $raw = $model->getAttribute('attributes') ?? [];
            if (is_string($raw)) $raw = json_decode($raw, true) ?? [];
            foreach ((array)$raw as $item) {
                if (isset($item['name'], $item['value']) && $item['value'] !== '') {
                    $attrs[(string)$item['name']] = (string)$item['value'];
                }
            }
        }
        if ($source === 'slamis') {
            if ($model->brand_code) $attrs['Бренд']   = $model->brand_code;
            if ($model->unit_name)  $attrs['Единица'] = $model->unit_name;
            if ($model->dimensions) $attrs['Размеры'] = $model->dimensions;
            if ($model->weight)     $attrs['Вес, кг'] = (string)$model->weight;
        }
        return $attrs;
    }

    public function render()
    {
        $facets          = $this->getFacets();
        $filteredResults = $this->getFilteredResults();

        return view('livewire.product-search', compact('facets', 'filteredResults'))
            ->layout('layouts.app', ['title' => 'Поиск товаров']);
    }
}
