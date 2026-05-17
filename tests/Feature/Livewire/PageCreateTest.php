<?php

use Carbon\Carbon;
use Livewire\Livewire;

describe('create draft', function () {
    test('creates a draft page with auto-generated name', function () {
        $repository = initializeSite();

        Livewire::test('create-draft')
            ->call('create');

        expect($repository->all())->toHaveCount(1);
        expect($repository->all()->first()->title)->toBe('Draft');
    });

    test('draft has correct properties', function () {
        Carbon::setTestNow(Carbon::parse('2025-06-01 12:00:00'));

        $repository = initializeSite();

        Livewire::test('create-draft')
            ->call('create');

        $page = $repository->all()->first();

        expect($page->title)->toBe('Draft')
            ->and((string) $page->path)->toBe('draft')
            ->and((string) $page->content)->toBe('')
            ->and($page->rss)->toBeTrue()
            ->and($page->tags)->toBe([]);

        Carbon::setTestNow(null);
    });

    test('redirects to edit page after creation', function () {
        Carbon::setTestNow(Carbon::parse('2025-06-01 12:00:00'));

        $repository = initializeSite();

        Livewire::test('create-draft')
            ->call('create')
            ->assertRedirectToRoute('pages.edit', 'draft');

        Carbon::setTestNow(null);
    });

    test('increments draft numbering when multiple drafts exist', function () {
        Carbon::setTestNow(Carbon::parse('2025-06-01 12:00:00'));

        $repository = initializeSite();

        Livewire::test('create-draft')
            ->call('create');

        expect($repository->all())->toHaveCount(1);
        expect($repository->all()->first()->title)->toBe('Draft');

        Livewire::test('create-draft')
            ->call('create');

        $drafts = $repository->all();
        expect(collect($drafts)->pluck('title')->all())->toEqualCanonicalizing(['Draft', 'Draft 2']);

        Carbon::setTestNow(null);
    });
});
