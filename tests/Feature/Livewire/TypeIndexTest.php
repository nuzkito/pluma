<?php

use Livewire\Livewire;

test('lists every type with the number of pages that have it, sorted by name', function () {
    aType('Article');
    aPage('First Post', 'first-post', type: 'post');
    aPage('Second Post', 'nested/second-post', type: 'post');
    aPage('Untyped', 'untyped');

    $this->get(route('types.index'))->assertSuccessful();

    Livewire::test('pages::type.index')
        ->assertSeeInOrder(['Article', '0', 'Page', '0', 'Post', '2'])
        ->assertSee(route('types.edit', 'post'));
});

test('shows an empty state when there are no types', function () {
    disk()->deleteDirectory('types');

    Livewire::test('pages::type.index')
        ->assertSee('There are no types yet.');
});
