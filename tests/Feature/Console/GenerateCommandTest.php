<?php

use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Page\PagePath;
use App\Domain\Editor\Page\TagPage;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\artisan;

test('generates the static site', function () {
    $repository = initializeSite();

    $repository->save(new ContentPage(
        title: 'Hello World',
        path: new PagePath('hello-world'),
        content: new Markdown('# Hello World'),
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
        rss: true,
    ));

    $repository->save(new ContentPage(
        title: 'Draft Page',
        path: new PagePath('draft-page'),
        content: new Markdown('This is a draft'),
        created_at: Carbon::parse('2025-02-01'),
    ));

    artisan('pluma:generate')
        ->expectsOutputToContain('Generating static site')
        ->assertSuccessful();

    $disk = Storage::disk('current');

    expect($disk->exists('site/index.html'))->toBeTrue()
        ->and($disk->exists('site/404.html'))->toBeTrue()
        ->and($disk->exists('site/hello-world/index.html'))->toBeTrue()
        ->and($disk->exists('site/draft-page'))->toBeFalse();
});

test('generates RSS feed when pages have rss enabled', function () {
    $repository = initializeSite();
    config(['pluma.rss.enabled' => true]);

    $repository->save(new ContentPage(
        title: 'Hello World',
        path: new PagePath('hello-world'),
        content: new Markdown('# Hello World'),
        created_at: Carbon::parse('2025-01-01'),

        published_at: Carbon::parse('2025-01-15'),
        rss: true,
    ));

    $repository->save(new ContentPage(
        title: 'Draft Page',
        path: new PagePath('draft-page'),
        content: new Markdown('This is a draft'),
        created_at: Carbon::parse('2025-02-01'),
    ));

    artisan('pluma:generate')
        ->assertSuccessful();

    $disk = Storage::disk('current');

    expect($disk->exists('site/feed.xml'))->toBeTrue();

    $rss = $disk->get('site/feed.xml');

    expect($rss)->toContain('<title>Hello World</title>')
        ->and($rss)->not->toContain('Draft Page');
});

test('copies resource files to site directory', function () {
    initializeSite();

    artisan('pluma:generate')
        ->assertSuccessful();

    $disk = Storage::disk('current');

    expect($disk->exists('site/styles.css'))->toBeTrue()
        ->and($disk->exists('site/scripts.js'))->toBeTrue();
});

test('generates tag pages with their posts', function () {
    $repository = initializeSite();

    $repository->save(new ContentPage(
        title: 'Hello World',
        path: new PagePath('hello-world'),
        content: new Markdown('# Hello World'),
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
        tags: ['Laravel'],
    ));

    $repository->save(TagPage::create('Laravel'));

    artisan('pluma:generate')
        ->assertSuccessful();

    $disk = Storage::disk('current');

    expect($disk->exists('site/tags/laravel/index.html'))->toBeTrue()
        ->and($disk->get('site/tags/laravel/index.html'))
        ->toContain('Laravel')
        ->toContain('Hello World');
});

test('links page tags to their tag page when create_tag_pages is enabled', function () {
    $repository = initializeSite();
    config()->set('pluma.tags.create_pages', true);

    $repository->save(new ContentPage(
        title: 'Hello World',
        path: new PagePath('hello-world'),
        content: new Markdown('# Hello World'),
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
        tags: ['Laravel'],
    ));

    artisan('pluma:generate')
        ->assertSuccessful();

    $html = Storage::disk('current')->get('site/hello-world/index.html');

    expect($html)->toContain('href="http://localhost:8001/tags/laravel/"');
});

test('does not link page tags when create_tag_pages is disabled', function () {
    $repository = initializeSite();
    config()->set('pluma.tags.create_pages', false);

    $repository->save(new ContentPage(
        title: 'Hello World',
        path: new PagePath('hello-world'),
        content: new Markdown('# Hello World'),
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
        tags: ['Laravel'],
    ));

    artisan('pluma:generate')
        ->assertSuccessful();

    $html = Storage::disk('current')->get('site/hello-world/index.html');

    expect($html)->toContain('Laravel')
        ->not->toContain('/tags/laravel/');
});

test('optimizes image assets and copies the rest of the assets when generating', function () {
    $repository = initializeSite();

    $repository->save(new ContentPage(
        title: 'Hello World',
        path: new PagePath('hello-world'),
        content: new Markdown('# Hello World'),
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
    ));

    $disk = Storage::disk('current');
    $photo = UploadedFile::fake()->image('photo.jpg', 3000, 2000)->getContent();
    $disk->put('assets/hello-world/photo.jpg', $photo);
    $disk->put('assets/hello-world/notes.txt', 'text content');

    artisan('pluma:generate')
        ->assertSuccessful();

    expect($disk->exists('site/hello-world/photo.jpg'))->toBeTrue()
        ->and($disk->size('site/hello-world/photo.jpg'))->toBeLessThan($disk->size('assets/hello-world/photo.jpg'))
        ->and($disk->get('site/hello-world/notes.txt'))->toBe('text content');
});

test('renders the cover image above the title when the page has one', function () {
    $repository = initializeSite();

    $repository->save(new ContentPage(
        title: 'Hello World',
        path: new PagePath('hello-world'),
        content: new Markdown('# Hello World'),
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
        cover_image: 'header image.png',
    ));

    artisan('pluma:generate')
        ->assertSuccessful();

    $html = Storage::disk('current')->get('site/hello-world/index.html');

    expect($html)->toContain('<img src="header%20image.png" alt="header image.png">')
        ->and(strpos($html, '<img src="header%20image.png"'))->toBeLessThan(strpos($html, '<h1>Hello World</h1>'));
});

test('does not render a cover image when the page has none', function () {
    $repository = initializeSite();

    $repository->save(new ContentPage(
        title: 'Hello World',
        path: new PagePath('hello-world'),
        content: new Markdown('Some content'),
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
    ));

    artisan('pluma:generate')
        ->assertSuccessful();

    $html = Storage::disk('current')->get('site/hello-world/index.html');

    expect($html)->not->toContain('<img');
});

test('renders markdown content as html', function () {
    $repository = initializeSite();

    $repository->save(new ContentPage(
        title: 'Hello World',
        path: new PagePath('hello-world'),
        content: new Markdown('# Hello World'),
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
    ));

    artisan('pluma:generate')
        ->assertSuccessful();

    $html = Storage::disk('current')->get('site/hello-world/index.html');

    expect($html)->toContain('<h1>Hello World</h1>');
});
