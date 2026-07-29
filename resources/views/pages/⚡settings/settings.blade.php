@use(App\Domain\Settings\SettingType)

<div class="grid gap-6">
    <flux:heading size="xl">Settings</flux:heading>

    <form wire:submit="save" class="grid gap-8">
        @foreach($this->groups as $group => $definitions)
            <flux:separator />

            <flux:fieldset>
                <flux:legend>{{ $group }}</flux:legend>

                <div class="grid gap-4">
                    @foreach($definitions as $definition)
                        @php($model = 'values.'.$definition->key)

                        @switch($definition->type)
                            @case(SettingType::Boolean)
                                <flux:checkbox wire:model="{{ $model }}" label="{{ $definition->label }}" description="{{ $definition->description }}" />
                                @break

                            @case(SettingType::Integer)
                                <flux:field>
                                    <flux:label>{{ $definition->label }}</flux:label>
                                    <flux:description>{{ $definition->description }}</flux:description>
                                    <flux:input type="number" wire:model="{{ $model }}" />
                                    <flux:error name="{{ $model }}" />
                                </flux:field>
                                @break

                            @case(SettingType::DateTime)
                                <flux:field>
                                    <flux:label>{{ $definition->label }}</flux:label>
                                    <flux:description>{{ $definition->description }}</flux:description>
                                    <flux:input type="datetime-local" wire:model="{{ $model }}" />
                                    <flux:error name="{{ $model }}" />
                                </flux:field>
                                @break

                            @case(SettingType::Image)
                                @php($uploadModel = 'newImages.'.$definition->key)
                                @php($image = (string) data_get($images, $definition->key))
                                @php($fieldId = 'setting-'.str_replace(['.', '_'], '-', $definition->key))

                                <flux:field>
                                    <flux:label>{{ $definition->label }}</flux:label>
                                    <flux:description>{{ $definition->description }}</flux:description>
                                    <flux:input type="file" id="{{ $fieldId }}-input" accept="image/*" wire:model="{{ $uploadModel }}" />
                                    <div class="mt-2 text-sm text-zinc-500 not-data-loading:hidden" wire:loading wire:target="{{ $uploadModel }}">Uploading...</div>
                                    @if($image !== '')
                                        <div id="{{ $fieldId }}-preview" class="mt-2 flex items-center gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 p-2">
                                            <img src="{{ route('site-assets.show', $image) }}" alt="{{ $image }}" class="w-8 h-8 shrink-0 rounded object-cover" loading="lazy" />
                                            <span class="min-w-0 flex-1 truncate text-sm">{{ $image }}</span>
                                            <flux:button wire:click="removeImage('{{ $definition->key }}')" size="xs" variant="danger">Remove</flux:button>
                                        </div>
                                    @endif
                                    <flux:error name="{{ $uploadModel }}" />
                                </flux:field>
                                @break

                            @case(SettingType::List)
                                <flux:field>
                                    <flux:label>{{ $definition->label }}</flux:label>
                                    <flux:description>{{ $definition->description }}</flux:description>
                                    <flux:textarea wire:model="{{ $model }}" placeholder="One per line" />
                                    <flux:error name="{{ $model }}" />
                                </flux:field>
                                @break

                            @default
                                <flux:field>
                                    <flux:label>{{ $definition->label }}</flux:label>
                                    <flux:description>{{ $definition->description }}</flux:description>
                                    <flux:input wire:model="{{ $model }}" />
                                    <flux:error name="{{ $model }}" />
                                </flux:field>
                        @endswitch
                    @endforeach
                </div>
            </flux:fieldset>
        @endforeach

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">Save</flux:button>
        </div>
    </form>
</div>
