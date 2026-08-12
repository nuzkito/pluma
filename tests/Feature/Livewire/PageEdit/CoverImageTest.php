<?php

use Livewire\Livewire;

test('cover image is null by default', function () {
    $page = aPage('No Cover Page', 'no-cover-page');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->assertSet('cover_image', null);
});

test('edit page loads the existing cover image', function () {
    $page = aPage('Page With Cover', 'page-with-cover', content: '# Content', cover_image: 'header.png');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->assertSet('cover_image', 'header.png');
});

test('setting an image as cover saves it on the page', function () {
    $page = aPage('Set Cover Page', 'set-cover-page');

    disk()->put("assets/{$page->path}/header.png", 'binary');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->call('setCoverImage', 'header.png')
        ->assertSet('cover_image', 'header.png');

    expect(repository()->findByPath('set-cover-page')->cover_image)->toBe('header.png');
});

test('shows the add as cover image button for images that are not the cover', function () {
    $page = aPage('Cover Button Page', 'cover-button-page');

    disk()->put("assets/{$page->path}/photo.png", 'binary');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->assertSee('Add as cover image');
});

test('does not show the add as cover image button for non-image assets', function () {
    $page = aPage('No Image Cover Page', 'no-image-cover-page');

    disk()->put("assets/{$page->path}/document.txt", 'content');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->assertDontSee('Add as cover image');
});

test('hides the add as cover image button for the image already set as cover', function () {
    $page = aPage('Already Cover Page', 'already-cover-page', content: '# Content', cover_image: 'header.png');

    disk()->put("assets/{$page->path}/header.png", 'binary');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->assertDontSee('Add as cover image');
});

test('deleting the cover image asset clears the cover image', function () {
    $page = aPage('Delete Cover Page', 'delete-cover-page', content: '# Content', cover_image: 'header.png');

    disk()->put("assets/{$page->path}/header.png", 'binary');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->call('deleteAsset', 'header.png')
        ->assertSet('cover_image', null);

    expect(repository()->findByPath('delete-cover-page')->cover_image)->toBeNull();
});

test('deleting a non-cover asset keeps the cover image', function () {
    $page = aPage('Keep Cover Page', 'keep-cover-page', content: '# Content', cover_image: 'header.png');

    disk()->put("assets/{$page->path}/header.png", 'binary');
    disk()->put("assets/{$page->path}/other.txt", 'content');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->call('deleteAsset', 'other.txt')
        ->assertSet('cover_image', 'header.png');

    expect(repository()->findByPath('keep-cover-page')->cover_image)->toBe('header.png');
});
