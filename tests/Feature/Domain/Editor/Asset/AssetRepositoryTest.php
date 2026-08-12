<?php

use App\Domain\Editor\Asset\Asset;
use App\Domain\Editor\Asset\AssetRepository;
use App\Domain\Editor\Asset\NewAsset;
use Illuminate\Http\UploadedFile;

test('saves an asset and returns slugified filename', function () {
    $assets = new AssetRepository;
    $page = aPage('Test Page', 'test-page');

    $file = UploadedFile::fake()->create('My Document.pdf', 100);
    $asset = new NewAsset(pagePath: $page->path, name: 'my-document.pdf', file: $file);

    $assets->save($asset);

    expect("assets/{$page->path}/my-document.pdf")->toExistOnDisk();
});

test('saves an asset without extension', function () {
    $assets = new AssetRepository;
    $page = aPage('Test Page', 'test-page');

    $file = UploadedFile::fake()->createWithContent('Makefile', 'all:');
    $asset = new NewAsset(pagePath: $page->path, name: 'makefile', file: $file);

    $assets->save($asset);

    expect("assets/{$page->path}/makefile")->toExistOnDisk();
});

test('deletes an asset', function () {
    $assets = new AssetRepository;
    $page = aPage('Test Page', 'test-page');

    disk()->put("assets/{$page->path}/test.txt", 'hello');

    $result = $assets->delete(new Asset(pagePath: $page->path, name: 'test.txt'));

    expect($result)->toBeTrue()
        ->and("assets/{$page->path}/test.txt")->toBeMissingFromDisk();
});

test('returns false when deleting non-existent asset', function () {
    $assets = new AssetRepository;
    $page = aPage('Test Page', 'test-page');

    $result = $assets->delete(new Asset(pagePath: $page->path, name: 'non-existent.txt'));

    expect($result)->toBeFalse();
});

test('prunes the assets directory when it is empty', function () {
    $assets = new AssetRepository;
    $page = aPage('Test Page', 'test-page');

    disk()->makeDirectory("assets/{$page->path}");

    $assets->pruneEmptyDirectory($page->path);

    expect("assets/{$page->path}")->toBeMissingFromDisk();
});

test('does not prune the assets directory when it still has files', function () {
    $assets = new AssetRepository;
    $page = aPage('Test Page', 'test-page');

    disk()->put("assets/{$page->path}/file.txt", 'content');

    $assets->pruneEmptyDirectory($page->path);

    expect("assets/{$page->path}/file.txt")->toExistOnDisk();
});

test('checks if asset exists', function () {
    $assets = new AssetRepository;
    $page = aPage('Test Page', 'test-page');

    disk()->put("assets/{$page->path}/test.txt", 'hello');

    expect($assets->exists($page->path, 'test.txt'))->toBeTrue()
        ->and($assets->exists($page->path, 'missing.txt'))->toBeFalse();
});

test('returns absolute path for asset', function () {
    $assets = new AssetRepository;
    $page = aPage('Test Page', 'test-page');

    disk()->put("assets/{$page->path}/test.txt", 'hello');

    $path = $assets->path($page->path, 'test.txt');

    expect($path)->toContain("assets/{$page->path}/test.txt")
        ->and(file_exists($path))->toBeTrue();
});

test('returns all assets for a page', function () {
    $assets = new AssetRepository;
    $page = aPage('Test Page', 'test-page');

    disk()->put("assets/{$page->path}/file1.txt", 'a');
    disk()->put("assets/{$page->path}/file2.png", 'b');

    $all = $assets->all($page->path);

    expect($all)->toHaveCount(2)
        ->and(collect($all)->pluck('filename')->all())->toEqualCanonicalizing(['file1.txt', 'file2.png']);

    foreach ($all as $asset) {
        expect($asset)->toHaveKeys(['filename', 'url']);
    }
});

test('returns empty array when page has no assets', function () {
    $assets = new AssetRepository;
    $page = aPage('Test Page', 'test-page');

    expect($assets->all($page->path))->toBe([]);
});
