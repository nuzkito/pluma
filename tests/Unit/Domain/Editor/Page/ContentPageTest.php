<?php

use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Page\PagePath;
use Carbon\Carbon;

test('creates a draft with slug path and empty content', function () {
    $page = ContentPage::draft('My New Post', 'my-new-post');

    expect($page->title)->toBe('My New Post')
        ->and((string) $page->path)->toBe('my-new-post')
        ->and((string) $page->content)->toBe('')
        ->and($page->rss)->toBeTrue()
        ->and($page->published_at)->toBeNull();
});

test('renames the page', function () {
    $page = ContentPage::draft('Original Title', 'original-title');

    $page->rename('Updated Title');

    expect($page->title)->toBe('Updated Title')
        ->and((string) $page->path)->toBe('updated-title');
});

test('renaming a page inside a directory keeps the directory', function () {
    $page = ContentPage::draft('Draft', 'posts/draft');

    $page->rename('My New Post');

    expect($page->title)->toBe('My New Post')
        ->and((string) $page->path)->toBe('posts/my-new-post');
});

test('renaming does not change a manually customized path', function () {
    $page = new ContentPage(
        title: 'Original Title',
        path: new PagePath('posts/custom-path'),
        content: new Markdown(''),
        created_at: Carbon::now(),
    );

    $page->rename('My New Post');

    expect($page->title)->toBe('My New Post')
        ->and((string) $page->path)->toBe('posts/custom-path');
});

test('moves the page to a new path', function () {
    $page = ContentPage::draft('My Post', 'my-post');

    $page->moveToPath(new PagePath('custom-path'));

    expect((string) $page->path)->toBe('custom-path');
});

test('sets the page content', function () {
    $page = ContentPage::draft('My Post', 'my-post');

    $page->setContent(new Markdown('New content'));

    expect((string) $page->content)->toBe('New content');
});

test('sets the page content to empty string', function () {
    $page = new ContentPage(
        title: 'Test',
        path: new PagePath('test'),
        content: new Markdown('Some content'),
        created_at: Carbon::now(),
    );

    $page->setContent(new Markdown(''));

    expect((string) $page->content)->toBe('');
});

test('toggles the rss flag', function () {
    $page = ContentPage::draft('My Post', 'my-post');

    $page->toggleRss(false);

    expect($page->rss)->toBeFalse();
});

test('publishes a page', function () {
    Carbon::setTestNow(Carbon::parse('2025-06-01 12:00:00'));

    $page = ContentPage::draft('My Post', 'my-post');
    $page->publish(Carbon::now());

    Carbon::setTestNow(null);

    expect($page->published_at)->not->toBeNull()
        ->and($page->published_at->toDateString())->toBe('2025-06-01');
});

test('unpublishes a page', function () {
    $page = new ContentPage(
        title: 'Published',
        path: new PagePath('published'),
        content: new Markdown(''),
        created_at: Carbon::now(),
        published_at: Carbon::now(),
    );

    $page->unpublish();

    expect($page->published_at)->toBeNull();
});

test('is published when published_at is set', function () {
    $page = new ContentPage(
        title: 'Published',
        path: new PagePath('published'),
        content: new Markdown(''),
        created_at: Carbon::now(),
        published_at: Carbon::now(),
    );

    expect($page->isPublished())->toBeTrue()
        ->and($page->isDraft())->toBeFalse();
});

test('is draft when published_at is null', function () {
    $page = ContentPage::draft('My Post', 'my-post');

    expect($page->isDraft())->toBeTrue()
        ->and($page->isPublished())->toBeFalse();
});

test('converts to array without published_at when draft', function () {
    $createdAt = Carbon::parse('2025-01-01T00:00:00+00:00');

    $page = new ContentPage(
        title: 'My Post',
        path: new PagePath('my-post'),
        content: new Markdown('Some content'),
        created_at: $createdAt,
        rss: true,
    );

    $array = $page->toArray();

    expect($array)->toBe([
        'title' => 'My Post',
        'path' => 'my-post',
        'created_at' => '2025-01-01T00:00:00+00:00',
        'rss' => true,
    ]);
});

test('converts to array with published_at when published', function () {
    $now = Carbon::parse('2025-06-01T12:00:00+00:00');

    $page = new ContentPage(
        title: 'My Post',
        path: new PagePath('my-post'),
        content: new Markdown(''),
        created_at: $now,
        published_at: $now,
    );

    $array = $page->toArray();

    expect($array)->toHaveKey('published_at', '2025-06-01T12:00:00+00:00');
});

test('sets tags on a page', function () {
    $page = ContentPage::draft('My Post', 'my-post');

    $page->withTags(['php', 'laravel']);

    expect($page->tags)->toBe(['php', 'laravel']);
});

test('strips keys from tags array', function () {
    $page = ContentPage::draft('My Post', 'my-post');

    $page->withTags([1 => 'php', 3 => 'laravel']);

    expect($page->tags)->toBe(['php', 'laravel']);
});

test('toArray includes tags when present', function () {
    $createdAt = Carbon::parse('2025-01-01T00:00:00+00:00');

    $page = new ContentPage(
        title: 'My Post',
        path: new PagePath('my-post'),
        content: new Markdown('Some content'),
        created_at: $createdAt,
        tags: ['php', 'laravel'],
    );

    $array = $page->toArray();

    expect($array)->toHaveKey('tags', ['php', 'laravel']);
});

test('toArray excludes empty tags array', function () {
    $createdAt = Carbon::parse('2025-01-01T00:00:00+00:00');

    $page = new ContentPage(
        title: 'My Post',
        path: new PagePath('my-post'),
        content: new Markdown('Some content'),
        created_at: $createdAt,
        tags: [],
    );

    $array = $page->toArray();

    expect($array)->not->toHaveKey('tags');
});
