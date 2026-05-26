<div class="grid gap-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item separator="slash" icon="home"></flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $page->path }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            @if($page->isPublished())
                <flux:badge color="emerald">Published</flux:badge>
            @else
                <flux:badge color="amber">Draft</flux:badge>
            @endif

            <span class="text-xs text-gray-400" wire:loading.class="opacity-50" wire:target="addTag,removeTag,publish,unpublish,delete,updatedTitle,updatedPath,updatedContent,updatedRss,updatedPublishedAt,updatedNewAttachments">
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

    <flux:checkbox wire:model.live="rss" label="Include in RSS feed" />

    <flux:field>
        <flux:label>Tags</flux:label>
        <flux:input x-on:keydown.enter.prevent="$wire.addTag($el.value); $el.value = ''" x-on:blur="$wire.addTag($el.value); $el.value = ''" list="available-tags" />
        <datalist id="available-tags">
            @foreach(array_diff($this->availableTags, $this->tags) as $tag)
                <option value="{{ $tag }}">{{ $tag }}</option>
            @endforeach
        </datalist>
        <div id="tags-list" class="mt-1 flex flex-wrap gap-2">
            @foreach($this->tags as $index => $tag)
                <flux:badge wire:key="tag-{{ $index }}">
                    {{ $tag }}
                    <flux:badge.close wire:click="removeTag({{ $index }})" />
                </flux:badge>
            @endforeach
        </div>
    </flux:field>

    <flux:input type="datetime-local" wire:model.live="published_at" label="Published at" />

    <flux:field wire:ignore>
        <flux:label>Content</flux:label>
        <flux:textarea id="content" wire:model="content"></flux:textarea>
    </flux:field>

    <flux:field>
        <flux:label>Attachments</flux:label>
        <flux:input type="file" id="attachment-input" multiple wire:model="newAttachments" />
        <div class="mt-2 text-sm text-zinc-500 not-data-loading:hidden" wire:loading wire:target="newAttachments">Uploading {{ count($newAttachments) }} file(s)...</div>
        <div id="attachments-list" class="mt-2 space-y-2">
            @foreach($attachments as $attachment)
                <div class="flex items-center gap-3 rounded-lg border border-zinc-200 p-2 transition-colors hover:bg-zinc-50">
                    @if($this->isImage($attachment['filename']))
                        <img src="{{ $attachment['url'] }}" alt="{{ $attachment['filename'] }}" class="w-8 h-8 shrink-0 rounded object-cover" loading="lazy" />
                    @else
                        <div class="flex w-8 h-8 shrink-0 items-center justify-center rounded bg-zinc-100 text-xs font-bold uppercase text-zinc-500">
                            {{ pathinfo($attachment['filename'], PATHINFO_EXTENSION) }}
                        </div>
                    @endif
                    <span class="min-w-0 flex-1 truncate text-sm">{{ $attachment['filename'] }}</span>
                    <flux:button wire:key="insert-{{ $loop->index }}" size="xs" x-on:click="insertAttachment('{{ $attachment['filename'] }}')">Insertar</flux:button>
                    <flux:button wire:key="delete-{{ $loop->index }}" wire:click="deleteAttachment('{{ $attachment['filename'] }}')" size="xs" variant="danger">Eliminar</flux:button>
                </div>
            @endforeach
        </div>
    </flux:field>
</div>
