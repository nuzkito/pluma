<?php

use App\Domain\Page\Markdown;
use App\Domain\Page\Page;
use App\Domain\Page\PagePath;
use App\Domain\Page\PageRepository;
use App\Domain\Page\SiteGenerator;
use App\Domain\Page\UpdatePageRss;
use Carbon\Carbon;

test('enables RSS when enabled is true', function () {
    $repository = initializeSite();

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $page = new Page(
        title: 'RSS Enable Test',
        path: new PagePath('rss-enable-test'),
        content: new Markdown('# Content'),
        created_at: Carbon::now(),
        rss: false,
    );
    $repository->save($page);

    $action = new UpdatePageRss(
        repository: app(PageRepository::class),
        siteGenerator: app(SiteGenerator::class),
    );

    $action->__invoke('rss-enable-test', true);

    expect($repository->findByPath('rss-enable-test')->rss)->toBeTrue();

    Carbon::setTestNow(null);
});

test('disables RSS when enabled is false', function () {
    $repository = initializeSite();

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $page = new Page(
        title: 'RSS Disable Test',
        path: new PagePath('rss-disable-test'),
        content: new Markdown('# Content'),
        created_at: Carbon::now(),
        rss: true,
    );
    $repository->save($page);

    $action = new UpdatePageRss(
        repository: app(PageRepository::class),
        siteGenerator: app(SiteGenerator::class),
    );

    $action->__invoke('rss-disable-test', false);

    expect($repository->findByPath('rss-disable-test')->rss)->toBeFalse();

    Carbon::setTestNow(null);
});
