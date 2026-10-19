<?php

use Livewire\Livewire;

test('edit page loads the existing type', function () {
    $page = aPage('Typed Page', 'typed-page', type: 'article');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->assertSet('type', 'article');
});

test('changing the type saves it on the page', function () {
    $page = aPage('Change Type Page', 'change-type-page');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->set('type', 'article')
        ->assertHasNoErrors();

    expect(repository()->findByPath('change-type-page')->type)->toBe('article');
});

test('rejects a type longer than 255 characters', function () {
    $page = aPage('Long Type Page', 'long-type-page');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->set('type', str_repeat('a', 256))
        ->assertHasErrors(['type' => 'max']);

    expect(repository()->findByPath('long-type-page')->type)->toBeNull();
});

test('the types used by other pages are offered once each, sorted alphabetically', function () {
    aPage('Note One', 'note-one', type: 'note');
    aPage('Note Two', 'nested/note-two', type: 'note');
    aPage('Article', 'article', type: 'Article');
    aPage('Untyped', 'untyped');

    $page = aPage('Edited Page', 'edited-page');

    $component = Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->assertSeeHtmlInOrder(['<option value="Article">Article</option>', '<option value="note">note</option>']);

    expect(substr_count($component->html(), '<option value="note">'))->toBe(1);
});

test('the type of the edited page is not offered when no other page uses it', function () {
    $page = aPage('Only Recipe', 'only-recipe', type: 'recipe');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->assertDontSeeHtml('<option value="recipe">');
});
