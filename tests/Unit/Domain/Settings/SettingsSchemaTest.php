<?php

use App\Domain\Settings\SettingsSchema;
use App\Domain\Settings\SettingType;

test('grouped keys definitions by group preserving declaration order', function () {
    $grouped = SettingsSchema::grouped();

    expect(array_keys($grouped))->toBe(['General', 'Tags', 'RSS', 'Embedding'])
        ->and(array_merge(...array_values($grouped)))->toEqual(SettingsSchema::definitions());
});

test('grouped places each definition in its declared group', function () {
    $grouped = SettingsSchema::grouped();

    expect(array_column($grouped['General'], 'key'))->toBe(['editor_url', 'url', 'title', 'description', 'cover_image'])
        ->and(array_column($grouped['Tags'], 'key'))->toBe(['tags.create_pages', 'tags.pages_path'])
        ->and(array_column($grouped['RSS'], 'key'))->toBe(['rss.enabled'])
        ->and(array_column($grouped['Embedding'], 'key'))->toBe(['embedding.enabled', 'embedding.allowed_domains']);
});

test('find returns the definition with the given key', function () {
    expect(SettingsSchema::find('tags.pages_path')->label)->toBe('Tag pages path');
});

test('find returns null for an unknown key', function () {
    expect(SettingsSchema::find('unknown'))->toBeNull();
});

test('ofType returns every definition of the given type', function () {
    expect(array_column(SettingsSchema::ofType(SettingType::Image), 'key'))->toBe(['cover_image'])
        ->and(array_column(SettingsSchema::ofType(SettingType::List), 'key'))->toBe(['embedding.allowed_domains']);
});
