<?php

namespace App\Domain\Settings;

use Illuminate\Contracts\Filesystem\Filesystem;

class SiteConfigLoader
{
    public function __construct(
        private Filesystem $disk,
    ) {}

    public function load(): void
    {
        if (! $this->disk->exists('pluma-settings.json')) {
            return;
        }

        $settings = json_decode($this->disk->get('pluma-settings.json'), true) ?? [];

        foreach (SettingsSchema::definitions() as $definition) {
            $value = data_get($settings, $definition->key);

            if ($value === null) {
                continue;
            }

            config(['pluma.'.$definition->key => $definition->type->cast($value)]);
        }
    }
}
