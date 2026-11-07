<?php

use App\Domain\Generator\SiteGenerator;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

/**
 * Decode the JSON-LD schemas of a generated document, keyed by their type.
 *
 * @return array<string, array<string, mixed>>
 */
function jsonLdSchemas(string $html): array
{
    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

    return collect($matches[1])
        ->map(fn (string $json): array => json_decode($json, true, flags: JSON_THROW_ON_ERROR))
        ->keyBy('@type')
        ->all();
}

beforeEach(function () {
    config([
        'pluma.url' => 'http://example.com',
        'pluma.title' => 'My Site',
        'pluma.description' => 'A site about things',
        'pluma.cover_image' => 'site cover.png',
    ]);
});

test('renders the title, description and urls of a page in its head', function () {
    aPublishedPage('My Post', 'blog/my-post', content: "# Fish & chips\n\nA *tasty* dish.");

    app(SiteGenerator::class)->generateAll();

    expect(disk()->get('site/blog/my-post/index.html'))
        ->toContain('<title>My Post</title>')
        ->toContain('<meta name="description" content="Fish &amp; chips A tasty dish.">')
        ->toContain('<link rel="canonical" href="http://example.com/blog/my-post/">')
        ->toContain('<meta property="og:url" content="http://example.com/blog/my-post/">')
        ->toContain('<meta property="og:type" content="article">')
        ->toContain('<meta property="og:site_name" content="My Site">');
});

test('shortens the content of a page to describe it', function () {
    aPublishedPage('Long Post', 'long-post', content: str_repeat('word ', 100));

    app(SiteGenerator::class)->generateAll();

    expect(disk()->get('site/long-post/index.html'))
        ->toContain('<meta name="description" content="'.str_repeat('word ', 31).'word...">');
});

test('falls back to the site description when the page has no content', function () {
    aPublishedPage('Empty Post', 'empty-post');

    app(SiteGenerator::class)->generateAll();

    expect(disk()->get('site/empty-post/index.html'))
        ->toContain('<meta name="description" content="A site about things">');
});

test('uses the cover image of the page as its open graph image', function () {
    aPublishedPage('My Post', 'my-post', cover_image: 'my cover.png');

    app(SiteGenerator::class)->generateAll();

    expect(disk()->get('site/my-post/index.html'))
        ->toContain('<meta property="og:image" content="http://example.com/my-post/my%20cover.png">')
        ->toContain('<meta name="twitter:card" content="summary_large_image">')
        ->not->toContain('site%20cover.png');
});

test('falls back to the cover image of the site when the page has none', function () {
    aPublishedPage('My Post', 'my-post');

    app(SiteGenerator::class)->generateAll();

    expect(disk()->get('site/my-post/index.html'))
        ->toContain('<meta property="og:image" content="http://example.com/site%20cover.png">');
});

test('renders a summary card without an image when neither the page nor the site have a cover image', function () {
    config(['pluma.cover_image' => '']);
    aPublishedPage('My Post', 'my-post');

    app(SiteGenerator::class)->generateAll();

    expect(disk()->get('site/my-post/index.html'))
        ->toContain('<meta name="twitter:card" content="summary">')
        ->not->toContain('og:image');
});

test('renders the publication and revision dates of a page', function () {
    aPublishedPage(
        'My Post',
        'my-post',
        published_at: Carbon::parse('2025-01-01 10:00:00'),
        revised_at: Carbon::parse('2025-07-01 09:00:00'),
    );

    app(SiteGenerator::class)->generateAll();

    expect(disk()->get('site/my-post/index.html'))
        ->toContain('<meta property="article:published_time" content="2025-01-01T10:00:00+00:00">')
        ->toContain('<meta property="article:modified_time" content="2025-07-01T09:00:00+00:00">');
});

test('does not carry the head of a page over to the next generated page', function () {
    aPublishedPage('First Post', 'first-post', revised_at: Carbon::parse('2025-07-01 09:00:00'), cover_image: 'first.png');
    aPublishedPage('Second Post', 'second-post');
    $generator = app(SiteGenerator::class);
    $generator->generatePage('first-post');

    $generator->generatePage('second-post');

    expect(disk()->get('site/second-post/index.html'))
        ->not->toContain('First Post')
        ->not->toContain('first.png')
        ->not->toContain('article:modified_time');
});

