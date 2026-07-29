<?php

namespace App\Domain\Settings;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class SettingsRepository
{
    private const string FILE = 'pluma-settings.json';

    private Filesystem $disk;

    public function __construct()
    {
        $this->disk = Storage::disk('current');
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if (! $this->disk->exists(self::FILE)) {
            return [];
        }

        return json_decode($this->disk->get(self::FILE), true) ?? [];
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function save(array $settings): void
    {
        $this->disk->put(self::FILE, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        (new SiteConfigLoader($this->disk))->load();
    }

    /**
     * Persist a single setting, leaving the rest of the file untouched.
     */
    public function put(string $key, mixed $value): void
    {
        $settings = $this->all();

        data_set($settings, $key, $value);

        $this->save($settings);
    }
}
