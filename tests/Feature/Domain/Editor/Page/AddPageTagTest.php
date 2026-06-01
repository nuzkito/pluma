<?php

use App\Domain\Editor\Page\AddPageTag;
use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Page\Page;
use App\Domain\Editor\Page\PagePath;
use App\Domain\Editor\Page\PageRepository;
use App\Domain\Generator\SiteGenerator;
use Carbon\Carbon;

test('adds tag to page', function () {
    $repository = initializeSite();

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $page = new Page(
        title: 'Tag Test',
        path: new PagePath('tag-test'),
        content: new Markdown('# Content'),
        created_at: Carbon::now(),
        tags: ['php'],
    );
    $repository->save($page);

    $action = new AddPageTag(
        repository: app(PageRepository::class),
        siteGenerator: app(SiteGenerator::class),
    );

    $result = $action->__invoke('tag-test', 'laravel');

    Carbon::setTestNow(null);

    expect($result)->toBeInstanceOf(Page::class)
        ->and($repository->findByPath('tag-test')->tags)->toEqual(['php', 'laravel']);
});

test('does not add duplicate tag', function () {
    $repository = initializeSite();

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $page = new Page(
        title: 'Duplicate Tag Test',
        path: new PagePath('duplicate-tag-test'),
        content: new Markdown('# Content'),
        created_at: Carbon::now(),
        tags: ['php'],
    );
    $repository->save($page);

    $action = new AddPageTag(
        repository: app(PageRepository::class),
        siteGenerator: app(SiteGenerator::class),
    );

    $result = $action->__invoke('duplicate-tag-test', 'php');

    Carbon::setTestNow(null);

    expect($result)->toBeInstanceOf(Page::class)
        ->and($repository->findByPath('duplicate-tag-test')->tags)->toEqual(['php']);
});

test('adds tag to published page and regenerates site', function () {
    $repository = initializeSite();

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $page = new Page(
        title: 'Published Tag Test',
        path: new PagePath('published-tag-test'),
        content: new Markdown('# Content'),
        created_at: Carbon::now(),
        published_at: Carbon::parse('2025-01-01 10:00:00'),
        tags: [],
    );
    $repository->save($page);

    $siteGenerator = mock(SiteGenerator::class, function ($mock) {
        $mock->shouldReceive('generatePage')->once();
        $mock->shouldReceive('regenerateIndex')->once();
    });

    $action = new AddPageTag(
        repository: app(PageRepository::class),
        siteGenerator: $siteGenerator,
    );

    $result = $action->__invoke('published-tag-test', 'new-tag');

    Carbon::setTestNow(null);

    expect($result->tags)->toEqual(['new-tag']);
});
