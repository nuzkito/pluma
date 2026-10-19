<div class="grid gap-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <x-path-breadcrumbs :path="$page->path" />

            @if($page->isPublished())
                <flux:badge color="emerald">Published</flux:badge>
            @else
                <flux:badge color="amber">Draft</flux:badge>
            @endif

            <span class="text-xs text-gray-400" wire:loading.class="opacity-50" wire:target="addTag,removeTag,publish,unpublish,delete,updatedTitle,updatedPath,updatedContent,updatedType,updatedRss,updatedPublishedAt,updatedRevisedAt,updatedNewAssets,setCoverImage,deleteAsset">
                <span wire:loading.delay>Saving...</span>
                <span wire:loading.delay.remove>Saved</span>
            </span>
        </div>
        <div class="flex gap-2">
            @if($page->isPublished())
                <flux:button wire:click="unpublish">Unpublish</flux:button>
            @else
                <flux:button wire:click="publish" variant="primary">Publish</flux:button>
            @endif
            <flux:button wire:click="delete" variant="danger">Delete</flux:button>
        </div>
    </div>

    <flux:input type="text" id="title" wire:model.live.blur="title" label="Title" />

    <flux:input type="text" id="path" wire:model.live.blur="path" label="Path" />

    <flux:field>
        <flux:label>Type</flux:label>
        <flux:input type="text" id="type" wire:model.live.blur="type" list="available-types" />
        <datalist id="available-types">
            @foreach($this->availableTypes as $typeName)
                <option value="{{ $typeName }}">{{ $typeName }}</option>
            @endforeach
        </datalist>
        <flux:error name="type" />
    </flux:field>

    @if(config('pluma.rss.enabled'))
        <flux:checkbox wire:model.live="rss" label="Include in RSS feed" />
    @endif

    <flux:field>
        <flux:label>Tags</flux:label>
        <flux:input x-on:keydown.enter.prevent="$wire.addTag($el.value); $el.value = ''" x-on:blur="$wire.addTag($el.value); $el.value = ''" list="available-tags" />
        <datalist id="available-tags">
            @foreach($this->availableTags as $tagName)
                <option value="{{ $tagName }}">{{ $tagName }}</option>
            @endforeach
        </datalist>
        <div id="tags-list" class="mt-1 flex flex-wrap gap-2">
            @foreach($this->tags as $index => $tag)
                <flux:badge :wire-key="'tag-' . $tag">
                    {{ $this->tagNames[$tag] ?? $tag }}
                    <flux:badge.close wire:click="removeTag({{ $index }})" />
                </flux:badge>
            @endforeach
        </div>
    </flux:field>

    <flux:input type="datetime-local" wire:model.live="published_at" label="Published at" />

    @if($page->isPublished())
        <flux:input type="datetime-local" wire:model.live="revised_at" label="Revised at" />
    @endif

    <x-markdown-content />

    <x-asset-manager :$assets :cover-image="$cover_image" :uploading="count($newAssets)" />
</div>
