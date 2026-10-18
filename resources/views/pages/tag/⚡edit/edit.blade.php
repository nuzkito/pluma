<div class="grid gap-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('tags.index')" separator="slash" icon="tag" wire:navigate />
                <flux:breadcrumbs.item separator="slash">{{ $tag->name }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            @if(config('pluma.tags.create_pages'))
                <flux:badge color="emerald">Page at /{{ $tag->path }}/</flux:badge>
            @else
                <flux:badge color="zinc">No page</flux:badge>
            @endif

            <span class="text-xs text-gray-400" wire:loading.class="opacity-50" wire:target="updatedName,updatedSlug,updatedContent,updatedNewAssets,setCoverImage,deleteAsset">
                <span wire:loading.delay>Saving...</span>
                <span wire:loading.delay.remove>Saved</span>
            </span>
        </div>

        <flux:button wire:click="delete" variant="danger" icon="trash">Delete</flux:button>
    </div>

    <flux:input type="text" id="name" wire:model.live.blur="name" label="Name" />

    <flux:input type="text" id="slug" wire:model.live.blur="slug" label="Slug" description="The last segment of the URL of the tag page." />

    <x-markdown-content />

    <x-asset-manager :$assets :cover-image="$cover_image" :uploading="count($newAssets)" />
</div>
