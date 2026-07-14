<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }} - Editor</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @fluxAppearance
    @stack('styles')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
    <flux:sidebar sticky collapsible="mobile"
        class="bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
        <flux:sidebar.brand>
            Pluma
        </flux:sidebar.brand>

        <flux:navlist>
            <flux:navlist.item href="{{ route('pages.index') }}" class="pl-10" wire:navigate>Pages</flux:navlist.item>
            <flux:navlist.item href="{{ route('settings.index') }}" icon="cog-6-tooth" wire:navigate>Settings</flux:navlist.item>
        </flux:navlist>

        <flux:spacer />

    </flux:sidebar>

    <flux:main class="flex-1 overflow-y-auto p-6">
        {{ $slot }}
    </flux:main>

    <flux:toast />

    @livewireScripts
    @fluxScripts
    @stack('scripts')
</body>

</html>
