<?php

use App\Domain\Generator\Page\Markdown;
use App\Domain\Generator\Page\Page;
use App\Domain\Generator\Page\PagePath;
use App\Domain\Generator\SiteGenerator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

afterEach(function () {
    chdir(base_path());
});

test('generates index without prior generatePage call', function () {
    $repository = initializeSite();
    chdir(Storage::disk('current')->path('/'));

    $generator = app(SiteGenerator::class);

    $page = new Page(
        title: 'My Page',
        path: new PagePath('my-page'),
        content: new Markdown('# Hello'),
        created_at: Carbon::now(),
        published_at: Carbon::now(),
    );

    $generator->generateIndex(new Collection([$page]));

    $disk = Storage::disk('current');

    expect($disk->exists('site/index.html'))->toBeTrue()
        ->and($disk->get('site/index.html'))->toContain('My Page');
});

test('generates 404 without prior generatePage call', function () {
    initializeSite();
    chdir(Storage::disk('current')->path('/'));

    $generator = app(SiteGenerator::class);

    $generator->generate404(new Collection);

    expect(Storage::disk('current')->exists('site/404.html'))->toBeTrue();
});

test('regenerates rss feed excluding pages with rss disabled', function () {
    initializeSite();
    config(['pluma.enable_rss' => true]);
    chdir(Storage::disk('current')->path('/'));

    $generator = app(SiteGenerator::class);

    Storage::disk('current')->put(
        'pages/rss-page.md',
        "---\ntitle: RSS Page\npath: rss-page\ncreated_at: '2025-01-01T10:00:00+00:00'\npublished_at: '2025-01-01T10:00:00+00:00'\nrss: true\n---\n\n# RSS Page"
    );

    Storage::disk('current')->put(
        'pages/no-rss-page.md',
        "---\ntitle: No RSS Page\npath: no-rss-page\ncreated_at: '2025-01-01T10:00:00+00:00'\npublished_at: '2025-01-01T10:00:00+00:00'\nrss: false\n---\n\n# No RSS"
    );

    $generator->regenerateIndex();

    $disk = Storage::disk('current');

    expect($disk->exists('site/feed.xml'))->toBeTrue()
        ->and($disk->get('site/feed.xml'))->toContain('RSS Page')
        ->and($disk->get('site/feed.xml'))->not->toContain('No RSS Page');
});

test('does not generate rss feed when rss is disabled', function () {
    initializeSite();
    config(['pluma.enable_rss' => false]);
    chdir(Storage::disk('current')->path('/'));

    $generator = app(SiteGenerator::class);

    Storage::disk('current')->put(
        'pages/rss-page.md',
        "---\ntitle: RSS Page\npath: rss-page\ncreated_at: '2025-01-01T10:00:00+00:00'\npublished_at: '2025-01-01T10:00:00+00:00'\nrss: true\n---\n\n# RSS Page"
    );

    $generator->regenerateIndex();

    expect(Storage::disk('current')->exists('site/feed.xml'))->toBeFalse();
});

test('generates a tag page listing only the posts with that tag', function () {
    initializeSite();
    chdir(Storage::disk('current')->path('/'));

    $generator = app(SiteGenerator::class);

    Storage::disk('current')->put(
        'pages/tagged-post.md',
        "---\ntitle: Tagged Post\npath: tagged-post\ncreated_at: '2025-01-01T10:00:00+00:00'\npublished_at: '2025-01-01T10:00:00+00:00'\ntags:\n    - Laravel\n---\n\n# Tagged"
    );

    Storage::disk('current')->put(
        'pages/other-post.md',
        "---\ntitle: Other Post\npath: other-post\ncreated_at: '2025-01-01T10:00:00+00:00'\npublished_at: '2025-01-01T10:00:00+00:00'\ntags:\n    - PHP\n---\n\n# Other"
    );

    Storage::disk('current')->put(
        'pages/tags/laravel.tag.md',
        "---\ntitle: Laravel\npath: tags/laravel\ncreated_at: '2025-01-01T10:00:00+00:00'\n---\n\nAll about Laravel"
    );

    $generator->generateAll();

    $disk = Storage::disk('current');

    expect($disk->exists('site/tags/laravel/index.html'))->toBeTrue()
        ->and($disk->get('site/tags/laravel/index.html'))
        ->toContain('Laravel')
        ->toContain('All about Laravel')
        ->toContain('Tagged Post')
        ->not->toContain('Other Post');
});

test('does not render a description when the tag page has empty content', function () {
    initializeSite();
    chdir(Storage::disk('current')->path('/'));

    $generator = app(SiteGenerator::class);

    Storage::disk('current')->put(
        'pages/tags/laravel.tag.md',
        "---\ntitle: Laravel\npath: tags/laravel\ncreated_at: '2025-01-01T10:00:00+00:00'\n---\n\n"
    );

    $generator->generateAll();

    $disk = Storage::disk('current');

    expect($disk->exists('site/tags/laravel/index.html'))->toBeTrue()
        ->and($disk->get('site/tags/laravel/index.html'))->not->toContain('<div>');
});

test('deletes feed.xml when last rss page has rss disabled', function () {
    initializeSite();
    chdir(Storage::disk('current')->path('/'));

    $generator = app(SiteGenerator::class);

    $disk = Storage::disk('current');
    $disk->makeDirectory('site');
    $disk->put('site/feed.xml', '<rss>old feed</rss>');

    $disk->put(
        'pages/former-rss-page.md',
        "---\ntitle: Former RSS Page\npath: former-rss-page\ncreated_at: '2025-01-01T10:00:00+00:00'\npublished_at: '2025-01-01T10:00:00+00:00'\nrss: false\n---\n\n# Former RSS"
    );

    $generator->regenerateIndex();

    expect($disk->exists('site/feed.xml'))->toBeFalse();
});
