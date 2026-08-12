<?php

use App\Domain\Settings\RemoveSettingImage;
use App\Domain\Settings\UploadSettingImage;
use Illuminate\Http\UploadedFile;

test('clears the setting and deletes the image files', function () {
    app(UploadSettingImage::class)('cover_image', UploadedFile::fake()->image('cover.png'));

    $removed = app(RemoveSettingImage::class)('cover_image');

    $saved = json_decode(disk()->get('pluma-settings.json'), true);

    expect($removed)->toBeTrue()
        ->and('assets/cover.png')->toBeMissingFromDisk()
        ->and('site/cover.png')->toBeMissingFromDisk()
        ->and($saved['cover_image'])->toBe('')
        ->and(config('pluma.cover_image'))->toBe('');
});

test('clears the setting when it has no image', function () {
    $removed = app(RemoveSettingImage::class)('cover_image');

    $saved = json_decode(disk()->get('pluma-settings.json'), true);

    expect($removed)->toBeTrue()
        ->and($saved['cover_image'])->toBe('');
});

test('does nothing when the key is not an image setting', function () {
    config(['pluma.title' => 'My Site']);

    $removed = app(RemoveSettingImage::class)('title');

    $saved = json_decode(disk()->get('pluma-settings.json'), true);

    expect($removed)->toBeFalse()
        ->and($saved['title'])->not->toBe('')
        ->and(config('pluma.title'))->toBe('My Site');
});

test('does nothing when the key is not a known setting', function () {
    expect(app(RemoveSettingImage::class)('unknown_key'))->toBeFalse();
});
