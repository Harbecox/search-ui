<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Поиск товаров' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen">

    <header class="bg-white shadow-sm">
        <div class="max-w-5xl mx-auto px-4 py-4">
            <h1 class="text-xl font-semibold text-gray-800">🔍 Поиск товаров</h1>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 py-6">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
