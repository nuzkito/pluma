<?php

use App\Domain\Settings\SiteConfigLoader;
use Illuminate\Support\Facades\Storage;

test('reads nested settings from pluma-settings.json into config', function () {
    Storage::fake('current');
    Storage::disk('current')->put('pluma-settings.json', json_encode([
        'title' => 'Loaded Title',
        'tags' => ['create_pages' => true, 'pages_path' => 'topics'],
        'rss' => ['enabled' => true],
        'embedding' => ['enabled' => true, 'allowed_domains' => ['example.com']],
    ]));

    (new SiteConfigLoader(Storage::disk('current')))->load();

    expect(config('pluma.title'))->toBe('Loaded Title')
        ->and(config('pluma.tags.create_pages'))->toBeTrue()
        ->and(config('pluma.tags.pages_path'))->toBe('topics')
        ->and(config('pluma.rss.enabled'))->toBeTrue()
        ->and(config('pluma.embedding.allowed_domains'))->toBe(['example.com']);
});

test('keeps config defaults for keys missing from the json', function () {
    Storage::fake('current');
    Storage::disk('current')->put('pluma-settings.json', json_encode([
        'title' => 'Only Title',
    ]));

    (new SiteConfigLoader(Storage::disk('current')))->load();

    expect(config('pluma.title'))->toBe('Only Title')
        ->and(config('pluma.tags.pages_path'))->toBe('tags')
        ->and(config('pluma.embedding.allowed_domains'))->toBe(['youtube.com', 'x.com', 'github.com']);
});

test('does nothing when the settings file is absent', function () {
    Storage::fake('current');

    (new SiteConfigLoader(Storage::disk('current')))->load();

    expect(config('pluma.tags.pages_path'))->toBe('tags');
});
