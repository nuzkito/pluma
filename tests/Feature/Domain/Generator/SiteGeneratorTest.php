<?php

use App\Domain\Generator\Page\Markdown;
use App\Domain\Generator\Page\Page;
use App\Domain\Generator\Page\PagePath;
use App\Domain\Generator\SiteGenerator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

test('generates index without prior generatePage call', function () {
    $generator = app(SiteGenerator::class);

    $page = new Page(
        title: 'My Page',
        path: new PagePath('my-page'),
        content: new Markdown('# Hello'),
        created_at: Carbon::now(),
        published_at: Carbon::now(),
        draft: false,
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
    config()->set('pluma.tags.create_pages', true);

    aPublishedPage('Tagged Post', 'tagged-post', tags: ['laravel']);
    aPublishedPage('Other Post', 'other-post', tags: ['php']);
    aTag('Laravel', content: 'All about Laravel');
    aTag('PHP');

    app(SiteGenerator::class)->generateAll();

    expect(disk()->get('site/tags/laravel/index.html'))
        ->toContain('Laravel')
        ->toContain('All about Laravel')
        ->toContain('Tagged Post')
        ->not->toContain('Other Post');
});

test('generates the tag pages at their slug under the tag pages path', function () {
    config()->set('pluma.tags.create_pages', true);
    config()->set('pluma.tags.pages_path', 'etiquetas');

    aTag('Laravel', slug: 'framework');

    app(SiteGenerator::class)->generateAll();

    expect('site/etiquetas/framework/index.html')->toExistOnDisk();
});

test('does not generate tag pages when they are disabled', function () {
    config()->set('pluma.tags.create_pages', false);

    aTag('Laravel');

    app(SiteGenerator::class)->generateAll();
    app(SiteGenerator::class)->generateTagPage('laravel');

    expect('site/tags')->toBeMissingFromDisk();
});

test('does not render a description when the tag has empty content', function () {
    config()->set('pluma.tags.create_pages', true);

    aTag('Laravel');

    app(SiteGenerator::class)->generateAll();

    expect(disk()->get('site/tags/laravel/index.html'))->not->toContain('<div>');
});

test('copies the assets of a tag, stored under its id, next to its generated html', function () {
    config()->set('pluma.tags.create_pages', true);

    aTag('Laravel', slug: 'framework', cover_image: 'header.png');

    disk()->put('assets/tags/laravel/header.png', 'binary');

    app(SiteGenerator::class)->generateAll();

    expect('site/tags/framework/header.png')->toExistOnDisk()
        ->and(disk()->get('site/tags/framework/index.html'))->toContain('src="header.png"');
});

test('generateTagPage regenerates the page of the tag with that id', function () {
    config()->set('pluma.tags.create_pages', true);

    aTag('Laravel', content: 'All about Laravel');

    app(SiteGenerator::class)->generateTagPage('laravel');

    expect(disk()->get('site/tags/laravel/index.html'))->toContain('All about Laravel');
});

test('removeTagPages removes the generated page of every tag', function () {
    config()->set('pluma.tags.create_pages', true);

    aTag('Laravel');
    aPublishedPage('Kept', 'kept');

    app(SiteGenerator::class)->generateAll();
    app(SiteGenerator::class)->removeTagPages();

    expect('site/tags/laravel')->toBeMissingFromDisk()
        ->and('site/kept/index.html')->toExistOnDisk();
});

test('sends the web object to every template', function () {
    config([
        'pluma.rss.enabled' => true,
        'pluma.url' => 'https://example.com',
        'pluma.title' => 'My Site',
        'pluma.description' => 'A site about things',
    ]);

    disk()->put(
        'pages/tagged-post.md',
        "---\ntitle: Tagged Post\npath: tagged-post\ncreated_at: '2025-01-01T10:00:00+00:00'\npublished_at: '2025-01-01T10:00:00+00:00'\nrss: true\ntags:\n    - laravel\n---\n\n# Tagged"
    );

    config()->set('pluma.tags.create_pages', true);
    aTag('Laravel');

    app(SiteGenerator::class)->generateAll();

    expect(disk()->get('site/index.html'))
        ->toContain('<h1>My Site</h1>')
        ->toContain('<p>A site about things</p>')
        ->toContain('https://example.com/styles.css')
        ->and(disk()->get('site/404.html'))->toContain('https://example.com/styles.css')
        ->and(disk()->get('site/tagged-post/index.html'))->toContain('https://example.com/styles.css')
        ->and(disk()->get('site/tags/laravel/index.html'))->toContain('https://example.com/styles.css')
        ->and(disk()->get('site/feed.xml'))
        ->toContain('<title>My Site</title>')
        ->toContain('<link>https://example.com</link>')
        ->toContain('<description>A site about things</description>');
});

test('sends the published pages to the page template', function () {
    $pagesSentToTheTemplate = null;

    view()->composer('page', function (View $view) use (&$pagesSentToTheTemplate) {
        $pagesSentToTheTemplate = $view->getData()['pages'] ?? null;
    });

    aPublishedPage(title: 'First Post', path: 'first-post');
    aPublishedPage(title: 'Second Post', path: 'second-post');
    aPage(title: 'A Draft', path: 'a-draft');

    app(SiteGenerator::class)->generateAll();

    expect($pagesSentToTheTemplate)->toBeInstanceOf(Collection::class)
        ->and($pagesSentToTheTemplate->pluck('title')->all())
        ->toEqualCanonicalizing(['First Post', 'Second Post']);
});

test('generatePage sends the published pages to the page template', function () {
    $pagesSentToTheTemplate = null;

    view()->composer('page', function (View $view) use (&$pagesSentToTheTemplate) {
        $pagesSentToTheTemplate = $view->getData()['pages'] ?? null;
    });

    aPublishedPage(title: 'First Post', path: 'first-post');
    aPublishedPage(title: 'Second Post', path: 'second-post');

    app(SiteGenerator::class)->generatePage('first-post');

    expect($pagesSentToTheTemplate)->toBeInstanceOf(Collection::class)
        ->and($pagesSentToTheTemplate->pluck('title')->all())
        ->toEqualCanonicalizing(['First Post', 'Second Post']);
});

test('shows the revision date of a revised page', function () {
    aPublishedPage(title: 'Revised Post', path: 'revised-post', revised_at: Carbon::parse('2025-07-01 09:00:00'));
    aPublishedPage(title: 'Unrevised Post', path: 'unrevised-post');

    app(SiteGenerator::class)->generateAll();

    expect(disk()->get('site/revised-post/index.html'))->toContain('Updated on <time datetime="2025-07-01">2025-07-01</time>')
        ->and(disk()->get('site/unrevised-post/index.html'))->not->toContain('Updated on');
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
