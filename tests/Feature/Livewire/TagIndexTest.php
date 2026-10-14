<?php

use Livewire\Livewire;

test('lists every tag with its slug, linking to its edit screen', function () {
    aTag('Laravel', slug: 'framework');
    aTag('PHP');

    $this->get(route('tags.index'))->assertSuccessful();

    Livewire::test('pages::tag.index')
        ->assertSeeInOrder(['Laravel', 'framework', 'PHP'])
        ->assertSeeHtml('href="'.route('tags.edit', 'laravel').'"');
});

test('shows an empty state when there are no tags', function () {
    Livewire::test('pages::tag.index')
        ->assertSee('There are no tags yet.');
});

test('warns that the site has no tag pages while they are disabled', function () {
    config()->set('pluma.tags.create_pages', false);

    Livewire::test('pages::tag.index')
        ->assertSee('Tag pages are disabled');

    config()->set('pluma.tags.create_pages', true);

    Livewire::test('pages::tag.index')
        ->assertDontSee('Tag pages are disabled');
});
