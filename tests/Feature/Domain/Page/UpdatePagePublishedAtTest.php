<?php

use App\Domain\Page\Markdown;
use App\Domain\Page\Page;
use App\Domain\Page\PagePath;
use App\Domain\Page\PageRepository;
use App\Domain\Page\SiteGenerator;
use App\Domain\Page\UpdatePagePublishedAt;
use Carbon\Carbon;

test('publishes page when published_at is provided', function () {
    $repository = initializeSite();

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $page = Page::draft('Publish Test');
    expect($page->isDraft())->toBeTrue();
    $repository->save($page);

    $action = new UpdatePagePublishedAt(
        repository: app(PageRepository::class),
        siteGenerator: app(SiteGenerator::class),
    );

    $action->__invoke('publish-test', '2025-06-15T14:30');

    Carbon::setTestNow(null);

    expect($repository->findByPath('publish-test')->isPublished())->toBeTrue()
        ->and($repository->findByPath('publish-test')->published_at?->format('Y-m-d H:i'))->toBe('2025-06-15 14:30');
});

test('unpublishes page when published_at is null', function () {
    $repository = initializeSite();

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $page = new Page(
        title: 'Unpublish Test',
        path: new PagePath('unpublish-test'),
        content: new Markdown('# Content'),
        created_at: Carbon::now(),
        published_at: Carbon::parse('2025-06-15 14:30:00'),
    );
    $repository->save($page);

    $action = new UpdatePagePublishedAt(
        repository: app(PageRepository::class),
        siteGenerator: app(SiteGenerator::class),
    );

    $action->__invoke('unpublish-test', null);

    Carbon::setTestNow(null);

    expect($repository->findByPath('unpublish-test')->isDraft())->toBeTrue()
        ->and($repository->findByPath('unpublish-test')->published_at)->toBeNull();
});
