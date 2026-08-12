<?php

use Carbon\Carbon;
use Livewire\Livewire;

test('deleting a page removes it from the repository', function () {
    $page = aPage('Page To Delete Via Button', 'delete-via-button', content: '# Content');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->call('delete')
        ->assertRedirectToRoute('pages.index', ['directory' => '']);

    expect(repository()->findByPath('delete-via-button'))->toBeNull();
});

test('deleting a published page removes it and regenerates index', function () {
    $page = aPublishedPage(
        'Published Delete Test',
        'published-delete',
        content: '# Content',
        published_at: Carbon::parse('2025-06-15 14:30:00'),
    );

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->call('delete')
        ->assertRedirectToRoute('pages.index', ['directory' => '']);

    expect(repository()->findByPath('published-delete'))->toBeNull();
});

test('deleting a page redirects to its directory', function () {
    $page = aPage('Nested Page To Delete', 'posts/2025/pagina1', content: '# Content');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->call('delete')
        ->assertRedirectToRoute('pages.index', ['directory' => 'posts/2025']);

    expect(repository()->findByPath('posts/2025/pagina1'))->toBeNull();
});
