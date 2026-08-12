<?php

use App\Domain\Editor\Page\CreateTagPage;
use Carbon\Carbon;

test('creates a tag page file with the slug-based name', function () {
    config()->set('pluma.tags.create_pages', true);

    $action = app(CreateTagPage::class);

    $action('Cosas varias');

    expect('pages/tags/cosas-varias.tag.md')->toExistOnDisk();
});

test('does not create a tag page when the option is disabled', function () {
    config()->set('pluma.tags.create_pages', false);

    $action = app(CreateTagPage::class);

    $action('Cosas varias');

    expect('pages/tags/cosas-varias.tag.md')->toBeMissingFromDisk();
});

test('stores title, path and created_at in the frontmatter with empty content', function () {
    config()->set('pluma.tags.create_pages', true);

    $action = app(CreateTagPage::class);

    $action('Laravel');

    $contents = disk()->get('pages/tags/laravel.tag.md');

    expect($contents)
        ->toContain('title: Laravel')
        ->toContain('path: tags/laravel')
        ->toContain('created_at:')
        ->not->toContain('rss:')
        ->not->toContain('tags:')
        ->and(trim(explode("---\n", $contents, 3)[2] ?? ''))->toBe('');
});

test('does not overwrite an existing tag page', function () {
    config()->set('pluma.tags.create_pages', true);

    disk()->put('pages/tags/laravel.tag.md', "---\ntitle: Laravel\npath: laravel\ncreated_at: '2025-01-01T10:00:00+00:00'\n---\n\nMy description");

    $action = app(CreateTagPage::class);

    $action('Laravel');

    expect(disk()->get('pages/tags/laravel.tag.md'))->toContain('My description');
});

test('generates the tag page in the static site with its posts', function () {
    config()->set('pluma.tags.create_pages', true);

    aPublishedPage(
        'Tagged Post',
        'tagged-post',
        content: '# Tagged',
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
        tags: ['Laravel'],
    );

    app(CreateTagPage::class)('Laravel');

    expect('site/tags/laravel/index.html')->toExistOnDisk()
        ->and(disk()->get('site/tags/laravel/index.html'))
        ->toContain('Laravel')
        ->toContain('Tagged Post');
});

test('does not show tag pages in the regular page listing', function () {
    config()->set('pluma.tags.create_pages', true);

    $action = app(CreateTagPage::class);

    $action('Cosas varias');

    expect(repository()->all())->toBeEmpty();
});
