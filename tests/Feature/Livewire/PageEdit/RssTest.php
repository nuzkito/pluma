<?php

use Livewire\Livewire;

test('checking unchecked rss checkbox sets page.rss to true', function () {
    $page = aPage('RSS Disabled Page', 'rss-disabled', content: '# Content');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->set('rss', true)
        ->assertSet('rss', true);

    $updated = repository()->findByPath('rss-disabled');

    expect($updated->rss)->toBeTrue();
});

test('unchecking checked rss checkbox sets page.rss to false', function () {
    $page = aPublishedPage('RSS Enabled Page', 'rss-enabled', content: '# Content', rss: true);

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->set('rss', false)
        ->assertSet('rss', false);

    $updated = repository()->findByPath('rss-enabled');

    expect($updated->rss)->toBeFalse();
});

test('shows the rss checkbox when rss is enabled', function () {
    config(['pluma.rss.enabled' => true]);

    $page = aPage('Page', 'a-page', content: '# Content');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->assertSee('Include in RSS feed');
});

test('hides the rss checkbox when rss is disabled', function () {
    config(['pluma.rss.enabled' => false]);

    $page = aPage('Page', 'a-page', content: '# Content');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->assertDontSee('Include in RSS feed');
});
