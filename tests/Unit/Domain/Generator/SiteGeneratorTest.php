<?php

use App\Domain\Generator\Page\Markdown;
use App\Domain\Generator\Page\Page;
use App\Domain\Generator\Page\PagePath;
use App\Domain\Generator\Page\PageRepository;
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
    chdir(Storage::disk('current')->path('/'));

    $generator = app(SiteGenerator::class);
    $repository = app(PageRepository::class);

    $pageWithRss = new Page(
        title: 'RSS Page',
        path: new PagePath('rss-page'),
        content: new Markdown('# RSS Page'),
        created_at: Carbon::now(),
        published_at: Carbon::now(),
        rss: true,
    );
    $repository->save($pageWithRss);

    $pageWithoutRss = new Page(
        title: 'No RSS Page',
        path: new PagePath('no-rss-page'),
        content: new Markdown('# No RSS'),
        created_at: Carbon::now(),
        published_at: Carbon::now(),
        rss: false,
    );
    $repository->save($pageWithoutRss);

    $generator->regenerateIndex();

    $disk = Storage::disk('current');

    expect($disk->exists('site/feed.xml'))->toBeTrue()
        ->and($disk->get('site/feed.xml'))->toContain('RSS Page')
        ->and($disk->get('site/feed.xml'))->not->toContain('No RSS Page');
});

test('generates a tag page listing only the posts with that tag', function () {
    initializeSite();
    chdir(Storage::disk('current')->path('/'));

    $generator = app(SiteGenerator::class);
    $repository = app(PageRepository::class);

    $repository->save(new Page(
        title: 'Tagged Post',
        path: new PagePath('tagged-post'),
        content: new Markdown('# Tagged'),
        created_at: Carbon::now(),
        published_at: Carbon::now(),
        tags: ['Laravel'],
    ));

    $repository->save(new Page(
        title: 'Other Post',
        path: new PagePath('other-post'),
        content: new Markdown('# Other'),
        created_at: Carbon::now(),
        published_at: Carbon::now(),
        tags: ['PHP'],
    ));

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
    $repository = app(PageRepository::class);

    $disk = Storage::disk('current');
    $disk->makeDirectory('site');
    $disk->put('site/feed.xml', '<rss>old feed</rss>');

    $page = new Page(
        title: 'Former RSS Page',
        path: new PagePath('former-rss-page'),
        content: new Markdown('# Former RSS'),
        created_at: Carbon::now(),
        published_at: Carbon::now(),
        rss: false,
    );
    $repository->save($page);

    $generator->regenerateIndex();

    expect($disk->exists('site/feed.xml'))->toBeFalse();
});
