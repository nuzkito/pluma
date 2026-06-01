<?php

use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Page\Page;
use App\Domain\Editor\Page\PagePath;
use App\Domain\Editor\Page\PageRepository;
use App\Domain\Editor\Page\RemovePageTag;
use App\Domain\Generator\SiteGenerator;
use Carbon\Carbon;

test('removes tag by index', function () {
    $repository = initializeSite();

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $page = new Page(
        title: 'Remove Tag Test',
        path: new PagePath('remove-tag-test'),
        content: new Markdown('# Content'),
        created_at: Carbon::now(),
        tags: ['php', 'laravel', 'testing'],
    );
    $repository->save($page);

    $action = new RemovePageTag(
        repository: app(PageRepository::class),
        siteGenerator: app(SiteGenerator::class),
    );

    $result = $action->__invoke('remove-tag-test', 1);

    Carbon::setTestNow(null);

    expect($result)->toBeInstanceOf(Page::class)
        ->and($repository->findByPath('remove-tag-test')->tags)->toEqual(['php', 'testing']);
});

test('removes first tag when index is 0', function () {
    $repository = initializeSite();

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $page = new Page(
        title: 'First Tag Test',
        path: new PagePath('first-tag-test'),
        content: new Markdown('# Content'),
        created_at: Carbon::now(),
        tags: ['php', 'laravel'],
    );
    $repository->save($page);

    $action = new RemovePageTag(
        repository: app(PageRepository::class),
        siteGenerator: app(SiteGenerator::class),
    );

    $result = $action->__invoke('first-tag-test', 0);

    Carbon::setTestNow(null);

    expect($repository->findByPath('first-tag-test')->tags)->toEqual(['laravel']);
});

test('removes last tag when index is last', function () {
    $repository = initializeSite();

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $page = new Page(
        title: 'Last Tag Test',
        path: new PagePath('last-tag-test'),
        content: new Markdown('# Content'),
        created_at: Carbon::now(),
        tags: ['php', 'laravel'],
    );
    $repository->save($page);

    $action = new RemovePageTag(
        repository: app(PageRepository::class),
        siteGenerator: app(SiteGenerator::class),
    );

    $result = $action->__invoke('last-tag-test', 1);

    Carbon::setTestNow(null);

    expect($repository->findByPath('last-tag-test')->tags)->toEqual(['php']);
});

test('removes tag from published page and regenerates site', function () {
    $repository = initializeSite();

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $page = new Page(
        title: 'Published Remove Tag Test',
        path: new PagePath('published-remove-tag-test'),
        content: new Markdown('# Content'),
        created_at: Carbon::now(),
        published_at: Carbon::parse('2025-01-01 10:00:00'),
        tags: ['php', 'laravel'],
    );
    $repository->save($page);

    $siteGenerator = mock(SiteGenerator::class, function ($mock) {
        $mock->shouldReceive('generatePage')->once();
        $mock->shouldReceive('regenerateIndex')->once();
    });

    $action = new RemovePageTag(
        repository: app(PageRepository::class),
        siteGenerator: $siteGenerator,
    );

    $result = $action->__invoke('published-remove-tag-test', 0);

    Carbon::setTestNow(null);

    expect($result->tags)->toEqual(['laravel']);
});
