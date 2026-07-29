<?php

use App\Domain\Settings\UploadSettingImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('stores the image, saves the setting and copies it to the site', function () {
    initializeSite();

    $image = app(UploadSettingImage::class)('cover_image', UploadedFile::fake()->image('cover.png'));

    $disk = Storage::disk('current');
    $saved = json_decode($disk->get('pluma-settings.json'), true);

    expect($image)->toBe('cover.png')
        ->and($disk->exists('assets/cover.png'))->toBeTrue()
        ->and($disk->exists('site/cover.png'))->toBeTrue()
        ->and($saved['cover_image'])->toBe('cover.png')
        ->and(config('pluma.cover_image'))->toBe('cover.png');
});

test('deletes the previous image', function () {
    initializeSite();

    $action = app(UploadSettingImage::class);
    $action('cover_image', UploadedFile::fake()->image('old.png'));
    $image = $action('cover_image', UploadedFile::fake()->image('new.png'));

    $disk = Storage::disk('current');

    expect($image)->toBe('new.png')
        ->and($disk->exists('assets/old.png'))->toBeFalse()
        ->and($disk->exists('site/old.png'))->toBeFalse()
        ->and($disk->exists('assets/new.png'))->toBeTrue()
        ->and($disk->exists('site/new.png'))->toBeTrue();
});

test('keeps the new file when it replaces an image with the same name', function () {
    initializeSite();

    $action = app(UploadSettingImage::class);
    $action('cover_image', UploadedFile::fake()->image('cover.png', 10, 10));
    $action('cover_image', UploadedFile::fake()->image('cover.png', 200, 200));

    $disk = Storage::disk('current');

    expect($disk->exists('assets/cover.png'))->toBeTrue()
        ->and($disk->exists('site/cover.png'))->toBeTrue()
        ->and(getimagesize($disk->path('assets/cover.png'))[0])->toBe(200);
});

test('does nothing when the key is not an image setting', function () {
    initializeSite();
    config(['pluma.title' => 'My Site']);

    $image = app(UploadSettingImage::class)('title', UploadedFile::fake()->image('cover.png'));

    expect($image)->toBeNull()
        ->and(Storage::disk('current')->exists('assets/cover.png'))->toBeFalse()
        ->and(config('pluma.title'))->toBe('My Site');
});

test('does nothing when the key is not a known setting', function () {
    initializeSite();

    $image = app(UploadSettingImage::class)('unknown_key', UploadedFile::fake()->image('cover.png'));

    expect($image)->toBeNull()
        ->and(Storage::disk('current')->exists('assets/cover.png'))->toBeFalse();
});
