<?php

use App\Domain\Editor\Asset\AssetRepository;
use App\Domain\Editor\Asset\DeleteAsset;
use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Page\PagePath;
use App\Domain\Editor\Page\PageRepository;
use App\Domain\Generator\SiteGenerator;
use Illuminate\Support\Facades\Storage;

test('deletes an asset', function () {
    initializeSite();
    $page = ContentPage::draft('Test Page', 'test-page');
    app(PageRepository::class)->save($page);

    Storage::disk('current')->put("assets/{$page->path}/file.txt", 'content');

    $deleteAsset = new DeleteAsset(
        app(PageRepository::class),
        app(AssetRepository::class),
        app(SiteGenerator::class)
    );

    $deleteAsset((string) $page->path, 'file.txt');

    expect(Storage::disk('current')->exists("assets/{$page->path}/file.txt"))->toBeFalse();
});

test('deletes asset and removes empty assets directory', function () {
    initializeSite();
    $page = ContentPage::draft('Test Page', 'test-page');
    app(PageRepository::class)->save($page);

    Storage::disk('current')->put("assets/{$page->path}/file.txt", 'content');

    $deleteAsset = new DeleteAsset(
        app(PageRepository::class),
        app(AssetRepository::class),
        app(SiteGenerator::class)
    );

    $deleteAsset((string) $page->path, 'file.txt');

    expect(Storage::disk('current')->exists("assets/{$page->path}"))->toBeFalse();
});

test('deletes asset from site directory when page is published', function () {
    initializeSite();
    $page = ContentPage::draft('Test Page', 'test-page');
    app(PageRepository::class)->save($page);

    Storage::disk('current')->put("assets/{$page->path}/file.txt", 'content');
    Storage::disk('current')->put("site/{$page->path}/file.txt", 'published content');

    $page->publish(Carbon\Carbon::now());
    app(PageRepository::class)->save($page);

    $deleteAsset = new DeleteAsset(
        app(PageRepository::class),
        app(AssetRepository::class),
        app(SiteGenerator::class)
    );

    $deleteAsset((string) $page->path, 'file.txt');

    expect(Storage::disk('current')->exists("site/{$page->path}/file.txt"))->toBeFalse();
});

test('does not delete site file when page is not published', function () {
    initializeSite();
    $page = ContentPage::draft('Test Page', 'test-page');
    app(PageRepository::class)->save($page);

    Storage::disk('current')->put("assets/{$page->path}/file.txt", 'content');
    Storage::disk('current')->put("site/{$page->path}/file.txt", 'stale content');

    $deleteAsset = new DeleteAsset(
        app(PageRepository::class),
        app(AssetRepository::class),
        app(SiteGenerator::class)
    );

    $deleteAsset((string) $page->path, 'file.txt');

    expect(Storage::disk('current')->exists("site/{$page->path}/file.txt"))->toBeTrue();
});

test('removes the cover image when the deleted asset is the cover', function () {
    initializeSite();
    $page = ContentPage::draft('Test Page', 'test-page');
    $page->changeCoverImage('header.png');
    app(PageRepository::class)->save($page);

    Storage::disk('current')->put("assets/{$page->path}/header.png", 'binary');

    $deleteAsset = new DeleteAsset(
        app(PageRepository::class),
        app(AssetRepository::class),
        app(SiteGenerator::class)
    );

    $deleteAsset((string) $page->path, 'header.png');

    expect(app(PageRepository::class)->findByPath('test-page')->cover_image)->toBeNull();
});

test('keeps the cover image when a different asset is deleted', function () {
    initializeSite();
    $page = ContentPage::draft('Test Page', 'test-page');
    $page->changeCoverImage('header.png');
    app(PageRepository::class)->save($page);

    Storage::disk('current')->put("assets/{$page->path}/header.png", 'binary');
    Storage::disk('current')->put("assets/{$page->path}/other.txt", 'content');

    $deleteAsset = new DeleteAsset(
        app(PageRepository::class),
        app(AssetRepository::class),
        app(SiteGenerator::class)
    );

    $deleteAsset((string) $page->path, 'other.txt');

    expect(app(PageRepository::class)->findByPath('test-page')->cover_image)->toBe('header.png');
});

test('regenerates the published page when its cover image is deleted', function () {
    initializeSite();
    $page = new ContentPage(
        title: 'Test Page',
        path: new PagePath('test-page'),
        content: new Markdown('# Content'),
        created_at: Carbon\Carbon::now(),
        published_at: Carbon\Carbon::now(),
        cover_image: 'header.png',
    );
    app(PageRepository::class)->save($page);

    Storage::disk('current')->put("assets/{$page->path}/header.png", 'binary');

    $siteGenerator = mock(SiteGenerator::class, function ($mock) {
        $mock->shouldReceive('generatePage')->once()->with('test-page');
        $mock->shouldReceive('removePageFile')->once()->with('test-page', 'header.png');
    });

    $deleteAsset = new DeleteAsset(
        app(PageRepository::class),
        app(AssetRepository::class),
        $siteGenerator
    );

    $deleteAsset((string) $page->path, 'header.png');

    expect(app(PageRepository::class)->findByPath('test-page')->cover_image)->toBeNull();
});

test('does not regenerate the page when a non-cover asset is deleted', function () {
    initializeSite();
    $page = new ContentPage(
        title: 'Test Page',
        path: new PagePath('test-page'),
        content: new Markdown('# Content'),
        created_at: Carbon\Carbon::now(),
        published_at: Carbon\Carbon::now(),
        cover_image: 'header.png',
    );
    app(PageRepository::class)->save($page);

    Storage::disk('current')->put("assets/{$page->path}/header.png", 'binary');
    Storage::disk('current')->put("assets/{$page->path}/other.txt", 'content');

    $siteGenerator = mock(SiteGenerator::class, function ($mock) {
        $mock->shouldNotReceive('generatePage');
        $mock->shouldReceive('removePageFile')->once()->with('test-page', 'other.txt');
    });

    $deleteAsset = new DeleteAsset(
        app(PageRepository::class),
        app(AssetRepository::class),
        $siteGenerator
    );

    $deleteAsset((string) $page->path, 'other.txt');
});

test('does not regenerate a draft page when its cover image is deleted', function () {
    initializeSite();
    $page = new ContentPage(
        title: 'Test Page',
        path: new PagePath('test-page'),
        content: new Markdown('# Content'),
        created_at: Carbon\Carbon::now(),
        cover_image: 'header.png',
    );
    app(PageRepository::class)->save($page);

    Storage::disk('current')->put("assets/{$page->path}/header.png", 'binary');

    $siteGenerator = mock(SiteGenerator::class, function ($mock) {
        $mock->shouldNotReceive('generatePage');
    });

    $deleteAsset = new DeleteAsset(
        app(PageRepository::class),
        app(AssetRepository::class),
        $siteGenerator
    );

    $deleteAsset((string) $page->path, 'header.png');

    expect(app(PageRepository::class)->findByPath('test-page')->cover_image)->toBeNull();
});

test('does nothing when page does not exist', function () {
    initializeSite();

    $deleteAsset = new DeleteAsset(
        app(PageRepository::class),
        app(AssetRepository::class),
        app(SiteGenerator::class)
    );

    $deleteAsset('non-existent-page', 'file.txt');

    expect(true)->toBeTrue();
});
