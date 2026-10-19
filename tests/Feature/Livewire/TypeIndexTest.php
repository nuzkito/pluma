<?php

use Livewire\Livewire;

test('lists every type with the number of pages that have it, sorted alphabetically', function () {
    aPage('First Note', 'first-note', type: 'note');
    aPage('Second Note', 'nested/second-note', type: 'note');
    aPage('An Article', 'an-article', type: 'Article');
    aPage('Untyped', 'untyped');

    $this->get(route('types.index'))->assertSuccessful();

    Livewire::test('pages::type.index')
        ->assertSeeInOrder(['Article', '1', 'note', '2']);
});

test('shows an empty state when no page has a type', function () {
    aPage('Untyped', 'untyped');

    Livewire::test('pages::type.index')
        ->assertSee('There are no types yet.');
});

test('deleting a type removes it from its pages and from the list', function () {
    aPage('First Note', 'first-note', type: 'note');
    aPage('An Article', 'an-article', type: 'article');

    Livewire::test('pages::type.index')
        ->call('delete', 'note')
        ->assertDontSee('note')
        ->assertSee('article');

    expect(repository()->findByPath('first-note')->type)->toBeNull()
        ->and(repository()->findByPath('an-article')->type)->toBe('article');
});
