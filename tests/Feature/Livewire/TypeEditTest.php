<?php

use Livewire\Livewire;

test('a type opens in the editor with its name', function () {
    $this->get(route('types.edit', 'post'))->assertSuccessful();

    Livewire::test('pages::type.edit', ['id' => 'post'])
        ->assertSet('name', 'Post');
});

test('a missing type returns a 404', function () {
    $this->get(route('types.edit', 'missing'))->assertNotFound();
});

test('renaming the type saves its name', function () {
    Livewire::test('pages::type.edit', ['id' => 'post'])
        ->set('name', 'Article')
        ->assertHasNoErrors();

    expect(types()->find('post')->name)->toBe('Article');
});

test('a name that is already taken shows an error', function () {
    Livewire::test('pages::type.edit', ['id' => 'post'])
        ->set('name', 'Page')
        ->assertHasErrors('name');

    expect(types()->find('post')->name)->toBe('Post');
});

test('deleting the type removes it from its pages and goes back to the list', function () {
    aPage('A Post', 'a-post', type: 'post');

    Livewire::test('pages::type.edit', ['id' => 'post'])
        ->call('delete')
        ->assertRedirect(route('types.index'));

    expect(types()->find('post'))->toBeNull()
        ->and(repository()->findByPath('a-post')->type)->toBeNull();
});
