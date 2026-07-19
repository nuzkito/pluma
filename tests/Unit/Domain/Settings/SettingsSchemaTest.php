<?php

use App\Domain\Settings\SettingsSchema;

test('grouped keys definitions by group preserving declaration order', function () {
    $grouped = SettingsSchema::grouped();

    expect(array_keys($grouped))->toBe(['General', 'Tags', 'RSS', 'Embedding'])
        ->and(array_merge(...array_values($grouped)))->toEqual(SettingsSchema::definitions());
});

test('grouped places each definition in its declared group', function () {
    $grouped = SettingsSchema::grouped();

    expect(array_column($grouped['General'], 'key'))->toBe(['editor_url', 'url', 'title', 'description'])
        ->and(array_column($grouped['Tags'], 'key'))->toBe(['tags.create_pages', 'tags.pages_path'])
        ->and(array_column($grouped['RSS'], 'key'))->toBe(['rss.enabled'])
        ->and(array_column($grouped['Embedding'], 'key'))->toBe(['embedding.enabled', 'embedding.allowed_domains']);
});