test('renders the data of the site in the head of the index', function () {
    app(SiteGenerator::class)->generateAll();

    expect(disk()->get('site/index.html'))
        ->toContain('<title>My Site</title>')
        ->toContain('<meta name="description" content="A site about things">')
        ->toContain('<link rel="canonical" href="http://example.com/">')
        ->toContain('<meta property="og:type" content="website">')
        ->toContain('<meta property="og:image" content="http://example.com/site%20cover.png">');
});

test('renders the data of the tag in the head of its page', function () {
    config(['pluma.tags.create_pages' => true]);
    aTag('Laravel', slug: 'laravel-framework', content: 'All about Laravel', cover_image: 'laravel.png');
    disk()->put('assets/tags/laravel/laravel.png', UploadedFile::fake()->image('laravel.png', 800, 600)->getContent());

    app(SiteGenerator::class)->generateAll();

    expect(disk()->get('site/tags/laravel-framework/index.html'))
        ->toContain('<title>Laravel</title>')
        ->toContain('<meta name="description" content="All about Laravel">')
        ->toContain('<link rel="canonical" href="http://example.com/tags/laravel-framework/">')
        ->toContain('<meta property="og:image" content="http://example.com/tags/laravel-framework/laravel.png">')
        ->toContain('<meta property="og:image:width" content="800">');
});

test('falls back to the description and cover image of the site for a tag without them', function () {
    config(['pluma.tags.create_pages' => true]);
    aTag('Laravel');

    app(SiteGenerator::class)->generateAll();

    expect(disk()->get('site/tags/laravel/index.html'))
        ->toContain('<meta name="description" content="A site about things">')
        ->toContain('<meta property="og:image" content="http://example.com/site%20cover.png">');
});

test('hides the not found page from search engines', function () {
    app(SiteGenerator::class)->generateAll();

    expect(disk()->get('site/404.html'))
        ->toContain('<title>Page not found</title>')
        ->toContain('<meta name="robots" content="noindex">')
        ->not->toContain('rel="canonical"');
});

test('links the rss feed when the site publishes one', function () {
    config(['pluma.rss.enabled' => true]);
    aPublishedPage('My Post', 'my-post', rss: true);

    app(SiteGenerator::class)->generateAll();

    expect(disk()->get('site/index.html'))
        ->toContain('<link rel="alternate" type="application/rss+xml" title="My Site" href="http://example.com/feed.xml">');
});

test('does not link the rss feed when no page is in it', function () {
    config(['pluma.rss.enabled' => true]);
    aPublishedPage('My Post', 'my-post', rss: false);

    app(SiteGenerator::class)->generateAll();

    expect(disk()->get('site/index.html'))->not->toContain('application/rss+xml');
});

test('lets the templates change the head tags', function () {
    disk()->put('views/page.blade.php', "@extends('layout')\n@php(Head::title('Custom title'))\n@section('content')@endsection");
    // Blade reuses a compiled view that is not older than its source, and earlier tests compile this same path.
    touch(disk()->path('views/page.blade.php'), time() + 60);
    aPublishedPage('My Post', 'my-post');

    app(SiteGenerator::class)->generateAll();

    expect(disk()->get('site/my-post/index.html'))->toContain('<title>Custom title</title>');
});

test('renders the size and type of the open graph image as the site serves it', function () {
    config(['pluma.cover_image' => 'cover.jpg']);
    disk()->put('assets/cover.jpg', UploadedFile::fake()->image('cover.jpg', 3000, 2000)->getContent());

    app(SiteGenerator::class)->generateAll();

    expect(disk()->get('site/index.html'))
        ->toContain('<meta property="og:image:type" content="image/jpeg">')
        ->toContain('<meta property="og:image:width" content="1600">')
        ->toContain('<meta property="og:image:height" content="1067">');
});

