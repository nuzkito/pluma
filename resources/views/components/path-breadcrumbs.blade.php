@props(['path' => ''])

@php
    $segments = array_values(array_filter(explode('/', (string) $path)));

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
