<?php

use Livewire\Livewire;

test('edit page loads the name of the existing type', function () {
    $page = aPage('Typed Page', 'typed-page', type: 'post');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->assertSet('type', 'Post');
});

test('changing the type saves its id on the page and shows its name', function () {
    $page = aPage('Change Type Page', 'change-type-page');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->set('type', 'page')
        ->assertHasNoErrors()
        ->assertSet('type', 'Page');

    expect(repository()->findByPath('change-type-page')->type)->toBe('page');
});

test('rejects a type longer than 255 characters', function () {
    $page = aPage('Long Type Page', 'long-type-page');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->set('type', str_repeat('a', 256))
        ->assertHasErrors(['type' => 'max']);

    expect(repository()->findByPath('long-type-page')->type)->toBeNull();
});

test('every type is offered sorted by name', function () {
    aType('Article');

    $page = aPage('Edited Page', 'edited-page');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->assertSeeHtmlInOrder([
            '<option value="Article">Article</option>',
            '<option value="Page">Page</option>',
            '<option value="Post">Post</option>',
        ]);
});
