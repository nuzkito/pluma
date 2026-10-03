<?php

use Carbon\Carbon;
use Livewire\Livewire;

test('setting published_at on a draft keeps it as a draft', function () {
    $page = aPage('Draft With Date', 'draft-with-date');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->set('published_at', '2025-06-15T14:30')
        ->assertSet('published_at', '2025-06-15T14:30');

    $updated = repository()->findByPath('draft-with-date');

    expect($updated->isDraft())->toBeTrue()
        ->and($updated->published_at?->format('Y-m-d H:i'))->toBe('2025-06-15 14:30');
});

test('clearing published_at on a published page restores its date', function () {
    $page = aPublishedPage(
        'Published Clear Date',
        'published-clear-date',
        content: '# Content',
        published_at: Carbon::parse('2025-06-15 14:30:00'),
    );

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->set('published_at', '')
        ->assertSet('published_at', '2025-06-15T14:30');

    expect(repository()->findByPath('published-clear-date')->isPublished())->toBeTrue();
});

test('changing published_at on a published page updates to the new date', function () {
    $page = aPublishedPage(
        'Update Published Date',
        'update-published-date',
        content: '# Content',
        published_at: Carbon::parse('2025-01-01 10:00:00'),
    );

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->set('published_at', '2025-08-20T16:45')
        ->assertSet('published_at', '2025-08-20T16:45');

    $updated = repository()->findByPath('update-published-date');

    expect($updated)->not->toBeNull()
        ->and($updated->isPublished())->toBeTrue()
        ->and($updated->published_at?->format('Y-m-d H:i'))->toBe('2025-08-20 16:45');
});

test('clearing published_at on a draft removes its date', function () {
    $page = aPage('Draft Clear Date', 'draft-clear-date', published_at: Carbon::parse('2025-06-15 14:30:00'));

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->set('published_at', '')
        ->assertSet('published_at', null);

    expect(repository()->findByPath('draft-clear-date')->published_at)->toBeNull();
});
