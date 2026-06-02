<?php

namespace App;

use Illuminate\Contracts\Filesystem\Filesystem;

class SiteConfigLoader
{
    public function __construct(
        private Filesystem $disk,
    ) {}

    public function load(): void
    {
        if (! $this->disk->exists('config.php')) {
            return;
        }

        $siteConfig = require $this->disk->path('config.php');

        config([
            'pluma.editor_url' => $siteConfig['editor_url'] ?? config('pluma.editor_url'),
            'pluma.url' => $siteConfig['url'] ?? config('pluma.url'),
            'pluma.title' => $siteConfig['title'] ?? config('pluma.title'),
            'pluma.description' => $siteConfig['description'] ?? config('pluma.description'),
            'pluma.create_tag_pages' => $siteConfig['create_tag_pages'] ?? config('pluma.create_tag_pages'),
            'pluma.tag_pages_path' => $siteConfig['tag_pages_path'] ?? config('pluma.tag_pages_path'),
        ]);
    }
}
