<?php

use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Page\PagePath;
use App\Domain\Editor\Page\PageRepository;
use App\Domain\Editor\Page\UpdatePagePublishedAt;
use App\Domain\Generator\SiteGenerator;
use Carbon\Carbon;

test('publishes page when published_at is provided', function () {
    $repository = initializeSite();

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $page = ContentPage::draft('Publish Test');
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

    $page = new ContentPage(
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
