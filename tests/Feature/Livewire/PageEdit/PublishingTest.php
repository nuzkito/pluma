<?php

use Carbon\Carbon;
use Livewire\Livewire;

test('publishing a draft page sets published_at and marks as published', function () {
    $page = aPage('Draft To Publish Via Button', 'draft-to-publish-via-button');

    expect($page->isDraft())->toBeTrue();

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->call('publish')
        ->tap(function ($component) {
            expect($component->published_at)->not->toBeNull();
        });

    $updated = repository()->findByPath('draft-to-publish-via-button');

    expect($updated)->not->toBeNull()
        ->and($updated->isPublished())->toBeTrue();
});

test('unpublishing a published page marks it as draft and keeps published_at', function () {
    $page = aPublishedPage(
        'Published To Unpublish Via Button',
        'published-to-unpublish-via-button',
        content: '# Content',
        published_at: Carbon::parse('2025-06-15 14:30:00'),
    );

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->call('unpublish')
        ->assertSet('published_at', '2025-06-15T14:30');

    $updated = repository()->findByPath('published-to-unpublish-via-button');

    expect($updated)->not->toBeNull()
        ->and($updated->isDraft())->toBeTrue()
        ->and($updated->published_at?->format('Y-m-d H:i'))->toBe('2025-06-15 14:30');
});
