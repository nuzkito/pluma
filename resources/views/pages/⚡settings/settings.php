<?php

use App\Domain\Settings\SettingDefinition;
use App\Domain\Settings\SettingsSchema;
use App\Domain\Settings\SiteConfigLoader;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    /**
     * @var array<string, mixed>
     */
    public array $values = [];

    public function mount(): void
    {
        foreach (SettingsSchema::definitions() as $definition) {
            data_set($this->values, $definition->key, $definition->type->forForm(config('pluma.'.$definition->key)));
        }
    }

    public function save(): void
    {
        $this->validate($this->validationRules());

        $settings = [];

        foreach (SettingsSchema::definitions() as $definition) {
            $value = $definition->type->fromForm(data_get($this->values, $definition->key));
            data_set($settings, $definition->key, $value);
        }

        Storage::disk('current')->put(
            'pluma-settings.json',
            json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );

        (new SiteConfigLoader(Storage::disk('current')))->load();

        Flux::toast('Settings saved.', variant: 'success');
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function validationRules(): array
    {
        $rules = [];

        foreach (SettingsSchema::definitions() as $definition) {
            if ($definition->rules !== []) {
                $rules["values.{$definition->key}"] = $definition->rules;
            }
        }

        return $rules;
    }

    /**
     * @return array<string, list<SettingDefinition>>
     */
    #[Computed]
    public function groups(): array
    {
        return SettingsSchema::grouped();
    }
};
