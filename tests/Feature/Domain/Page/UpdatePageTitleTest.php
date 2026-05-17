<?php

use App\Domain\Error;
use App\Domain\Ok;
use App\Domain\Page\Markdown;
use App\Domain\Page\Page;
use App\Domain\Page\PagePath;
use App\Domain\Page\PageRepository;
use App\Domain\Page\SiteGenerator;
use App\Domain\Page\UpdatePageTitle;
use Carbon\Carbon;

test('returns Ok with page when title is updated successfully', function () {
    $repository = initializeSite();

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $page = Page::draft('Original Title');
    $repository->save($page);

    $action = new UpdatePageTitle(
        repository: app(PageRepository::class),
        siteGenerator: app(SiteGenerator::class),
    );

    $result = $action->__invoke('original-title', 'New Title');

    expect($result)->toBeInstanceOf(Ok::class)
        ->and($result->unwrap())->toBeInstanceOf(Page::class);

    Carbon::setTestNow(null);
});

test('returns Ok with page when title is updated without path change', function () {
    $repository = initializeSite();

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $page = new Page(
        title: 'Original Title',
        path: new PagePath('custom-path'),
        content: new Markdown('# Content'),
        created_at: Carbon::now(),
    );
    $repository->save($page);

    $action = new UpdatePageTitle(
        repository: app(PageRepository::class),
        siteGenerator: app(SiteGenerator::class),
    );

    $result = $action->__invoke('custom-path', 'New Title');

    expect($result)->toBeInstanceOf(Ok::class)
        ->and($repository->findByPath('custom-path')->title)->toBe('New Title');

    Carbon::setTestNow(null);
});

test('returns Error when new title generates conflicting slug with another page', function () {
    $repository = initializeSite();

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $pageA = Page::draft('Existing Title');
    $repository->save($pageA);

    $pageB = new Page(
        title: 'Different Title',
        path: new PagePath('different-title'),
        content: new Markdown('# Content'),
        created_at: Carbon::now(),
    );
    $repository->save($pageB);

    $action = new UpdatePageTitle(
        repository: app(PageRepository::class),
        siteGenerator: app(SiteGenerator::class),
    );

    $result = $action->__invoke('different-title', 'Existing Title');

    expect($result)->toBeInstanceOf(Error::class)
        ->and($result->unwrapError())->toBe('This title generates the same slug as another page that already exists.');

    Carbon::setTestNow(null);
});
