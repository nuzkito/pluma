<?php

use App\Domain\Editor\Page\PublishPage;

test('publish page sets published_at and syncs site generation', function () {
    $page = aPage('Test Page', 'test-page');

    $action = app(PublishPage::class);

    $result = $action((string) $page->path);

    expect($result->isPublished())->toBeTrue()
        ->and($result->published_at)->not->toBeNull();
});

test('publish page always syncs site generation', function () {
    $page = aPage('Test Page', 'test-page');

    $action = app(PublishPage::class);

    $result = $action((string) $page->path);

    expect($result->isPublished())->toBeTrue()
        ->and("site/{$page->path}")->toExistOnDisk();
});
