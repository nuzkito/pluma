<?php

use App\Domain\Settings\RemoveSettingImage;
use App\Domain\Settings\UploadSettingImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('clears the setting and deletes the image files', function () {
    initializeSite();
    app(UploadSettingImage::class)('cover_image', UploadedFile::fake()->image('cover.png'));

    $removed = app(RemoveSettingImage::class)('cover_image');

    $disk = Storage::disk('current');
    $saved = json_decode($disk->get('pluma-settings.json'), true);

    expect($removed)->toBeTrue()
        ->and($disk->exists('assets/cover.png'))->toBeFalse()
        ->and($disk->exists('site/cover.png'))->toBeFalse()
        ->and($saved['cover_image'])->toBe('')
        ->and(config('pluma.cover_image'))->toBe('');
});

test('clears the setting when it has no image', function () {
    initializeSite();

    $removed = app(RemoveSettingImage::class)('cover_image');

    $saved = json_decode(Storage::disk('current')->get('pluma-settings.json'), true);

    expect($removed)->toBeTrue()
        ->and($saved['cover_image'])->toBe('');
});

test('does nothing when the key is not an image setting', function () {
    initializeSite();
    config(['pluma.title' => 'My Site']);

    $removed = app(RemoveSettingImage::class)('title');

    $saved = json_decode(Storage::disk('current')->get('pluma-settings.json'), true);

    expect($removed)->toBeFalse()
        ->and($saved['title'])->not->toBe('')
        ->and(config('pluma.title'))->toBe('My Site');
});

test('does nothing when the key is not a known setting', function () {
    initializeSite();

    expect(app(RemoveSettingImage::class)('unknown_key'))->toBeFalse();
});
