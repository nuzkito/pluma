<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Editor</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde@2.18.0/dist/easymde.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="m-0 bg-gray-100 text-gray-800 font-sans">
    <div class="flex h-screen">
        <aside class="w-56 bg-sidebar text-white flex flex-col shrink-0">
            <div class="p-4 text-lg font-bold">
                Pluma
            </div>
            <nav class="flex flex-col gap-1 px-2">
                <div class="flex items-center justify-between rounded px-2 py-1.5 hover:bg-white/10">
                    <a href="{{ route('pages.index') }}" class="text-white no-underline text-sm font-medium grow">Pages</a>
                    <form action="{{ route('pages.store') }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-white/20 hover:bg-white/30 text-white rounded w-6 h-6 flex items-center justify-center text-sm cursor-pointer border-none" title="New page">+</button>
                    </form>
                </div>
            </nav>
        </aside>

        <main class="flex-1 overflow-y-auto p-6">
            @yield('content')
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/easymde@2.18.0/dist/easymde.min.js"></script>
    @stack('scripts')
</body>
</html>
