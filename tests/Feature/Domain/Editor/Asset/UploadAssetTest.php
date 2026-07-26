<?php

use App\Domain\Editor\Asset\UploadAsset;
use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Page\PagePath;
use App\Domain\Editor\Page\PageRepository;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('uploads a single file', function () {
    initializeSite();
    $page = ContentPage::draft('Test Page', 'test-page');
    app(PageRepository::class)->save($page);

    $file = UploadedFile::fake()->createWithContent('my-file.txt', 'content');

    $uploadAsset = app(UploadAsset::class);

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

    $uploadAsset = app(UploadAsset::class);

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

    $uploadAsset = app(UploadAsset::class);

    $uploadAsset((string) $page->path, $files);

    expect(Storage::disk('current')->exists("assets/{$page->path}/file1.txt"))->toBeTrue();
    expect(Storage::disk('current')->exists("assets/{$page->path}/file2.jpg"))->toBeTrue();
});

test('returns url with correct route', function () {
    initializeSite();
    $page = ContentPage::draft('Test Page', 'test-page');
    app(PageRepository::class)->save($page);

    $files = [UploadedFile::fake()->createWithContent('my-file.txt', 'content')];

    $uploadAsset = app(UploadAsset::class);

    $result = $uploadAsset((string) $page->path, $files);

    expect($result[0]['url'])->toContain('/assets/');
    expect($result[0]['url'])->toContain('my-file.txt');
});

test('copies the uploaded files to the generated site when the page is published', function () {
    initializeSite();
    $page = new ContentPage(
        title: 'Test Page',
        path: new PagePath('test-page'),
        content: new Markdown('# Content'),
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
    );
    app(PageRepository::class)->save($page);

    $files = [
        UploadedFile::fake()->image('photo.jpg', 3000, 2000),
        UploadedFile::fake()->createWithContent('notes.txt', 'text content'),
    ];

    app(UploadAsset::class)((string) $page->path, $files);

    $disk = Storage::disk('current');

    expect($disk->exists("site/{$page->path}/photo.jpg"))->toBeTrue()
        ->and($disk->size("site/{$page->path}/photo.jpg"))->toBeLessThan($disk->size("assets/{$page->path}/photo.jpg"))
        ->and($disk->get("site/{$page->path}/notes.txt"))->toBe('text content');
});

test('does not copy the uploaded files to the generated site when the page is a draft', function () {
    initializeSite();
    $page = ContentPage::draft('Test Page', 'test-page');
    app(PageRepository::class)->save($page);

    app(UploadAsset::class)((string) $page->path, [UploadedFile::fake()->createWithContent('notes.txt', 'text content')]);

    expect(Storage::disk('current')->exists("site/{$page->path}/notes.txt"))->toBeFalse();
});
