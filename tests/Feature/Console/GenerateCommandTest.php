<?php

use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Page\PagePath;
use App\Domain\Editor\Page\TagPage;
use Carbon\Carbon;
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
    config()->set('pluma.create_tag_pages', true);

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
    config()->set('pluma.create_tag_pages', false);

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
