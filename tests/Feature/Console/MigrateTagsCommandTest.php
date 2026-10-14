<?php

use function Pest\Laravel\artisan;

beforeEach(fn () => initializeSite());

test('replaces the names of the tags in the pages with the ids of the tags it creates', function () {
    aPage('Root Page', 'root-page', tags: ['First Tag', 'PHP']);
    aPage('Nested Page', 'posts/nested-page', tags: ['First Tag']);

    artisan('pluma:migrate-tags')
        ->expectsOutputToContain('Converted 0 tag page(s), migrated 2 page(s) and created 2 tag(s).')
        ->assertSuccessful();

    expect(repository()->findByPath('root-page')->tags)->toBe(['first-tag', 'php'])
        ->and(repository()->findByPath('posts/nested-page')->tags)->toBe(['first-tag'])
        ->and(tags()->find('first-tag')->name)->toBe('First Tag')
        ->and(tags()->find('php')->name)->toBe('PHP');
});

test('reuses the existing tag with that name and keeps the references that already are ids', function () {
    aTag('Laravel', id: 'framework');
    aTag('PHP');

    aPage('Tagged Page', 'tagged-page', tags: ['php', 'Laravel', 'framework']);

    artisan('pluma:migrate-tags')->assertSuccessful();

    expect(repository()->findByPath('tagged-page')->tags)->toBe(['php', 'framework'])
        ->and(tags()->all())->toHaveCount(2);
});

test('keeps the rest of the page untouched', function () {
    $page = aPublishedPage('Published Page', 'published-page', content: '# Content', rss: true, tags: ['First Tag']);

    artisan('pluma:migrate-tags')->assertSuccessful();

    $migrated = repository()->findByPath('published-page');

    expect((string) $migrated->content)->toBe('# Content')
        ->and($migrated->isPublished())->toBeTrue()
        ->and($migrated->rss)->toBeTrue()
        ->and($migrated->published_at->toIso8601String())->toBe($page->published_at->toIso8601String())
        ->and($migrated->revised_at)->toBeNull();
});

test('regenerates the site so the pages link to the pages of their tags', function () {
    config()->set('pluma.tags.create_pages', true);

    aPublishedPage('Published Page', 'published-page', tags: ['First Tag']);

    artisan('pluma:migrate-tags')->assertSuccessful();

    expect('site/tags/first-tag/index.html')->toExistOnDisk()
        ->and(disk()->get('site/published-page/index.html'))->toContain('/tags/first-tag/">First Tag</a>');
});

test('converts the tag pages stored among the pages into tag files, with their assets', function () {
    disk()->put(
        'pages/etiquetas/first-tag.tag.md',
        "---\ntitle: 'First Tag'\npath: etiquetas/first-tag\ncover_image: header.png\ncreated_at: '2025-01-01T10:00:00+00:00'\n---\n\nAll about the first tag"
    );
    disk()->put('assets/etiquetas/first-tag/header.png', 'binary');
    aPage('Tagged Page', 'tagged-page', tags: ['First Tag']);

    config()->set('pluma.tags.pages_path', 'etiquetas');

    artisan('pluma:migrate-tags')
        ->expectsOutputToContain('Converted 1 tag page(s), migrated 1 page(s) and created 0 tag(s).')
        ->assertSuccessful();

    $tag = tags()->find('first-tag');

    expect($tag->name)->toBe('First Tag')
        ->and((string) $tag->content)->toBe('All about the first tag')
        ->and($tag->cover_image)->toBe('header.png')
        ->and($tag->created_at->toIso8601String())->toBe('2025-01-01T10:00:00+00:00')
        ->and(repository()->findByPath('tagged-page')->tags)->toBe(['first-tag'])
        ->and('assets/tags/first-tag/header.png')->toExistOnDisk()
        ->and('assets/etiquetas/first-tag')->toBeMissingFromDisk()
        ->and('pages/etiquetas')->toBeMissingFromDisk();
});

test('a tag page fills in the fields that an existing tag with the same id leaves empty', function () {
    aTag('First Tag', slug: 'custom');
    disk()->put(
        'pages/tags/first-tag.tag.md',
        "---\ntitle: 'Old name'\npath: tags/first-tag\ncover_image: header.png\ncreated_at: '2025-01-01T10:00:00+00:00'\n---\n\nAll about the first tag"
    );

    artisan('pluma:migrate-tags')->assertSuccessful();

    $tag = tags()->find('first-tag');

    expect($tag->name)->toBe('First Tag')
        ->and($tag->slug)->toBe('custom')
        ->and((string) $tag->content)->toBe('All about the first tag')
        ->and($tag->cover_image)->toBe('header.png')
        ->and('pages/tags/first-tag.tag.md')->toBeMissingFromDisk();
});

test('copies the views the site lacks from the template, so the tag pages can be generated', function () {
    config()->set('pluma.tags.create_pages', true);

    disk()->delete('views/tag.blade.php');
    disk()->put('views/page.blade.php', 'My own page view');
    aPage('Tagged Page', 'tagged-page', tags: ['First Tag']);

    artisan('pluma:migrate-tags')
        ->expectsOutputToContain('Copied the missing view views/tag.blade.php from the template.')
        ->assertSuccessful();

    expect(disk()->get('views/tag.blade.php'))->toBe(file_get_contents(base_path('template/views/tag.blade.php')))
        ->and(disk()->get('views/page.blade.php'))->toBe('My own page view')
        ->and('site/tags/first-tag/index.html')->toExistOnDisk();
});

test('copies the missing views even when the tags are already migrated', function () {
    disk()->delete('views/tag.blade.php');

    artisan('pluma:migrate-tags')
        ->expectsOutputToContain('Copied the missing view views/tag.blade.php from the template.')
        ->assertSuccessful();

    expect('views/tag.blade.php')->toExistOnDisk();
});

test('does nothing when every page already references its tags by id', function () {
    aTag('PHP');
    aPage('Tagged Page', 'tagged-page', tags: ['php']);

    artisan('pluma:migrate-tags')
        ->expectsOutputToContain('Nothing to migrate.')
        ->assertSuccessful();

    expect('site/index.html')->toBeMissingFromDisk();
});
