<?php

use App\Domain\Settings\UploadSettingImage;
use Illuminate\Http\UploadedFile;

test('stores the image, saves the setting and copies it to the site', function () {
    $image = app(UploadSettingImage::class)('cover_image', UploadedFile::fake()->image('cover.png'));

    $saved = json_decode(disk()->get('pluma-settings.json'), true);

    expect($image)->toBe('cover.png')
        ->and('assets/cover.png')->toExistOnDisk()
        ->and('site/cover.png')->toExistOnDisk()
        ->and($saved['cover_image'])->toBe('cover.png')
        ->and(config('pluma.cover_image'))->toBe('cover.png');
});

test('deletes the previous image', function () {
    $action = app(UploadSettingImage::class);
    $action('cover_image', UploadedFile::fake()->image('old.png'));
    $image = $action('cover_image', UploadedFile::fake()->image('new.png'));

    expect($image)->toBe('new.png')
        ->and('assets/old.png')->toBeMissingFromDisk()
        ->and('site/old.png')->toBeMissingFromDisk()
        ->and('assets/new.png')->toExistOnDisk()
        ->and('site/new.png')->toExistOnDisk();
});

test('keeps the new file when it replaces an image with the same name', function () {
    $action = app(UploadSettingImage::class);
    $action('cover_image', UploadedFile::fake()->image('cover.png', 10, 10));
    $action('cover_image', UploadedFile::fake()->image('cover.png', 200, 200));

    expect('assets/cover.png')->toExistOnDisk()
        ->and('site/cover.png')->toExistOnDisk()
        ->and(getimagesize(disk()->path('assets/cover.png'))[0])->toBe(200);
});

test('does nothing when the key is not an image setting', function () {
    config(['pluma.title' => 'My Site']);

    $image = app(UploadSettingImage::class)('title', UploadedFile::fake()->image('cover.png'));

    expect($image)->toBeNull()
        ->and('assets/cover.png')->toBeMissingFromDisk()
        ->and(config('pluma.title'))->toBe('My Site');
});

test('does nothing when the key is not a known setting', function () {
    $image = app(UploadSettingImage::class)('unknown_key', UploadedFile::fake()->image('cover.png'));

    expect($image)->toBeNull()
        ->and('assets/cover.png')->toBeMissingFromDisk();
});
