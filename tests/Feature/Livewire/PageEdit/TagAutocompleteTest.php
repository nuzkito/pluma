<?php

use Livewire\Livewire;

test('all tags from all pages are available in the component for autocompletion', function () {
    $page1 = aPage('Page One', 'page-one', content: '# Content 1');

    $page2 = aPage(
        'Page Two With Tags',
        'page-two-with-tags',
        content: '# Content 2',
        tags: ['livewire', 'php'],
    );

    Livewire::test('pages::page.edit', ['path' => (string) $page1->path])
        ->assertSeeHtml('<option value="livewire">livewire</option>')
        ->assertSeeHtml('<option value="php">php</option>');
});

test('tags from the edited page are excluded from the datalist options', function () {
    $page1 = aPage(
        'Page One With Tags',
        'page-one-with-tags',
        content: '# Content 1',
        tags: ['php', 'laravel'],
    );

    $page2 = aPage(
        'Page Two With Tags',
        'page-two-with-tags',
        content: '# Content 2',
        tags: ['livewire', 'php'],
    );

    Livewire::test('pages::page.edit', ['path' => (string) $page1->path])
        ->assertSeeHtml('<option value="livewire">livewire</option>')
        ->assertDontSeeHtml('<option value="php"')
        ->assertDontSeeHtml('<option value="laravel"');
});

test('editing a page with no tags shows all other pages tags in datalist', function () {
    $page1 = aPage('Page Without Tags', 'no-tags-page', content: '# Content');

    $page2 = aPage('Page With Tags', 'with-tags-page', content: '# Content', tags: ['php', 'laravel']);

    Livewire::test('pages::page.edit', ['path' => (string) $page1->path])
        ->assertSeeHtml('<option value="php">php</option>')
        ->assertSeeHtml('<option value="laravel">laravel</option>');
});

test('tag datalist options are sorted alphabetically by name', function () {
    $page1 = aPage('Page One', 'page-one', content: '# Content 1', tags: ['zebra', 'alpha']);

    $page2 = aPage('Page Two', 'page-two', content: '# Content 2', tags: ['beta', 'gamma']);

    Livewire::test('pages::page.edit', ['path' => (string) $page1->path])
        ->assertSeeHtmlInOrder(['alpha', 'beta', 'gamma', 'zebra']);
});
