<?php

use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\RemovePageTag;
use App\Domain\Generator\SiteGenerator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\mock;

test('removes tag by index', function () {
    aPage('Remove Tag Test', 'remove-tag-test', content: '# Content', tags: ['php', 'laravel', 'testing']);

    $action = app(RemovePageTag::class);

    $result = $action('remove-tag-test', 1);

    expect($result)->toBeInstanceOf(ContentPage::class)
        ->and(repository()->findByPath('remove-tag-test')->tags)->toEqual(['php', 'testing']);
});

test('removes first tag when index is 0', function () {
    aPage('First Tag Test', 'first-tag-test', content: '# Content', tags: ['php', 'laravel']);

    $action = app(RemovePageTag::class);

    $result = $action('first-tag-test', 0);

    expect(repository()->findByPath('first-tag-test')->tags)->toEqual(['laravel']);
});

test('removes last tag when index is last', function () {
    aPage('Last Tag Test', 'last-tag-test', content: '# Content', tags: ['php', 'laravel']);

    $action = app(RemovePageTag::class);

    $result = $action('last-tag-test', 1);

    expect(repository()->findByPath('last-tag-test')->tags)->toEqual(['php']);
});

test('removes tag from published page and regenerates site', function () {
    aPublishedPage(
        'Published Remove Tag Test',
        'published-remove-tag-test',
        content: '# Content',
        published_at: Carbon::parse('2025-01-01 10:00:00'),
        tags: ['php', 'laravel'],
    );

    mock(SiteGenerator::class, function ($mock) {
        $mock->shouldReceive('generatePage')->twice();
        $mock->shouldReceive('regenerateIndex')->once();
    });

    $action = app(RemovePageTag::class);

    $result = $action('published-remove-tag-test', 0);

    expect($result->tags)->toEqual(['laravel']);
});

test('updates the static tag page when removing a tag from a published page', function () {
    config()->set('pluma.tags.create_pages', true);

    Storage::disk('current')->put(
        'pages/tags/laravel.tag.md',
        "---\ntitle: laravel\npath: tags/laravel\ncreated_at: '2025-01-01T10:00:00+00:00'\n---\n\n"
    );

    aPublishedPage(
        'Published Post',
        'published-post',
        content: '# Content',
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
        tags: ['laravel'],
    );

    $generator = app(SiteGenerator::class);
    $generator->generatePage('tags/laravel');

    expect(Storage::disk('current')->get('site/tags/laravel/index.html'))->toContain('Published Post');

    $action = app(RemovePageTag::class);

    $action('published-post', 0);

    expect(Storage::disk('current')->get('site/tags/laravel/index.html'))->not->toContain('Published Post');
});
