<?php

use App\Domain\Editor\Page\TagPage;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

use function Pest\Laravel\artisan;

beforeEach(fn () => initializeSite());

test('generates the static site', function () {
    aPublishedPage(
        'Hello World',
        'hello-world',
        content: '# Hello World',
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
        rss: true,
    );

    aPage('Draft Page', 'draft-page', content: 'This is a draft', created_at: Carbon::parse('2025-02-01'));

    artisan('pluma:generate')
        ->expectsOutputToContain('Generating static site')
        ->assertSuccessful();

    expect('site/index.html')->toExistOnDisk()
        ->and('site/404.html')->toExistOnDisk()
        ->and('site/hello-world/index.html')->toExistOnDisk()
        ->and('site/draft-page')->toBeMissingFromDisk();
});

test('generates RSS feed when pages have rss enabled', function () {
    config(['pluma.rss.enabled' => true]);

    aPublishedPage(
        'Hello World',
        'hello-world',
        content: '# Hello World',
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
        rss: true,
    );

    aPage('Draft Page', 'draft-page', content: 'This is a draft', created_at: Carbon::parse('2025-02-01'));

    artisan('pluma:generate')
        ->assertSuccessful();

    expect('site/feed.xml')->toExistOnDisk();

    $rss = disk()->get('site/feed.xml');

    expect($rss)->toContain('<title>Hello World</title>')
        ->and($rss)->not->toContain('Draft Page');
});

test('copies resource files to site directory', function () {
    artisan('pluma:generate')
        ->assertSuccessful();

    expect('site/styles.css')->toExistOnDisk()
        ->and('site/scripts.js')->toExistOnDisk();
});

test('generates tag pages with their posts', function () {
    aPublishedPage(
        'Hello World',
        'hello-world',
        content: '# Hello World',
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
        tags: ['Laravel'],
    );

    repository()->save(TagPage::create('Laravel'));

    artisan('pluma:generate')
        ->assertSuccessful();

    expect('site/tags/laravel/index.html')->toExistOnDisk()
        ->and(disk()->get('site/tags/laravel/index.html'))
        ->toContain('Laravel')
        ->toContain('Hello World');
});

test('links page tags to their tag page when create_tag_pages is enabled', function () {
    config()->set('pluma.tags.create_pages', true);

    aPublishedPage(
        'Hello World',
        'hello-world',
        content: '# Hello World',
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
        tags: ['Laravel'],
    );

    artisan('pluma:generate')
        ->assertSuccessful();

    $html = disk()->get('site/hello-world/index.html');

    expect($html)->toContain('href="http://localhost:8001/tags/laravel/"');
});

test('does not link page tags when create_tag_pages is disabled', function () {
    config()->set('pluma.tags.create_pages', false);

    aPublishedPage(
        'Hello World',
        'hello-world',
        content: '# Hello World',
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
        tags: ['Laravel'],
    );

    artisan('pluma:generate')
        ->assertSuccessful();

    $html = disk()->get('site/hello-world/index.html');

    expect($html)->toContain('Laravel')
        ->not->toContain('/tags/laravel/');
});

test('optimizes image assets and copies the rest of the assets when generating', function () {
    aPublishedPage(
        'Hello World',
        'hello-world',
        content: '# Hello World',
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
    );

    $photo = UploadedFile::fake()->image('photo.jpg', 3000, 2000)->getContent();
    disk()->put('assets/hello-world/photo.jpg', $photo);
    disk()->put('assets/hello-world/notes.txt', 'text content');

    artisan('pluma:generate')
        ->assertSuccessful();

    expect('site/hello-world/photo.jpg')->toExistOnDisk()
        ->and(disk()->size('site/hello-world/photo.jpg'))->toBeLessThan(disk()->size('assets/hello-world/photo.jpg'))
        ->and(disk()->get('site/hello-world/notes.txt'))->toBe('text content');
});

test('renders the cover image above the title when the page has one', function () {
    aPublishedPage(
        'Hello World',
        'hello-world',
        content: '# Hello World',
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
        cover_image: 'header image.png',
    );

    artisan('pluma:generate')
        ->assertSuccessful();

    $html = disk()->get('site/hello-world/index.html');

    expect($html)->toContain('<img src="header%20image.png" alt="header image.png">')
        ->and(strpos($html, '<img src="header%20image.png"'))->toBeLessThan(strpos($html, '<h1>Hello World</h1>'));
});

test('does not render a cover image when the page has none', function () {
    aPublishedPage(
        'Hello World',
        'hello-world',
        content: 'Some content',
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
    );

    artisan('pluma:generate')
        ->assertSuccessful();

    $html = disk()->get('site/hello-world/index.html');

    expect($html)->not->toContain('<img');
});

test('optimizes the site cover image and renders it above the title in the index', function () {
    config(['pluma.title' => 'My Site', 'pluma.cover_image' => 'site cover.jpg']);

    disk()->put('assets/site cover.jpg', UploadedFile::fake()->image('site cover.jpg', 3000, 2000)->getContent());

    artisan('pluma:generate')
        ->assertSuccessful();

    $html = disk()->get('site/index.html');

    expect('site/site cover.jpg')->toExistOnDisk()
        ->and(disk()->size('site/site cover.jpg'))->toBeLessThan(disk()->size('assets/site cover.jpg'))
        ->and($html)->toContain('<img src="site%20cover.jpg" alt="My Site">')
        ->and(strpos($html, '<img src="site%20cover.jpg"'))->toBeLessThan(strpos($html, '<h1>My Site</h1>'));
});

test('does not render a site cover image when none is configured', function () {
    config(['pluma.title' => 'My Site', 'pluma.cover_image' => '']);

    artisan('pluma:generate')
        ->assertSuccessful();

    expect(disk()->get('site/index.html'))->not->toContain('<img');
});

test('ignores a site cover image whose file is missing', function () {
    config(['pluma.cover_image' => 'missing.png']);

    artisan('pluma:generate')
        ->assertSuccessful();

    expect('site/missing.png')->toBeMissingFromDisk();
});

test('renders markdown content as html', function () {
    aPublishedPage(
        'Hello World',
        'hello-world',
        content: '# Hello World',
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
    );

    artisan('pluma:generate')
        ->assertSuccessful();

    $html = disk()->get('site/hello-world/index.html');

    expect($html)->toContain('<h1>Hello World</h1>');
});
