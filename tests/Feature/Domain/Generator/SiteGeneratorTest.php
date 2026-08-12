<?php

use App\Domain\Generator\Page\ContentPage;
use App\Domain\Generator\Page\Markdown;
use App\Domain\Generator\Page\PagePath;
use App\Domain\Generator\SiteGenerator;
use Carbon\Carbon;
use Illuminate\Support\Collection;

test('generates index without prior generatePage call', function () {
    $generator = app(SiteGenerator::class);

    $page = new ContentPage(
        title: 'My Page',
        path: new PagePath('my-page'),
        content: new Markdown('# Hello'),
        created_at: Carbon::now(),
        published_at: Carbon::now(),
    );

    $generator->generateIndex(new Collection([$page]));

    expect('site/index.html')->toExistOnDisk()
        ->and(disk()->get('site/index.html'))->toContain('My Page');
});

test('generates 404 without prior generatePage call', function () {
    $generator = app(SiteGenerator::class);

    $generator->generate404(new Collection);

    expect('site/404.html')->toExistOnDisk();
});

test('regenerates rss feed excluding pages with rss disabled', function () {
    config(['pluma.rss.enabled' => true]);

    $generator = app(SiteGenerator::class);

    disk()->put(
        'pages/rss-page.md',
        "---\ntitle: RSS Page\npath: rss-page\ncreated_at: '2025-01-01T10:00:00+00:00'\npublished_at: '2025-01-01T10:00:00+00:00'\nrss: true\n---\n\n# RSS Page"
    );

    disk()->put(
        'pages/no-rss-page.md',
        "---\ntitle: No RSS Page\npath: no-rss-page\ncreated_at: '2025-01-01T10:00:00+00:00'\npublished_at: '2025-01-01T10:00:00+00:00'\nrss: false\n---\n\n# No RSS"
    );

    $generator->regenerateIndex();

    expect('site/feed.xml')->toExistOnDisk()
        ->and(disk()->get('site/feed.xml'))->toContain('RSS Page')
        ->and(disk()->get('site/feed.xml'))->not->toContain('No RSS Page');
});

test('does not generate rss feed when rss is disabled', function () {
    config(['pluma.rss.enabled' => false]);

    $generator = app(SiteGenerator::class);

    disk()->put(
        'pages/rss-page.md',
        "---\ntitle: RSS Page\npath: rss-page\ncreated_at: '2025-01-01T10:00:00+00:00'\npublished_at: '2025-01-01T10:00:00+00:00'\nrss: true\n---\n\n# RSS Page"
    );

    $generator->regenerateIndex();

    expect('site/feed.xml')->toBeMissingFromDisk();
});

test('generates a tag page listing only the posts with that tag', function () {
    $generator = app(SiteGenerator::class);

    disk()->put(
        'pages/tagged-post.md',
        "---\ntitle: Tagged Post\npath: tagged-post\ncreated_at: '2025-01-01T10:00:00+00:00'\npublished_at: '2025-01-01T10:00:00+00:00'\ntags:\n    - Laravel\n---\n\n# Tagged"
    );

    disk()->put(
        'pages/other-post.md',
        "---\ntitle: Other Post\npath: other-post\ncreated_at: '2025-01-01T10:00:00+00:00'\npublished_at: '2025-01-01T10:00:00+00:00'\ntags:\n    - PHP\n---\n\n# Other"
    );

    disk()->put(
        'pages/tags/laravel.tag.md',
        "---\ntitle: Laravel\npath: tags/laravel\ncreated_at: '2025-01-01T10:00:00+00:00'\n---\n\nAll about Laravel"
    );

    $generator->generateAll();

    expect('site/tags/laravel/index.html')->toExistOnDisk()
        ->and(disk()->get('site/tags/laravel/index.html'))
        ->toContain('Laravel')
        ->toContain('All about Laravel')
        ->toContain('Tagged Post')
        ->not->toContain('Other Post');
});

test('does not render a description when the tag page has empty content', function () {
    $generator = app(SiteGenerator::class);

    disk()->put(
        'pages/tags/laravel.tag.md',
        "---\ntitle: Laravel\npath: tags/laravel\ncreated_at: '2025-01-01T10:00:00+00:00'\n---\n\n"
    );

    $generator->generateAll();

    expect('site/tags/laravel/index.html')->toExistOnDisk()
        ->and(disk()->get('site/tags/laravel/index.html'))->not->toContain('<div>');
});

test('copies the assets of a tag page next to its generated html', function () {
    $generator = app(SiteGenerator::class);

    disk()->put(
        'pages/tags/laravel.tag.md',
        "---\ntitle: Laravel\npath: tags/laravel\ncover_image: header.png\ncreated_at: '2025-01-01T10:00:00+00:00'\n---\n\nAll about Laravel"
    );

    disk()->put('assets/tags/laravel/header.png', 'binary');

    $generator->generateAll();

    expect('site/tags/laravel/header.png')->toExistOnDisk()
        ->and(disk()->get('site/tags/laravel/index.html'))->toContain('src="header.png"');
});

test('generatePage regenerates the tag page living at that path', function () {
    $generator = app(SiteGenerator::class);

    disk()->put(
        'pages/tags/laravel.tag.md',
        "---\ntitle: Laravel\npath: tags/laravel\ncreated_at: '2025-01-01T10:00:00+00:00'\n---\n\nAll about Laravel"
    );

    $generator->generatePage('tags/laravel');

    expect('site/tags/laravel/index.html')->toExistOnDisk()
        ->and(disk()->get('site/tags/laravel/index.html'))->toContain('All about Laravel');
});

test('removes a single file from a generated page', function () {
    $generator = app(SiteGenerator::class);

    disk()->put('site/my-page/index.html', '<html></html>');
    disk()->put('site/my-page/photo.png', 'binary');

    $generator->removePageFile('my-page', 'photo.png');

    expect('site/my-page/photo.png')->toBeMissingFromDisk()
        ->and('site/my-page/index.html')->toExistOnDisk();
});

test('deletes feed.xml when last rss page has rss disabled', function () {
    $generator = app(SiteGenerator::class);

    disk()->makeDirectory('site');
    disk()->put('site/feed.xml', '<rss>old feed</rss>');

    disk()->put(
        'pages/former-rss-page.md',
        "---\ntitle: Former RSS Page\npath: former-rss-page\ncreated_at: '2025-01-01T10:00:00+00:00'\npublished_at: '2025-01-01T10:00:00+00:00'\nrss: false\n---\n\n# Former RSS"
    );

    $generator->regenerateIndex();

    expect('site/feed.xml')->toBeMissingFromDisk();
});
