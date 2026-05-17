<div>
    {{-- Форма поиска --}}
    <div class="bg-white rounded-xl shadow p-5 mb-4">
        <form wire:submit="search" class="space-y-3">
            <div class="flex gap-2">
                <input wire:model="query" type="text"
                    placeholder="Например: автоматический выключатель от 1000р до 2000р не ВА5735"
                    class="flex-1 border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    autofocus>
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition"
                    wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="search">Найти</span>
                    <span wire:loading wire:target="search">...</span>
                </button>
                @if ($searched)
                    <button type="button" wire:click="clear"
                        class="text-gray-400 hover:text-gray-600 px-3 py-2 rounded-lg border text-sm transition">
                        Сбросить
                    </button>
                @endif
            </div>
            <div class="flex justify-end">
                <label class="flex items-center gap-1.5 cursor-pointer select-none">
                    <input type="checkbox" wire:model="smartSearch" class="rounded text-blue-600">
                    <span class="text-xs text-gray-600">🤖 Умный поиск</span>
                </label>
            </div>
        </form>
    </div>

    {{-- Ollama плашка --}}
    @if ($parsedQuery)
        <div class="bg-blue-50 border border-blue-100 rounded-lg px-4 py-2.5 mb-4 text-xs text-blue-700 flex flex-wrap gap-x-4 gap-y-1 items-center">
            <span class="font-medium">🤖</span>
            <span>Запрос: <strong>{{ $parsedQuery['query'] }}</strong></span>
            @if($parsedQuery['amps'] ?? false) <span>Ток: <strong>{{ $parsedQuery['amps'] }}А</strong></span> @endif
            @if($parsedQuery['price_min'] ?? false) <span>от <strong>{{ number_format($parsedQuery['price_min'], 0, '.', ' ') }} ₽</strong></span> @endif
            @if($parsedQuery['price_max'] ?? false) <span>до <strong>{{ number_format($parsedQuery['price_max'], 0, '.', ' ') }} ₽</strong></span> @endif
            @if(!empty($parsedQuery['exclude'] ?? [])) <span class="text-red-500">Исключить: <strong>{{ implode(', ', $parsedQuery['exclude']) }}</strong></span> @endif
        </div>
    @endif

    @if ($error)
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-4 text-sm">⚠️ {{ $error }}</div>
    @endif

    <div wire:loading wire:target="search" class="text-center py-10 text-gray-400 text-sm">
        <p class="text-2xl mb-2">{{ $smartSearch ? '🤖' : '🔍' }}</p>
        {{ $smartSearch ? 'Анализирую запрос...' : 'Поиск...' }}
    </div>

    <div wire:loading.remove wire:target="search">
    @if ($searched)
    <div class="flex gap-5 items-start">

        {{-- ── САЙДБАР ── --}}
        @if (!empty($allResults))
        <aside class="w-60 flex-shrink-0 space-y-3">

            <div class="bg-white rounded-xl shadow-sm px-4 py-3 text-xs text-gray-500">
                Найдено: <strong class="text-gray-800">{{ count($allResults) }}</strong>
                @if(count($filteredResults) !== count($allResults))
                    / показано: <strong class="text-blue-600">{{ count($filteredResults) }}</strong>
                @endif
                <span class="float-right text-gray-300">{{ $tookMs }}мс</span>
            </div>

            {{-- Цена --}}
            @if(isset($facets['price_min']) && $facets['price_min'] !== null)
            <div class="bg-white rounded-xl shadow-sm p-4">
                <h3 class="text-xs font-semibold text-gray-600 mb-3 uppercase tracking-wide">Цена, ₽</h3>
                <div class="flex gap-2 items-center">
                    <input wire:model.live="filterPriceMin" type="number"
                        placeholder="{{ round($facets['price_min']) }}"
                        class="w-full border rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400">
                    <span class="text-gray-300 text-xs">—</span>
                    <input wire:model.live="filterPriceMax" type="number"
                        placeholder="{{ round($facets['price_max']) }}"
                        class="w-full border rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400">
                </div>
                <p class="text-xs text-gray-400 mt-1">
                    {{ number_format($facets['price_min'], 0, '.', ' ') }} — {{ number_format($facets['price_max'], 0, '.', ' ') }} ₽
                </p>
            </div>
            @endif

            {{-- Источник --}}
            @if(isset($facets['sources']) && count($facets['sources']) > 1)
            <div class="bg-white rounded-xl shadow-sm p-4">
                <h3 class="text-xs font-semibold text-gray-600 mb-3 uppercase tracking-wide">Источник</h3>
                <div class="space-y-1.5">
                    @foreach($facets['sources'] as $src => $count)
                        @php $active = in_array($src, $filterSources) @endphp
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:click="toggleSource('{{ $src }}')" @checked($active) class="rounded text-blue-600 w-3.5 h-3.5">
                            <span class="text-xs flex-1 {{ $active ? 'text-blue-700 font-semibold' : 'text-gray-700' }}">{{ $src }}</span>
                            <span class="text-xs text-gray-400">{{ $count }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Атрибуты --}}
            @if(!empty($facets['attrs']))
                @foreach($facets['attrs'] as $attrName => $values)
                    @php $activeValues = $filterAttrs[$attrName] ?? [] @endphp
                    <div class="bg-white rounded-xl shadow-sm p-4">
                        <h3 class="text-xs font-semibold text-gray-600 mb-3 uppercase tracking-wide truncate" title="{{ $attrName }}">
                            {{ $attrName }}
                        </h3>
                        <div class="space-y-1.5 max-h-44 overflow-y-auto pr-1">
                            @foreach($values as $value => $count)
                                @php $active = in_array($value, $activeValues) @endphp
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="checkbox"
                                        wire:click="toggleAttr('{{ e($attrName) }}', '{{ e($value) }}')"
                                        @checked($active)
                                        class="rounded text-blue-600 w-3.5 h-3.5 flex-shrink-0">
                                    <span class="text-xs flex-1 truncate {{ $active ? 'text-blue-700 font-semibold' : 'text-gray-700' }} group-hover:text-blue-600"
                                        title="{{ $value }}">{{ $value }}</span>
                                    <span class="text-xs text-gray-400 flex-shrink-0">{{ $count }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif

            {{-- Сброс --}}
            @if($filterPriceMin || $filterPriceMax || !empty($filterSources) || !empty(array_filter($filterAttrs)))
            <button wire:click="resetFilters"
                class="w-full text-xs text-gray-500 hover:text-red-500 border border-gray-200 hover:border-red-300 rounded-xl py-2 bg-white transition">
                × Сбросить фильтры
            </button>
            @endif

        </aside>
        @endif

        {{-- ── РЕЗУЛЬТАТЫ ── --}}
        <div class="flex-1 min-w-0 space-y-3">
            @forelse ($filteredResults as $item)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex gap-4 hover:border-blue-200 transition">

                    <div class="flex-shrink-0 w-24 h-24">
                        @if ($item['image'])
                            @if ($item['url'])
                                <a href="{{ $item['url'] }}" target="_blank" rel="noopener">
                                    <img src="{{ $item['image'] }}" alt="{{ $item['title'] }}"
                                        class="w-24 h-24 object-contain rounded-lg border hover:opacity-80 transition">
                                </a>
                            @else
                                <img src="{{ $item['image'] }}" alt="{{ $item['title'] }}"
                                    class="w-24 h-24 object-contain rounded-lg border">
                            @endif
                        @else
                            <div class="w-24 h-24 bg-gray-100 rounded-lg flex items-center justify-center text-gray-300 text-3xl">📦</div>
                        @endif
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            @if ($item['url'])
                                <a href="{{ $item['url'] }}" target="_blank" rel="noopener"
                                    class="font-medium text-blue-700 hover:underline text-sm leading-snug">{{ $item['title'] }}</a>
                            @else
                                <h3 class="font-medium text-gray-900 text-sm leading-snug">{{ $item['title'] }}</h3>
                            @endif
                            @if ($item['price'])
                                <span class="text-blue-600 font-semibold text-sm whitespace-nowrap flex-shrink-0">
                                    {{ number_format($item['price'], 2, '.', ' ') }} ₽
                                </span>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center gap-1.5 mt-1">
                            @if ($item['sku']) <span class="text-xs text-gray-400">Арт: {{ $item['sku'] }}</span> @endif
                            <span class="text-xs bg-gray-100 text-gray-400 px-1.5 py-0.5 rounded">{{ $item['source'] }}</span>
                        </div>

                        @if ($item['description'])
                            <p class="text-xs text-gray-600 mt-1.5 line-clamp-2">{{ $item['description'] }}</p>
                        @endif

                        @if (!empty($item['attrs']))
                            <div class="flex flex-wrap gap-1 mt-2">
                                @foreach (array_slice($item['attrs'], 0, 5) as $name => $value)
                                    @php $isActive = in_array($value, $filterAttrs[$name] ?? []) @endphp
                                    <span wire:click="toggleAttr('{{ e($name) }}', '{{ e($value) }}')"
                                        class="text-xs px-2 py-0.5 rounded-full cursor-pointer transition
                                            {{ $isActive ? 'bg-blue-100 text-blue-700 font-medium ring-1 ring-blue-300' : 'bg-gray-100 text-gray-600 hover:bg-blue-50' }}"
                                        title="{{ $name }}: {{ $value }}">
                                        {{ $value }}
                                    </span>
                                @endforeach
                                @if (count($item['attrs']) > 5)
                                    <span class="text-xs text-gray-400 self-center">+{{ count($item['attrs']) - 5 }}</span>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="flex-shrink-0 flex flex-col items-end justify-between">
                        <span class="text-xs text-gray-300">{{ $item['score'] }}</span>
                        @if ($item['url'])
                            <a href="{{ $item['url'] }}" target="_blank" rel="noopener"
                                class="text-xs text-blue-500 hover:text-blue-700 border border-blue-200 px-2 py-1 rounded hover:bg-blue-50 transition">
                                Открыть →
                            </a>
                        @endif
                    </div>
                </div>

            @empty
                @if (!empty($allResults))
                    <div class="text-center py-16 text-gray-400 bg-white rounded-xl border">
                        <p class="text-3xl mb-3">🔽</p>
                        <p class="text-sm">Нет результатов с текущими фильтрами</p>
                        <button wire:click="resetFilters" class="mt-3 text-xs text-blue-500 hover:underline">Сбросить фильтры</button>
                    </div>
                @else
                    <div class="text-center py-16 text-gray-400 bg-white rounded-xl border">
                        <p class="text-4xl mb-3">🔍</p>
                        <p class="text-sm">По запросу «{{ $query }}» ничего не найдено</p>
                    </div>
                @endif
            @endforelse
        </div>

    </div>
    @endif
    </div>
</div>
