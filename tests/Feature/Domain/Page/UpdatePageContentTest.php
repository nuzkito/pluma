<?php

use App\Domain\Page\Markdown;
use App\Domain\Page\Page;
use App\Domain\Page\PagePath;
use App\Domain\Page\PageRepository;
use App\Domain\Page\SiteGenerator;
use App\Domain\Page\UpdatePageContent;
use Carbon\Carbon;

test('updates page content successfully', function () {
    $repository = initializeSite();

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $page = new Page(
        title: 'Content Update Test',
        path: new PagePath('content-test'),
        content: new Markdown('# Old Content'),
        created_at: Carbon::now(),
    );
    $repository->save($page);

    $action = new UpdatePageContent(
        repository: app(PageRepository::class),
        siteGenerator: app(SiteGenerator::class),
    );

    $action->__invoke('content-test', '# New Content');

    expect((string) $repository->findByPath('content-test')->content)->toBe('# New Content');

    Carbon::setTestNow(null);
});

test('clears content when empty string is provided', function () {
    $repository = initializeSite();

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $page = new Page(
        title: 'Clear Content Test',
        path: new PagePath('clear-content-test'),
        content: new Markdown('# Has Content'),
        created_at: Carbon::now(),
    );
    $repository->save($page);

    $action = new UpdatePageContent(
        repository: app(PageRepository::class),
        siteGenerator: app(SiteGenerator::class),
    );

    $action->__invoke('clear-content-test', '');

    expect((string) $repository->findByPath('clear-content-test')->content)->toBe('');

    Carbon::setTestNow(null);
});
