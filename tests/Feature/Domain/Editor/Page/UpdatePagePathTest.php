<?php

use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Page\PagePath;
use App\Domain\Editor\Page\PageRepository;
use App\Domain\Editor\Page\UpdatePagePath;
use App\Domain\Error;
use App\Domain\Generator\SiteGenerator;
use App\Domain\Ok;
use Carbon\Carbon;

test('returns Ok with page when path is updated successfully', function () {
    $repository = initializeSite();

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $page = ContentPage::draft('Path Update Test');
    $repository->save($page);

    $action = new UpdatePagePath(
        repository: app(PageRepository::class),
        siteGenerator: app(SiteGenerator::class),
    );

    $result = $action->__invoke('path-update-test', 'new-path');

    expect($result)->toBeInstanceOf(Ok::class)
        ->and($result->unwrap())->toBeInstanceOf(ContentPage::class);

    Carbon::setTestNow(null);
});

test('returns Error when new path already exists for another page', function () {
    $repository = initializeSite();

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $pageA = ContentPage::draft('Existing Page');
    $repository->save($pageA);

    $pageB = new ContentPage(
        title: 'Another Page',
        path: new PagePath('existing-page'),
        content: new Markdown('# Content'),
        created_at: Carbon::now(),
    );
    $repository->save($pageB);

    $action = new UpdatePagePath(
        repository: app(PageRepository::class),
        siteGenerator: app(SiteGenerator::class),
    );

    $result = $action->__invoke('another-page', 'existing-page');

    expect($result)->toBeInstanceOf(Error::class)
        ->and($result->unwrapError())->toBe('A page with this path already exists.');

    Carbon::setTestNow(null);
});
