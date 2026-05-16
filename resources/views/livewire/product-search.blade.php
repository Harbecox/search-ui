<div>
    {{-- Форма поиска --}}
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <form wire:submit="search" class="space-y-3">

            {{-- Строка поиска --}}
            <div class="flex gap-2">
                <input
                    wire:model="query"
                    type="text"
                    placeholder="Введите запрос, например: автоматический выключатель 16А"
                    class="flex-1 border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    autofocus
                >
                <button
                    type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>Найти</span>
                    <span wire:loading>...</span>
                </button>
                @if ($searched)
                    <button
                        type="button"
                        wire:click="clear"
                        class="text-gray-400 hover:text-gray-600 px-3 py-2 rounded-lg border text-sm transition"
                    >
                        Сбросить
                    </button>
                @endif
            </div>

            {{-- Фильтры --}}
            <div class="flex gap-3 items-center">
                <span class="text-xs text-gray-500">Цена:</span>
                <input
                    wire:model="priceMin"
                    type="number"
                    placeholder="от"
                    class="w-24 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <span class="text-gray-400 text-sm">—</span>
                <input
                    wire:model="priceMax"
                    type="number"
                    placeholder="до"
                    class="w-24 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>

        </form>
    </div>

    {{-- Ошибка --}}
    @if ($error)
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-4 text-sm">
            ⚠️ {{ $error }}
        </div>
    @endif

    {{-- Индикатор загрузки --}}
    <div wire:loading class="text-center py-10 text-gray-400 text-sm">
        Поиск...
    </div>

    {{-- Результаты --}}
    <div wire:loading.remove>
        @if ($searched)
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm text-gray-500">
                    Найдено: <strong>{{ count($results) }}</strong>
                    @if(count($results) === 0) товаров @else товар(ов) @endif
                </p>
                <p class="text-xs text-gray-400">{{ $tookMs }} мс</p>
            </div>
        @endif

        @if (count($results) > 0)
            <div class="space-y-3">
                @foreach ($results as $item)
                    @php $product = $item['product'] @endphp
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex gap-4">

                        {{-- Изображение --}}
                        @if ($product->image_url)
                            <img
                                src="{{ $product->image_url }}"
                                alt="{{ $product->title }}"
                                class="w-20 h-20 object-contain rounded-lg border flex-shrink-0"
                            >
                        @else
                            <div class="w-20 h-20 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0 text-gray-300 text-2xl">
                                📦
                            </div>
                        @endif

                        {{-- Информация --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-medium text-gray-900 text-sm leading-snug">
                                    {{ $product->title }}
                                </h3>
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
                                <p class="text-xs text-gray-600 mt-1 line-clamp-2">
                                    {{ $product->description }}
                                </p>
                            @endif

                            {{-- Характеристики --}}
                            @php $attrs = $product->attributes_map @endphp
                            @if (!empty($attrs))
                                <div class="flex flex-wrap gap-1 mt-2">
                                    @foreach (array_slice($attrs, 0, 4) as $name => $value)
                                        <span class="bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded-full">
                                            {{ $name }}: {{ $value }}
                                        </span>
                                    @endforeach
                                    @if (count($attrs) > 4)
                                        <span class="text-xs text-gray-400 px-1">+{{ count($attrs) - 4 }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Релевантность --}}
                        <div class="flex-shrink-0 text-right">
                            <span class="text-xs text-gray-300">{{ $item['score'] }}</span>
                        </div>

                    </div>
                @endforeach
            </div>

        @elseif ($searched && !$error)
            <div class="text-center py-16 text-gray-400">
                <p class="text-4xl mb-3">🔍</p>
                <p class="text-sm">По запросу «{{ $query }}» ничего не найдено</p>
            </div>
        @endif
    </div>
</div>
