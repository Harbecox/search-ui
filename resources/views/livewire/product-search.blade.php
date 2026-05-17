<div>
    {{-- Форма поиска --}}
    <div class="bg-white rounded-xl shadow p-5 mb-4">
        <form wire:submit="search" class="space-y-3">

            {{-- Строка поиска --}}
            <div class="flex gap-2">
                <input
                    wire:model="query"
                    type="text"
                    placeholder="Например: автоматический выключатель 10А от 1000р до 2000р"
                    class="flex-1 border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    autofocus
                >
                <button
                    type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="search">Найти</span>
                    <span wire:loading wire:target="search">Поиск...</span>
                </button>
                @if ($searched)
                    <button type="button" wire:click="clear"
                        class="text-gray-400 hover:text-gray-600 px-3 py-2 rounded-lg border text-sm transition">
                        Сбросить
                    </button>
                @endif
            </div>

            {{-- Переключатель умного поиска --}}
            <div class="flex justify-end">
                <label class="flex items-center gap-1.5 cursor-pointer select-none">
                    <input type="checkbox" wire:model="smartSearch" class="rounded text-blue-600">
                    <span class="text-xs text-gray-600">🤖 Умный поиск (цена, исключения)</span>
                </label>
            </div>

        </form>
    </div>

    {{-- Что распознал Ollama --}}
    @if ($parsedQuery)
        <div class="bg-blue-50 border border-blue-100 rounded-lg px-4 py-2.5 mb-4 text-xs text-blue-700 flex flex-wrap gap-3">
            <span>🤖 Распознано:</span>
            <span>Запрос: <strong>{{ $parsedQuery['query'] }}</strong></span>
            @if($parsedQuery['price_min'] ?? false)
                <span>от <strong>{{ number_format($parsedQuery['price_min'], 0, '.', ' ') }} ₽</strong></span>
            @endif
            @if($parsedQuery['price_max'] ?? false)
                <span>до <strong>{{ number_format($parsedQuery['price_max'], 0, '.', ' ') }} ₽</strong></span>
            @endif
            @if(!empty($parsedQuery['exclude']))
                <span>Исключить: <strong>{{ implode(', ', $parsedQuery['exclude']) }}</strong></span>
            @endif
        </div>
    @endif

    {{-- Ошибка --}}
    @if ($error)
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-4 text-sm">
            ⚠️ {{ $error }}
        </div>
    @endif

    {{-- Индикатор загрузки --}}
    <div wire:loading wire:target="search" class="text-center py-10 text-gray-400 text-sm">
        <p class="text-2xl mb-2">{{ $smartSearch ? '🤖' : '🔍' }}</p>
        {{ $smartSearch ? 'Анализирую запрос...' : 'Поиск...' }}
    </div>

    {{-- Результаты --}}
    <div wire:loading.remove wire:target="search">
        @if ($searched)
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm text-gray-500">
                    Найдено: <strong>{{ count($results) }}</strong> товар(ов)
                </p>
                <p class="text-xs text-gray-400">{{ $tookMs }} мс</p>
            </div>
        @endif

        @if (count($results) > 0)
            <div class="space-y-3">
                @foreach ($results as $item)
                    @php $product = $item['product'] @endphp
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex gap-4">

                        @if ($product->image_url)
                            <img src="{{ $product->image_url }}" alt="{{ $product->title }}"
                                class="w-20 h-20 object-contain rounded-lg border flex-shrink-0">
                        @else
                            <div class="w-20 h-20 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0 text-gray-300 text-2xl">
                                📦
                            </div>
                        @endif

                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-medium text-gray-900 text-sm leading-snug">{{ $product->title }}</h3>
                                @if ($product->price)
                                    <span class="text-blue-600 font-semibold text-sm whitespace-nowrap">
                                        {{ number_format($product->price, 2, '.', ' ') }} ₽
                                    </span>
                                @endif
                            </div>

                            @if ($product->sku)
                                <p class="text-xs text-gray-400 mt-0.5">Арт: {{ $product->sku }}</p>
                            @endif

                            @if ($product->description)
                                <p class="text-xs text-gray-600 mt-1 line-clamp-2">{{ $product->description }}</p>
                            @endif

                            @php $attrs = $product->attributes_map @endphp
                            @if (!empty($attrs))
                                <div class="flex flex-wrap gap-1 mt-2">
                                    @foreach (array_slice($attrs, 0, 4) as $name => $value)
                                        <span class="bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded-full">
                                            {{ $name }}: {{ $value }}
                                        </span>
                                    @endforeach
                                    @if (count($attrs) > 4)
                                        <span class="text-xs text-gray-400">+{{ count($attrs) - 4 }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="flex-shrink-0">
                            <span class="text-xs text-gray-300">{{ $item['score'] }}</span>
                        </div>
                    </div>
                @endforeach

        @elseif ($searched && !$error)
            <div class="text-center py-16 text-gray-400">
                <p class="text-4xl mb-3">🔍</p>
                <p class="text-sm">По запросу «{{ $query }}» ничего не найдено</p>
            </div>
        @endif
    </div>
</div>
