<?php

use App\Domain\Editor\Page\PublishPage;
use App\Domain\Editor\Page\UnpublishPage;

test('unpublish page clears published_at and syncs site generation', function () {
    $page = aPage('Test Page', 'test-page');

    app(PublishPage::class)((string) $page->path);

    expect("site/{$page->path}")->toExistOnDisk();

    $action = app(UnpublishPage::class);
    $action((string) $page->path);

    $updated = repository()->findByPath((string) $page->path);

    expect($updated->isPublished())->toBeFalse()
        ->and($updated->published_at)->toBeNull();

    expect("site/{$page->path}")->toBeMissingFromDisk();
});

test('unpublish regenerates index', function () {
    $page = aPage('Test Page', 'test-page');

    app(PublishPage::class)((string) $page->path);

    expect(disk()->get('site/index.html'))->toContain($page->title);

    $action = app(UnpublishPage::class);
    $action((string) $page->path);

    expect(disk()->get('site/index.html'))->not->toContain($page->title);
});
