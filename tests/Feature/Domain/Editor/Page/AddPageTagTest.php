<?php

use App\Domain\Editor\Page\AddPageTag;
use App\Domain\Editor\Page\Page;
use App\Domain\Generator\SiteGenerator;
use Carbon\Carbon;

use function Pest\Laravel\mock;

test('adds the id of the tag to the page, creating the tag', function () {
    aPage('Tag Test', 'tag-test', content: '# Content', tags: ['php']);

    $action = app(AddPageTag::class);

    $result = $action('tag-test', 'Cosas varias');

    expect($result)->toBeInstanceOf(Page::class)
        ->and(repository()->findByPath('tag-test')->tags)->toEqual(['php', 'cosas-varias'])
        ->and(tags()->find('cosas-varias')->name)->toBe('Cosas varias');
});

test('adds the id of the existing tag with that name', function () {
    aTag('Laravel', id: 'framework');
    aPage('Tag Test', 'tag-test');

    app(AddPageTag::class)('tag-test', 'laravel');

    expect(repository()->findByPath('tag-test')->tags)->toEqual(['framework'])
        ->and(tags()->all())->toHaveCount(1);
});

test('does not add duplicate tag', function () {
    aPage('Duplicate Tag Test', 'duplicate-tag-test', content: '# Content', tags: ['php']);

    $action = app(AddPageTag::class);

    $result = $action('duplicate-tag-test', 'php');

    expect($result)->toBeInstanceOf(Page::class)
        ->and(repository()->findByPath('duplicate-tag-test')->tags)->toEqual(['php']);
});

test('adds tag to published page and regenerates site', function () {
    aPublishedPage(
        'Published Tag Test',
        'published-tag-test',
        content: '# Content',
        published_at: Carbon::parse('2025-01-01 10:00:00'),
    );

    mock(SiteGenerator::class, function ($mock) {
        $mock->shouldReceive('generatePage')->once()->with('published-tag-test');
        $mock->shouldReceive('generateTagPage')->with('new-tag');
        $mock->shouldReceive('regenerateIndex')->once();
    });

    $action = app(AddPageTag::class);

    $result = $action('published-tag-test', 'new-tag');

    expect($result->tags)->toEqual(['new-tag']);
});

test('updates the static tag page when adding a tag to a published page', function () {
    config()->set('pluma.tags.create_pages', true);

    aTag('Laravel');

    aPublishedPage(
        'Published Post',
        'published-post',
        content: '# Content',
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
    );

    $action = app(AddPageTag::class);

    $action('published-post', 'laravel');

    expect(disk()->get('site/tags/laravel/index.html'))
        ->toContain('Published Post');
});
