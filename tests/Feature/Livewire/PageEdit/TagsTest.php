<?php

use Livewire\Livewire;

test('adding first tag to draft page saves it correctly', function () {
    $page = aPage('Draft With Tags', 'draft-with-tags');

    expect($page->tags)->toBe([]);

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->call('addTag', 'first-tag')
        ->assertOk();

    $updated = repository()->findByPath('draft-with-tags');

    expect($updated->tags)->toBe(['first-tag']);
});

test('adding a tag creates its tag page file', function () {
    config()->set('pluma.tags.create_pages', true);

    $page = aPage('Page Creating Tag', 'page-creating-tag');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->call('addTag', 'Cosas varias')
        ->assertOk();

    expect('pages/tags/cosas-varias.tag.md')->toExistOnDisk();
});

test('adding a tag does not create its tag page file when the option is disabled', function () {
    config()->set('pluma.tags.create_pages', false);

    $page = aPage('Page Without Tag Page', 'page-without-tag-page');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->call('addTag', 'Cosas varias')
        ->assertOk();

    expect(repository()->findByPath('page-without-tag-page')->tags)->toBe(['Cosas varias'])
        ->and('pages/tags/cosas-varias.tag.md')->toBeMissingFromDisk();
});

test('adding a tag whose page already exists does not fail', function () {
    config()->set('pluma.tags.create_pages', true);

    $page = aPage('Page Reusing Tag', 'page-reusing-tag');

    disk()->put('pages/tags/laravel.tag.md', "---\ntitle: Laravel\npath: laravel\ncreated_at: '2025-01-01T10:00:00+00:00'\n---\n\nExisting description");

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->call('addTag', 'Laravel')
        ->assertOk();

    expect(disk()->get('pages/tags/laravel.tag.md'))->toContain('Existing description');
});

test('adding second tag to page with existing tag results in two tags', function () {
    $page = aPage('Page With One Tag', 'one-tag-page', content: '# Content', tags: ['existing-tag']);

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->call('addTag', 'new-tag')
        ->assertOk();

    $updated = repository()->findByPath('one-tag-page');

    expect($updated->tags)->toBe(['existing-tag', 'new-tag']);
});

test('adding two tags sequentially saves both correctly', function () {
    $page = aPage('Multi Tag Page', 'multi-tag-page');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->call('addTag', 'tag-one')
        ->assertOk()
        ->call('addTag', 'tag-two')
        ->assertOk();

    $updated = repository()->findByPath('multi-tag-page');

    expect($updated->tags)->toBe(['tag-one', 'tag-two']);
});

test('removing a tag removes it from the page', function () {
    $page = aPage(
        'Page To Remove Tag From',
        'remove-tag-page',
        content: '# Content',
        tags: ['keep-this', 'remove-this'],
    );

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->call('removeTag', 1)
        ->assertOk();

    $updated = repository()->findByPath('remove-tag-page');

    expect($updated->tags)->toBe(['keep-this'])
        ->and($updated->tags)->not->toContain('remove-this');
});

test('removing all tags results in empty array', function () {
    $page = aPage('Clear All Tags', 'clear-tags', content: '# Content', tags: ['tag1', 'tag2']);

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->call('removeTag', 0)
        ->assertOk()
        ->call('removeTag', 0)
        ->assertOk();

    $updated = repository()->findByPath('clear-tags');

    expect($updated->tags)->toBe([]);
});

test('duplicate tags are prevented by addTag', function () {
    $page = aPage('Duplicate Tags', 'duplicate-tags');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->call('addTag', 'php')
        ->assertOk()
        ->call('addTag', 'laravel')
        ->assertOk();

    expect(repository()->findByPath('duplicate-tags')->tags)->toBe(['php', 'laravel']);
});