test('renders the size of the cover image of a page', function () {
    aPublishedPage('My Post', 'my-post', cover_image: 'cover.png');
    disk()->put('assets/my-post/cover.png', UploadedFile::fake()->image('cover.png', 800, 600)->getContent());

    app(SiteGenerator::class)->generateAll();

    expect(disk()->get('site/my-post/index.html'))
        ->toContain('<meta property="og:image:type" content="image/png">')
        ->toContain('<meta property="og:image:width" content="800">')
        ->toContain('<meta property="og:image:height" content="600">');
});

test('renders only the type of an open graph image without a known size', function () {
    config(['pluma.cover_image' => 'cover.svg']);
    disk()->put('assets/cover.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"></svg>');

    app(SiteGenerator::class)->generateAll();

    expect(disk()->get('site/index.html'))
        ->toContain('<meta property="og:image:type" content="image/svg+xml">')
        ->not->toContain('og:image:width');
});

test('renders the language of the site', function () {
    config(['pluma.language' => 'es-ES']);
    aPublishedPage('My Post', 'my-post');

    app(SiteGenerator::class)->generateAll();

    $html = disk()->get('site/my-post/index.html');

    expect($html)
        ->toContain('<html lang="es-ES">')
        ->toContain('<meta property="og:locale" content="es_ES">')
        ->and(jsonLdSchemas($html)['Article']['inLanguage'])->toBe('es-ES');
});

test('falls back to english when the site has no language', function () {
    config(['pluma.language' => '']);

    app(SiteGenerator::class)->generateAll();

    expect(disk()->get('site/index.html'))
        ->toContain('<html lang="en">')
        ->toContain('<meta property="og:locale" content="en">');
});

test('renders the author of the site in every document', function () {
    config(['pluma.author' => 'Jane Doe']);
    aPublishedPage('My Post', 'my-post');

    app(SiteGenerator::class)->generateAll();

    $html = disk()->get('site/my-post/index.html');

    expect($html)->toContain('<meta name="author" content="Jane Doe">')
        ->and(jsonLdSchemas($html)['Article']['author'])->toBe(['@type' => 'Person', 'name' => 'Jane Doe'])
        ->and(disk()->get('site/index.html'))->toContain('<meta name="author" content="Jane Doe">');
});

test('leaves the author out when the site has none', function () {
    config(['pluma.author' => '']);
    aPublishedPage('My Post', 'my-post');

    app(SiteGenerator::class)->generateAll();

    $html = disk()->get('site/my-post/index.html');

    expect($html)->not->toContain('name="author"')
        ->and(jsonLdSchemas($html)['Article'])->not->toHaveKey('author');
});

test('renders the x accounts of the site and the author with a leading at sign', function () {
    config(['pluma.twitter.site' => 'pluma', 'pluma.twitter.creator' => '@janedoe']);

    app(SiteGenerator::class)->generateAll();

    expect(disk()->get('site/index.html'))
        ->toContain('<meta name="twitter:site" content="@pluma">')
        ->toContain('<meta name="twitter:creator" content="@janedoe">');
});

test('leaves the x accounts out when the site has none', function () {
    config(['pluma.twitter.site' => '', 'pluma.twitter.creator' => '']);

    app(SiteGenerator::class)->generateAll();

    expect(disk()->get('site/index.html'))
        ->not->toContain('twitter:site')
        ->not->toContain('twitter:creator');
});

test('links the favicon and the apple touch icon of the site', function () {
    config(['pluma.favicon' => 'favicon.png', 'pluma.apple_touch_icon' => 'apple-touch-icon.png']);
    disk()->put('assets/favicon.png', UploadedFile::fake()->image('favicon.png', 32, 32)->getContent());
    disk()->put('assets/apple-touch-icon.png', UploadedFile::fake()->image('apple-touch-icon.png', 180, 180)->getContent());

    app(SiteGenerator::class)->generateAll();

    expect(disk()->get('site/404.html'))
        ->toContain('<link rel="icon" href="http://example.com/favicon.png" type="image/png" sizes="32x32">')
        ->toContain('<link rel="apple-touch-icon" href="http://example.com/apple-touch-icon.png" sizes="180x180">');
});

