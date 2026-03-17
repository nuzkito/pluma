<?php

use App\Domain\Page\Markdown;
use App\Domain\Page\Page;
use App\Domain\Page\PagePath;
use Carbon\Carbon;

test('creates a draft with slug path and empty content', function () {
    $page = Page::draft('My New Post');

    expect($page->title)->toBe('My New Post')
        ->and((string) $page->path)->toBe('my-new-post')
        ->and((string) $page->content)->toBe('')
        ->and($page->rss)->toBeTrue()
        ->and($page->published_at)->toBeNull();
});

test('converts content to html', function () {
    $page = new Page(
        title: 'Test',
        path: new PagePath('test'),
        content: new Markdown('# Hello World'),
        created_at: Carbon::now(),
    );

    expect($page->content->html())->toContain('<h1>Hello World</h1>');
});

test('renames the page', function () {
    $page = Page::draft('Original Title');

    $page->rename('Updated Title');

    expect($page->title)->toBe('Updated Title');
});

test('moves the page to a new path', function () {
    $page = Page::draft('My Post');

    $page->moveToPath(new PagePath('custom-path'));

    expect((string) $page->path)->toBe('custom-path');
});

test('sets the page content', function () {
    $page = Page::draft('My Post');

    $page->setContent(new Markdown('New content'));

    expect((string) $page->content)->toBe('New content');
});

test('sets the page content to empty string', function () {
    $page = new Page(
        title: 'Test',
        path: new PagePath('test'),
        content: new Markdown('Some content'),
        created_at: Carbon::now(),
    );

    $page->setContent(new Markdown(''));

    expect((string) $page->content)->toBe('');
});

test('toggles the rss flag', function () {
    $page = Page::draft('My Post');

    $page->toggleRss(false);

    expect($page->rss)->toBeFalse();
});

test('publishes a page', function () {
    Carbon::setTestNow(Carbon::parse('2025-06-01 12:00:00'));

    $page = Page::draft('My Post');
    $page->publish(Carbon::now());

    Carbon::setTestNow(null);

    expect($page->published_at)->not->toBeNull()
        ->and($page->published_at->toDateString())->toBe('2025-06-01');
});

test('unpublishes a page', function () {
    $page = new Page(
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
    $page = new Page(
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
    $page = Page::draft('My Post');

    expect($page->isDraft())->toBeTrue()
        ->and($page->isPublished())->toBeFalse();
});

test('converts to array without published_at when draft', function () {
    $createdAt = Carbon::parse('2025-01-01T00:00:00+00:00');

    $page = new Page(
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

    $page = new Page(
        title: 'My Post',
        path: new PagePath('my-post'),
        content: new Markdown(''),
        created_at: $now,
        published_at: $now,
    );

    $array = $page->toArray();

    expect($array)->toHaveKey('published_at', '2025-06-01T12:00:00+00:00');
});
