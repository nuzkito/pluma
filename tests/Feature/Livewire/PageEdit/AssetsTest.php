<?php

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

test('edit page shows existing assets in list', function () {
    $page = aPage('Page With Assets', 'with-assets', content: '# Content');

    disk()->put("assets/{$page->path}/file1.txt", 'hello');
    disk()->put("assets/{$page->path}/image.png", 'binary');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->tap(function ($component) {
            expect($component->assets)->toHaveCount(2);
            expect(collect($component->assets)->pluck('filename')->all())->toEqualCanonicalizing(['file1.txt', 'image.png']);

            foreach ($component->assets as $asset) {
                expect($asset)->toHaveKeys(['filename', 'url']);
            }
        });
});

test('the assets section and the editor render as drag and drop zones', function () {
    $page = aPage('Drop Zone Page', 'drop-zone', content: '# Content');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->assertSeeHtml('id="asset-drop-zone"')
        ->assertSeeHtml('id="asset-drop-zone-overlay"')
        ->assertSeeHtml('group-data-dragging:flex')
        ->assertSeeHtml('Drop files here to upload')
        ->assertSeeHtml('id="content-drop-zone"')
        ->assertSeeHtml('group-data-over:outline-blue-500');
});

test('uploading a file adds it to assets list', function () {
    $page = aPage('Upload Test Page', 'upload-test', content: '# Content');

    $file = UploadedFile::fake()->createWithContent('test.txt', 'hello world');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->set('newAssets', [$file])
        ->assertSet('newAssets', []);

    expect("assets/{$page->path}/test.txt")->toExistOnDisk();
});

test('uploading multiple files adds all to assets list', function () {
    $page = aPage('Multi Upload Test', 'multi-upload', content: '# Content');

    $file1 = UploadedFile::fake()->createWithContent('doc1.pdf', 'content1');
    $file2 = UploadedFile::fake()->createWithContent('doc2.txt', 'content2');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->set('newAssets', [$file1, $file2])
        ->assertSet('newAssets', []);

    expect("assets/{$page->path}/doc1.pdf")->toExistOnDisk();
    expect("assets/{$page->path}/doc2.txt")->toExistOnDisk();
});

test('rejects files bigger than the upload limit', function () {
    $page = aPage('Big Upload Test', 'big-upload');

    $file = UploadedFile::fake()->create('huge.pdf', 12289);

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->set('newAssets', [$file])
        ->assertHasErrors(['newAssets.0']);

    expect("assets/{$page->path}/huge.pdf")->toBeMissingFromDisk();
});

test('deleting an asset removes it from storage and list', function () {
    $page = aPage('Delete Asset Test', 'delete-asset', content: '# Content');

    disk()->put("assets/{$page->path}/to-delete.txt", 'hello');
    disk()->put("assets/{$page->path}/keep.txt", 'world');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->call('deleteAsset', 'to-delete.txt')
        ->tap(function ($component) {
            expect(collect($component->assets)->pluck('filename')->all())->toEqualCanonicalizing(['keep.txt']);
        });

    expect("assets/{$page->path}/to-delete.txt")->toBeMissingFromDisk();
    expect("assets/{$page->path}/keep.txt")->toExistOnDisk();
});

test('deleting all assets results in empty list', function () {
    $page = aPage('Clear Assets Test', 'clear-assets', content: '# Content');

    disk()->put("assets/{$page->path}/file1.txt", 'hello');
    disk()->put("assets/{$page->path}/file2.png", 'binary');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->call('deleteAsset', 'file1.txt')
        ->call('deleteAsset', 'file2.png');

    expect(disk()->files("assets/{$page->path}"))->toBe([]);
});

test('deleting all assets removes empty assets directory', function () {
    $page = aPage('Empty Dir Cleanup Test', 'empty-dir-cleanup', content: '# Content');

    disk()->put("assets/{$page->path}/file1.txt", 'hello');
    disk()->put("assets/{$page->path}/file2.png", 'binary');

    expect("assets/{$page->path}")->toExistOnDisk();

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->call('deleteAsset', 'file1.txt')
        ->call('deleteAsset', 'file2.png');

    expect("assets/{$page->path}")->toBeMissingFromDisk();
});

test('deleting asset from published page removes it from site disk', function () {
    $page = aPublishedPage(
        'Published Delete Site Disk Test',
        'published-delete-site-disk',
        content: '# Content',
        published_at: Carbon::parse('2025-06-15 14:30:00'),
    );

    disk()->put("assets/{$page->path}/to-delete.txt", 'hello');
    disk()->put("site/{$page->path}/to-delete.txt", 'hello');

    expect("site/{$page->path}/to-delete.txt")->toExistOnDisk();

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->call('deleteAsset', 'to-delete.txt');

    expect("assets/{$page->path}/to-delete.txt")->toBeMissingFromDisk();
    expect("site/{$page->path}/to-delete.txt")->toBeMissingFromDisk();
});

test('deleting asset from draft page does not touch site disk', function () {
    $page = aPage('Draft Delete No Site Disk Test', 'draft-delete-no-site-disk', content: '# Content');

    disk()->put("assets/{$page->path}/to-delete.txt", 'hello');
    disk()->put("site/{$page->path}/to-delete.txt", 'should remain');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->call('deleteAsset', 'to-delete.txt');

    expect("assets/{$page->path}/to-delete.txt")->toBeMissingFromDisk();
    expect("site/{$page->path}/to-delete.txt")->toExistOnDisk();
});
