<?php

use App\Domain\Editor\Page\DeletePage;
use App\Domain\Editor\Page\PublishPage;

test('delete page removes it from repository', function () {
    $page = aPage('Test Page', 'test-page');

    $action = app(DeletePage::class);
    $action((string) $page->path);

    expect(repository()->findByPath((string) $page->path))->toBeNull();
});

test('delete page removes generated site files', function () {
    $page = aPage('Test Page', 'test-page');

    app(PublishPage::class)((string) $page->path);

    expect("site/{$page->path}")->toExistOnDisk();

    $action = app(DeletePage::class);
    $action((string) $page->path);

    expect("site/{$page->path}")->toBeMissingFromDisk();
});

test('delete page regenerates index when deleting published page', function () {
    $page1 = aPage('First Page', 'first-page');

    $page2 = aPage('Second Page', 'second-page');

    app(PublishPage::class)((string) $page1->path);
    app(PublishPage::class)((string) $page2->path);

    expect(disk()->get('site/index.html'))->toContain('first-page')
        ->and(disk()->get('site/index.html'))->toContain('second-page');

    $action = app(DeletePage::class);
    $action((string) $page1->path);

    expect(disk()->get('site/index.html'))->not->toContain('first-page')
        ->and(disk()->get('site/index.html'))->toContain('second-page');
});

test('delete page does not touch site files when deleting draft', function () {
    $page = aPage('Draft Page', 'draft-page');

    $action = app(DeletePage::class);
    $action((string) $page->path);

    expect("site/{$page->path}")->toBeMissingFromDisk();
});
