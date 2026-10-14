<?php

use App\Domain\Editor\Tag\CreateTag;
use Carbon\Carbon;

test('creates the file of the tag even when tag pages are disabled', function () {
    config()->set('pluma.tags.create_pages', false);

    $tag = app(CreateTag::class)('Cosas varias');

    expect($tag->id)->toBe('cosas-varias')
        ->and('tags/cosas-varias.md')->toExistOnDisk()
        ->and('pages/tags')->toBeMissingFromDisk()
        ->and('site/tags/cosas-varias')->toBeMissingFromDisk();
});

test('stores name, slug and created_at in the frontmatter with empty content', function () {
    app(CreateTag::class)('Laravel');

    $contents = disk()->get('tags/laravel.md');

    expect($contents)
        ->toContain('name: Laravel')
        ->toContain('slug: laravel')
        ->toContain('created_at:')
        ->and(trim(explode("---\n", $contents, 3)[2] ?? ''))->toBe('');
});

test('returns the existing tag with the same name instead of overwriting it', function () {
    $existing = aTag('Laravel', content: 'My description');

    $tag = app(CreateTag::class)('laravel');

    expect($tag->id)->toBe($existing->id)
        ->and(disk()->get('tags/laravel.md'))->toContain('My description');
});

test('gives a new id to a tag whose name generates the id of another one', function () {
    aTag('C', id: 'c');

    $tag = app(CreateTag::class)('C#');

    expect($tag->id)->toBe('c-2')
        ->and($tag->name)->toBe('C#');
});

test('generates the page of the tag with its posts when tag pages are enabled', function () {
    config()->set('pluma.tags.create_pages', true);

    aPublishedPage(
        'Tagged Post',
        'tagged-post',
        content: '# Tagged',
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
        tags: ['laravel'],
    );

    app(CreateTag::class)('Laravel');

    expect(disk()->get('site/tags/laravel/index.html'))
        ->toContain('Laravel')
        ->toContain('Tagged Post');
});

test('gives a new id to a tag whose name generates the slug of another one', function () {
    aTag('Laravel', slug: 'framework');

    $tag = app(CreateTag::class)('Framework');

    expect($tag->id)->toBe('framework-2')
        ->and($tag->slug)->toBe('framework-2');
});
