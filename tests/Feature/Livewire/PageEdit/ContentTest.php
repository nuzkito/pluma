<?php

use Livewire\Livewire;

test('edit page shows current content in textarea', function () {
    $page = aPage('Page With Content', 'with-content', content: '# Hello World');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->assertSet('content', '# Hello World');
});

test('updating content saves it to the page', function () {
    $page = aPage('Content Update Test', 'content-update', content: '# Old Content');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->set('content', '# New Content')
        ->assertSet('content', '# New Content');

    $updated = repository()->findByPath('content-update');

    expect($updated)->not->toBeNull()
        ->and((string) $updated->content)->toBe('# New Content');
});

test('setting empty content clears the page', function () {
    $page = aPage('Clear Content', 'clear-content', content: '# Has Content');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->set('content', '')
        ->assertSet('content', '');

    $updated = repository()->findByPath('clear-content');

    expect($updated)->not->toBeNull()
        ->and((string) $updated->content)->toBe('');
});
