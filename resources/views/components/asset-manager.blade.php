@props(['assets' => [], 'coverImage' => null, 'uploading' => 0])

@php
    $isImage = fn (string $filename): bool => in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'], true);
@endphp

<flux:field id="asset-drop-zone" class="group">
    <flux:label>Assets</flux:label>
    <div class="relative group-data-dragging:min-h-24">
        <flux:input type="file" id="asset-input" multiple wire:model="newAssets" />
        <div class="mt-2 text-sm text-zinc-500 not-data-loading:hidden" wire:loading wire:target="newAssets">Uploading {{ $uploading }} file(s)...</div>
        <div id="assets-list" class="mt-2 space-y-2">
            @foreach($assets as $asset)
                <div class="flex items-center gap-3 rounded-lg border border-zinc-200 p-2 transition-colors hover:bg-zinc-50" wire:key="asset-{{ $asset['filename'] }}">
                    @if($isImage($asset['filename']))
                        <img src="{{ $asset['url'] }}" alt="{{ $asset['filename'] }}" class="w-8 h-8 shrink-0 rounded object-cover" loading="lazy" />
                    @else
                        <div class="flex w-8 h-8 shrink-0 items-center justify-center rounded bg-zinc-100 text-xs font-bold uppercase text-zinc-500">
                            {{ pathinfo($asset['filename'], PATHINFO_EXTENSION) }}
                        </div>
                    @endif
                    <span class="min-w-0 flex-1 truncate text-sm">{{ $asset['filename'] }}</span>
                    @if($coverImage === $asset['filename'])
                        <flux:badge :wire-key="'cover-' . $asset['filename']" size="sm" color="emerald" icon="photo">Cover image</flux:badge>
                    @elseif($isImage($asset['filename']))
                        <flux:button :wire-key="'cover-' . $asset['filename']" wire:click="setCoverImage('{{ $asset['filename'] }}')" size="xs">Add as cover image</flux:button>
                    @endif
                    <flux:button :wire-key="'insert-' . $asset['filename']" size="xs" x-on:click="insertAsset('{{ $asset['filename'] }}')">Insertar</flux:button>
                    <flux:button :wire-key="'delete-' . $asset['filename']" wire:click="deleteAsset('{{ $asset['filename'] }}')" size="xs" variant="danger">Eliminar</flux:button>
                </div>
            @endforeach
        </div>
        <div id="asset-drop-zone-overlay" class="pointer-events-none absolute inset-0 z-10 hidden flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-zinc-300 bg-white/80 text-sm font-medium text-zinc-500 group-data-dragging:flex group-data-over:border-blue-500 group-data-over:text-blue-600 dark:border-zinc-600 dark:bg-zinc-800/80 dark:text-zinc-400 dark:group-data-over:text-blue-400">
            <flux:icon.arrow-up-tray class="size-6" />
            Drop files here to upload
        </div>
    </div>
</flux:field>
