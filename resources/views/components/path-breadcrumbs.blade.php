@props(['path' => ''])

@php
    $segments = explode('/', (string) $path)
        |> array_filter(...)
        |> array_values(...);

    $crumbs = [];
    $cumulative = '';

    foreach ($segments as $segment) {
        $cumulative = trim("$cumulative/$segment", '/');
        $crumbs[] = ['name' => $segment, 'directory' => $cumulative];
    }
@endphp

<flux:breadcrumbs>
    <flux:breadcrumbs.item :href="$crumbs === [] ? null : route('pages.index')" separator="slash" icon="home" wire:navigate />

    @foreach($crumbs as $crumb)
        <flux:breadcrumbs.item :href="$loop->last ? null : route('pages.index', ['directory' => $crumb['directory']])" separator="slash" wire:navigate>{{ $crumb['name'] }}</flux:breadcrumbs.item>
    @endforeach
</flux:breadcrumbs>
