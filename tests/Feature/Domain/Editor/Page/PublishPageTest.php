<?php

use App\Domain\Editor\Page\PublishPage;
use Carbon\Carbon;

test('publish page sets published_at and syncs site generation', function () {
    $page = aPage('Test Page', 'test-page');

    $action = app(PublishPage::class);

    $result = $action((string) $page->path);

    expect($result->isPublished())->toBeTrue()
        ->and($result->published_at)->not->toBeNull();
});

test('publish page keeps the published_at it already had', function () {
    aPage('Test Page', 'test-page', published_at: Carbon::parse('2025-01-01 10:00:00'));

    $result = app(PublishPage::class)('test-page');

    expect($result->isPublished())->toBeTrue()
        ->and($result->published_at->format('Y-m-d H:i'))->toBe('2025-01-01 10:00');
});

test('publish page always syncs site generation', function () {
    $page = aPage('Test Page', 'test-page');

    $action = app(PublishPage::class);

    $result = $action((string) $page->path);

    expect($result->isPublished())->toBeTrue()
        ->and("site/{$page->path}")->toExistOnDisk();
});