test('links an svg favicon without a size', function () {
    config(['pluma.favicon' => 'favicon.svg']);
    disk()->put('assets/favicon.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"></svg>');

    app(SiteGenerator::class)->generateAll();

    expect(disk()->get('site/index.html'))
        ->toContain('<link rel="icon" href="http://example.com/favicon.svg" type="image/svg+xml">');
});

test('does not link icons when the site has none', function () {
    config(['pluma.favicon' => '', 'pluma.apple_touch_icon' => '']);

    app(SiteGenerator::class)->generateAll();

    expect(disk()->get('site/index.html'))
        ->not->toContain('rel="icon"')
        ->not->toContain('rel="apple-touch-icon"');
});

test('describes the site as a website in the structured data of the index', function () {
    config(['pluma.language' => 'en']);

    app(SiteGenerator::class)->generateAll();

    expect(jsonLdSchemas(disk()->get('site/index.html')))->toBe(['WebSite' => [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => 'My Site',
        'url' => 'http://example.com/',
        'description' => 'A site about things',
        'inLanguage' => 'en',
        'image' => 'http://example.com/site%20cover.png',
    ]]);
});

test('describes a page as an article in its structured data', function () {
    config(['pluma.language' => 'en']);
    aPublishedPage(
        'My Post',
        'my-post',
        content: 'Some text',
        published_at: Carbon::parse('2025-01-01 10:00:00'),
        revised_at: Carbon::parse('2025-07-01 09:00:00'),
        cover_image: 'cover.png',
    );

    app(SiteGenerator::class)->generateAll();

    expect(jsonLdSchemas(disk()->get('site/my-post/index.html'))['Article'])->toBe([
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => 'My Post',
        'description' => 'Some text',
        'url' => 'http://example.com/my-post/',
        'mainEntityOfPage' => 'http://example.com/my-post/',
        'image' => 'http://example.com/my-post/cover.png',
        'datePublished' => '2025-01-01T10:00:00+00:00',
        'dateModified' => '2025-07-01T09:00:00+00:00',
        'inLanguage' => 'en',
    ]);
});

test('lists the published pages that contain a page in its breadcrumbs', function () {
    aPublishedPage('Blog', 'blog');
    aPage('Draft Year', 'blog/2025');
    aPublishedPage('My Post', 'blog/2025/my-post');

    app(SiteGenerator::class)->generateAll();

    expect(jsonLdSchemas(disk()->get('site/blog/2025/my-post/index.html'))['BreadcrumbList']['itemListElement'])->toBe([
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'My Site', 'item' => 'http://example.com/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => 'http://example.com/blog/'],
        ['@type' => 'ListItem', 'position' => 3, 'name' => 'My Post', 'item' => 'http://example.com/blog/2025/my-post/'],
    ]);
});

test('describes a tag page as a collection in its structured data', function () {
    config(['pluma.tags.create_pages' => true]);
    aTag('Laravel', content: 'All about Laravel');

    app(SiteGenerator::class)->generateAll();

    $schemas = jsonLdSchemas(disk()->get('site/tags/laravel/index.html'));

    expect($schemas['CollectionPage'])
        ->toMatchArray(['name' => 'Laravel', 'description' => 'All about Laravel', 'url' => 'http://example.com/tags/laravel/'])
        ->and($schemas['BreadcrumbList']['itemListElement'])->toBe([
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'My Site', 'item' => 'http://example.com/'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Laravel', 'item' => 'http://example.com/tags/laravel/'],
        ]);
});

test('does not render structured data in the not found page', function () {
    app(SiteGenerator::class)->generateAll();

    expect(jsonLdSchemas(disk()->get('site/404.html')))->toBe([]);
});

test('leaves empty values out of the structured data', function () {
    config(['pluma.title' => '', 'pluma.description' => '', 'pluma.cover_image' => '']);

    app(SiteGenerator::class)->generateAll();

    expect(jsonLdSchemas(disk()->get('site/index.html'))['WebSite'])
        ->not->toHaveKeys(['name', 'description', 'image']);
});
