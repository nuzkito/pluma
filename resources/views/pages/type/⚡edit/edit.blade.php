<div class="grid gap-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('types.index')" separator="slash" icon="squares-2x2" wire:navigate />
                <flux:breadcrumbs.item separator="slash">{{ $type->name }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <span class="text-xs text-gray-400" wire:loading.class="opacity-50" wire:target="updatedName">
                <span wire:loading.delay>Saving...</span>
                <span wire:loading.delay.remove>Saved</span>
            </span>
        </div>

        <flux:button wire:click="delete" variant="danger" icon="trash">Delete</flux:button>
    </div>

    <flux:input type="text" id="name" wire:model.live.blur="name" label="Name" />
</div>
