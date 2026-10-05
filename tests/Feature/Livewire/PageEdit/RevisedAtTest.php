<?php

use Carbon\Carbon;
use Livewire\Livewire;

test('shows the revised_at of a page', function () {
    $page = aPublishedPage('Revised Page', 'revised-page', revised_at: Carbon::parse('2025-07-01 09:00:00'));

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->assertSet('revised_at', '2025-07-01T09:00');
});

test('changing revised_at updates the page', function () {
    $page = aPublishedPage('Change Revised Date', 'change-revised-date');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->set('revised_at', '2025-07-01T09:00')
        ->assertSet('revised_at', '2025-07-01T09:00');

    expect(repository()->findByPath('change-revised-date')->revised_at?->format('Y-m-d H:i'))->toBe('2025-07-01 09:00');
});

test('clearing revised_at removes it from the page', function () {
    $page = aPublishedPage('Clear Revised Date', 'clear-revised-date', revised_at: Carbon::parse('2025-07-01 09:00:00'));

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->set('revised_at', '')
        ->assertSet('revised_at', null);

    expect(repository()->findByPath('clear-revised-date')->revised_at)->toBeNull();
});

test('editing the content of a published page refreshes revised_at', function () {
    Carbon::setTestNow(Carbon::parse('2025-07-01 09:00:00'));

    $page = aPublishedPage('Edit Revised Content', 'edit-revised-content', content: '# Old', published_at: Carbon::parse('2025-06-01 12:00:00'));

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->set('content', '# New')
        ->assertSet('revised_at', '2025-07-01T09:00');
});

test('hides revised_at on a draft', function () {
    $page = aPage('Draft Page', 'draft-page');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->assertDontSee('Revised at');
});

test('changing revised_at updates a tag page', function () {
    $tagPage = aTagPage('Laravel');

    Livewire::test('pages::page.edit', ['path' => (string) $tagPage->path])
        ->set('revised_at', '2025-07-01T09:00')
        ->assertSet('revised_at', '2025-07-01T09:00');

    expect(repository()->findByPath((string) $tagPage->path)->revised_at?->format('Y-m-d H:i'))->toBe('2025-07-01 09:00');
});
