<?php

use Carbon\Carbon;
use Livewire\Livewire;

test('setting published_at date publishes an unpublished page', function () {
    $page = aPage('Draft To Publish', 'draft-to-publish');

    expect($page->isDraft())->toBeTrue();

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->set('published_at', '2025-06-15T14:30')
        ->assertSet('published_at', '2025-06-15T14:30');

    $updated = repository()->findByPath('draft-to-publish');

    expect($updated)->not->toBeNull()
        ->and($updated->isPublished())->toBeTrue()
        ->and($updated->published_at?->format('Y-m-d H:i'))->toBe('2025-06-15 14:30');
});

test('clearing published_at unpublishes a page', function () {
    $page = aPublishedPage(
        'Published To Unpublish',
        'published-to-unpublish',
        content: '# Content',
        published_at: Carbon::parse('2025-06-15 14:30:00'),
    );

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->set('published_at', null)
        ->assertSet('published_at', null);

    $updated = repository()->findByPath('published-to-unpublish');

    expect($updated)->not->toBeNull()
        ->and($updated->isDraft())->toBeTrue()
        ->and($updated->published_at)->toBeNull();
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
