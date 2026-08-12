<?php

use Carbon\Carbon;
use Livewire\Livewire;

test('edit page shows current title in title field', function () {
    $page = aPage('My Original Title', 'my-original-title');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->assertSet('title', 'My Original Title');
});

test('editing title updates page title in repository', function () {
    $page = aPage('Original Title', 'original-title');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->set('title', 'Updated Title From Edit')
        ->assertSet('title', 'Updated Title From Edit');

    $updated = repository()->findByPath('updated-title-from-edit');

    expect($updated)->not->toBeNull()
        ->and($updated->title)->toBe('Updated Title From Edit')
        ->and((string) $updated->path)->toBe('updated-title-from-edit');
});

test('changing title auto-updates path when slug-based', function () {
    $page = aPage('My Original Title', 'my-original-title');

    expect((string) $page->path)->toBe('my-original-title');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->set('title', 'New Auto Slug Title')
        ->assertSet('title', 'New Auto Slug Title');

    $updated = repository()->findByPath('new-auto-slug-title');

    expect($updated)->not->toBeNull()
        ->and((string) $updated->path)->toBe('new-auto-slug-title')
        ->and($updated->title)->toBe('New Auto Slug Title');
});

test('changing title does not update path when custom path exists', function () {
    $page = aPage('Original Title', 'custom-path', content: '# Content');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->set('title', 'New Title With Custom Path')
        ->assertSet('title', 'New Title With Custom Path');

    $updated = repository()->findByPath('custom-path');

    expect($updated)->not->toBeNull()
        ->and((string) $updated->path)->toBe('custom-path')
        ->and($updated->title)->toBe('New Title With Custom Path');
});

test('changing title to same slug does not affect path', function () {
    $page = aPage('Same Slug Title', 'same-slug-title');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->set('title', 'Same Slug Title')
        ->assertSet('title', 'Same Slug Title');

    $updated = repository()->findByPath('same-slug-title');

    expect($updated)->not->toBeNull()
        ->and((string) $updated->path)->toBe('same-slug-title')
        ->and($updated->title)->toBe('Same Slug Title');
});

test('editing title preserves other page properties', function () {
    $page = aPublishedPage(
        'Original Title',
        'preserve-test',
        content: '# Original Content',
        published_at: Carbon::parse('2025-06-15 14:30:00'),
        rss: true,
        tags: ['php', 'testing'],
    );

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->set('title', 'New Title Preserves Other Fields')
        ->assertSet('title', 'New Title Preserves Other Fields');

    $updated = repository()->findByPath('preserve-test');

    expect($updated->title)->toBe('New Title Preserves Other Fields')
        ->and((string) $updated->path)->toBe('preserve-test')
        ->and((string) $updated->content)->toBe('# Original Content')
        ->and($updated->rss)->toBeTrue()
        ->and($updated->tags)->toBe(['php', 'testing'])
        ->and($updated->published_at?->format('Y-m-d H:i'))->toBe('2025-06-15 14:30');
});

test('title change dispatches url-changed event when path changes', function () {
    $page = aPage('Title That Changes Path', 'title-that-changes-path');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->set('title', 'Completely Different Title Now')
        ->assertSet('title', 'Completely Different Title Now')
        ->assertDispatched('url-changed');

    expect(repository()->findByPath('completely-different-title-now'))->not->toBeNull();
});

test('slug-based page title change that would conflict with another slug is rejected', function () {
    $pageA = aPage('Existing Title', 'existing-title');

    $pageB = aPage('Different Title', 'different-title', content: '# Content');

    Livewire::test('pages::page.edit', ['path' => 'different-title'])
        ->set('title', 'Existing Title')
        ->assertHasErrors('title');

    expect(repository()->findByPath('existing-title'))->not->toBeNull();
    $updated = repository()->findByPath('different-title');
    expect((string) $updated->path)->toBe('different-title');
});
