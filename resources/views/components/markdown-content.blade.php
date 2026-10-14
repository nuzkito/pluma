{{-- The textarea that mountMarkdownEditor() turns into the Markdown editor, bound to the content property. --}}
<flux:field wire:ignore id="content-drop-zone" class="group">
    <flux:label>Content</flux:label>
    <div class="rounded-lg outline-2 outline-offset-4 outline-dashed outline-transparent group-data-dragging:outline-zinc-300 group-data-over:outline-blue-500 dark:group-data-dragging:outline-zinc-600">
        <flux:textarea id="content" wire:model="content"></flux:textarea>
    </div>
</flux:field>
