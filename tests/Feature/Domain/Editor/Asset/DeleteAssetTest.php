<?php

use App\Domain\Editor\Asset\DeleteAsset;
use App\Domain\Generator\SiteGenerator;

use function Pest\Laravel\mock;

test('deletes an asset', function () {
    $page = aPage('Test Page', 'test-page');

    disk()->put("assets/{$page->path}/file.txt", 'content');

    $deleteAsset = app(DeleteAsset::class);

    $deleteAsset((string) $page->path, 'file.txt');

    expect("assets/{$page->path}/file.txt")->toBeMissingFromDisk();
});

test('deletes asset and removes empty assets directory', function () {
    $page = aPage('Test Page', 'test-page');

    disk()->put("assets/{$page->path}/file.txt", 'content');

    $deleteAsset = app(DeleteAsset::class);

    $deleteAsset((string) $page->path, 'file.txt');

    expect("assets/{$page->path}")->toBeMissingFromDisk();
});

test('deletes asset from site directory when page is published', function () {
    $page = aPage('Test Page', 'test-page');

    disk()->put("assets/{$page->path}/file.txt", 'content');
    disk()->put("site/{$page->path}/file.txt", 'published content');

    $page->publish(Carbon\Carbon::now());
    repository()->save($page);

    $deleteAsset = app(DeleteAsset::class);

    $deleteAsset((string) $page->path, 'file.txt');

    expect("site/{$page->path}/file.txt")->toBeMissingFromDisk();
});

test('does not delete site file when page is not published', function () {
    $page = aPage('Test Page', 'test-page');

    disk()->put("assets/{$page->path}/file.txt", 'content');
    disk()->put("site/{$page->path}/file.txt", 'stale content');

    $deleteAsset = app(DeleteAsset::class);

    $deleteAsset((string) $page->path, 'file.txt');

    expect("site/{$page->path}/file.txt")->toExistOnDisk();
});

test('removes the cover image when the deleted asset is the cover', function () {
    $page = aPage('Test Page', 'test-page', cover_image: 'header.png');

    disk()->put("assets/{$page->path}/header.png", 'binary');

    $deleteAsset = app(DeleteAsset::class);

    $deleteAsset((string) $page->path, 'header.png');

    expect(repository()->findByPath('test-page')->cover_image)->toBeNull();
});

test('keeps the cover image when a different asset is deleted', function () {
    $page = aPage('Test Page', 'test-page', cover_image: 'header.png');

    disk()->put("assets/{$page->path}/header.png", 'binary');
    disk()->put("assets/{$page->path}/other.txt", 'content');

    $deleteAsset = app(DeleteAsset::class);

    $deleteAsset((string) $page->path, 'other.txt');

    expect(repository()->findByPath('test-page')->cover_image)->toBe('header.png');
});

test('regenerates the published page when its cover image is deleted', function () {
    $page = aPublishedPage(
        'Test Page',
        'test-page',
        content: '# Content',
        created_at: Carbon\Carbon::now(),
        published_at: Carbon\Carbon::now(),
        cover_image: 'header.png',
    );

    disk()->put("assets/{$page->path}/header.png", 'binary');

    mock(SiteGenerator::class, function ($mock) {
        $mock->shouldReceive('generatePage')->once()->with('test-page');
        $mock->shouldReceive('removePageFile')->once()->with('test-page', 'header.png');
    });

    $deleteAsset = app(DeleteAsset::class);

    $deleteAsset((string) $page->path, 'header.png');

    expect(repository()->findByPath('test-page')->cover_image)->toBeNull();
});

test('does not regenerate the page when a non-cover asset is deleted', function () {
    $page = aPublishedPage(
        'Test Page',
        'test-page',
        content: '# Content',
        created_at: Carbon\Carbon::now(),
        published_at: Carbon\Carbon::now(),
        cover_image: 'header.png',
    );

    disk()->put("assets/{$page->path}/header.png", 'binary');
    disk()->put("assets/{$page->path}/other.txt", 'content');

    mock(SiteGenerator::class, function ($mock) {
        $mock->shouldNotReceive('generatePage');
        $mock->shouldReceive('removePageFile')->once()->with('test-page', 'other.txt');
    });

    $deleteAsset = app(DeleteAsset::class);

    $deleteAsset((string) $page->path, 'other.txt');
});

test('does not regenerate a draft page when its cover image is deleted', function () {
    $page = aPage(
        'Test Page',
        'test-page',
        content: '# Content',
        created_at: Carbon\Carbon::now(),
        cover_image: 'header.png',
    );

    disk()->put("assets/{$page->path}/header.png", 'binary');

    mock(SiteGenerator::class, function ($mock) {
        $mock->shouldNotReceive('generatePage');
    });

    $deleteAsset = app(DeleteAsset::class);

    $deleteAsset((string) $page->path, 'header.png');

    expect(repository()->findByPath('test-page')->cover_image)->toBeNull();
});

test('does nothing when page does not exist', function () {
    $deleteAsset = app(DeleteAsset::class);

    $deleteAsset('non-existent-page', 'file.txt');

    expect(true)->toBeTrue();
});
