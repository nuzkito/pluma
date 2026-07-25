<?php

use App\Domain\Editor\Asset\AssetRepository;
use App\Domain\Editor\Asset\UploadAsset;
use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\PageRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('uploads a single file', function () {
    initializeSite();
    $page = ContentPage::draft('Test Page', 'test-page');
    app(PageRepository::class)->save($page);

    $file = UploadedFile::fake()->createWithContent('my-file.txt', 'content');

    $uploadAsset = new UploadAsset(
        app(AssetRepository::class)
    );

    $result = $uploadAsset((string) $page->path, [$file]);

    expect($result)->toHaveCount(1);
    expect($result[0])->toHaveKeys(['filename', 'url']);
    expect($result[0]['filename'])->toBe('my-file.txt');
});

test('uploads multiple files', function () {
    initializeSite();
    $page = ContentPage::draft('Test Page', 'test-page');
    app(PageRepository::class)->save($page);

    $files = [
        UploadedFile::fake()->createWithContent('file1.txt', 'a'),
        UploadedFile::fake()->createWithContent('file2.jpg', 'b'),
        UploadedFile::fake()->createWithContent('file3.pdf', 'c'),
    ];

    $uploadAsset = new UploadAsset(
        app(AssetRepository::class)
    );

    $result = $uploadAsset((string) $page->path, $files);

    expect($result)->toHaveCount(3);
    expect(collect($result)->pluck('filename')->all())->toEqualCanonicalizing(['file1.txt', 'file2.jpg', 'file3.pdf']);
});

test('saves files to correct assets directory', function () {
    initializeSite();
    $page = ContentPage::draft('Test Page', 'test-page');
    app(PageRepository::class)->save($page);

    $files = [
        UploadedFile::fake()->createWithContent('file1.txt', 'a'),
        UploadedFile::fake()->createWithContent('file2.jpg', 'b'),
    ];

    $uploadAsset = new UploadAsset(
        app(AssetRepository::class)
    );

    $uploadAsset((string) $page->path, $files);

    expect(Storage::disk('current')->exists("assets/{$page->path}/file1.txt"))->toBeTrue();
    expect(Storage::disk('current')->exists("assets/{$page->path}/file2.jpg"))->toBeTrue();
});

test('returns url with correct route', function () {
    initializeSite();
    $page = ContentPage::draft('Test Page', 'test-page');
    app(PageRepository::class)->save($page);

    $files = [UploadedFile::fake()->createWithContent('my-file.txt', 'content')];

    $uploadAsset = new UploadAsset(
        app(AssetRepository::class)
    );

    $result = $uploadAsset((string) $page->path, $files);

    expect($result[0]['url'])->toContain('/assets/');
    expect($result[0]['url'])->toContain('my-file.txt');
});
