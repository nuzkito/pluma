<?php

use Livewire\Livewire;

test('the names of every tag are available for autocompletion', function () {
    aTag('Livewire');
    aTag('PHP');

    $page = aPage('Page One', 'page-one', content: '# Content 1');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->assertSeeHtml('<option value="Livewire">Livewire</option>')
        ->assertSeeHtml('<option value="PHP">PHP</option>');
});

test('tags from the edited page are excluded from the datalist options', function () {
    aTag('Livewire');
    aTag('PHP');
    aTag('Laravel');

    $page = aPage('Page One With Tags', 'page-one-with-tags', tags: ['php', 'laravel']);

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->assertSeeHtml('<option value="Livewire">Livewire</option>')
        ->assertDontSeeHtml('<option value="PHP"')
        ->assertDontSeeHtml('<option value="Laravel"');
});

test('tag datalist options are sorted alphabetically by name', function () {
    aTag('zebra');
    aTag('Alpha');
    aTag('gamma');
    aTag('Beta');

    $page = aPage('Page One', 'page-one');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->assertSeeHtmlInOrder(['Alpha', 'Beta', 'gamma', 'zebra']);
});
