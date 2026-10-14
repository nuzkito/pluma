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

test('adding a tag creates its file and shows it by name', function () {
    $page = aPage('Page Creating Tag', 'page-creating-tag');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->call('addTag', 'Cosas varias')
        ->assertOk()
        ->assertSet('tags', ['cosas-varias'])
        ->assertSeeHtml('Cosas varias');

    expect('tags/cosas-varias.md')->toExistOnDisk();
});

test('adding a tag that already exists reuses it', function () {
    $page = aPage('Page Reusing Tag', 'page-reusing-tag');

    aTag('Laravel', content: 'Existing description');

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->call('addTag', 'Laravel')
        ->assertOk();

    expect(repository()->findByPath('page-reusing-tag')->tags)->toBe(['laravel'])
        ->and(disk()->get('tags/laravel.md'))->toContain('Existing description');
});

test('tags are shown by their current name', function () {
    aTag('Laravel framework', id: 'laravel');
    $page = aPage('Tagged Page', 'tagged-page', tags: ['laravel', 'Legacy Tag']);

    Livewire::test('pages::page.edit', ['path' => (string) $page->path])
        ->assertSeeHtml('Laravel framework')
        ->assertSeeHtml('Legacy Tag');
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
