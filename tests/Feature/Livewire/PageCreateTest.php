<?php

use Livewire\Livewire;

describe('create draft', function () {
    test('creates a draft page with auto-generated name', function () {
        Livewire::test('create-draft')
            ->call('create');

        expect(repository()->all())->toHaveCount(1);
        expect(repository()->all()->first()->title)->toBe('Draft');
    });

    test('draft has correct properties', function () {
        Livewire::test('create-draft')
            ->call('create');

        $page = repository()->all()->first();

        expect($page->title)->toBe('Draft')
            ->and((string) $page->path)->toBe('draft')
            ->and((string) $page->content)->toBe('')
            ->and($page->rss)->toBeTrue()
            ->and($page->tags)->toBe([]);
    });

    test('redirects to edit page after creation', function () {
        Livewire::test('create-draft')
            ->call('create')
            ->assertRedirectToRoute('pages.edit', 'draft');
    });

    test('increments draft numbering when multiple drafts exist', function () {
        Livewire::test('create-draft')
            ->call('create');

        expect(repository()->all())->toHaveCount(1);
        expect(repository()->all()->first()->title)->toBe('Draft');

        Livewire::test('create-draft')
            ->call('create');

        $drafts = repository()->all();
        expect(collect($drafts)->pluck('title')->all())->toEqualCanonicalizing(['Draft', 'Draft 2']);
    });

    test('creates the draft inside the given directory', function () {
        disk()->makeDirectory('pages/posts');

        Livewire::test('create-draft', ['directory' => 'posts'])
            ->call('create')
            ->assertRedirectToRoute('pages.edit', 'posts/draft');

        $page = repository()->searchByDirectory('posts')->first();

        expect((string) $page->path)->toBe('posts/draft')
            ->and(repository()->all())->toBeEmpty();
    });

    test('numbers drafts independently per directory', function () {
        disk()->makeDirectory('pages/posts');

        Livewire::test('create-draft')->call('create');
        Livewire::test('create-draft', ['directory' => 'posts'])->call('create');

        expect((string) repository()->all()->first()->path)->toBe('draft')
            ->and((string) repository()->searchByDirectory('posts')->first()->path)->toBe('posts/draft');
    });
});
