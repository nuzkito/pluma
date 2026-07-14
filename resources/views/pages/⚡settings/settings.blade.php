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
