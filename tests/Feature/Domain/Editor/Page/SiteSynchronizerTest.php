<?php

use App\Domain\Editor\Page\CreateTagPage;
use App\Domain\Editor\Page\PagePath;
use App\Domain\Editor\Page\SiteSynchronizer;
use App\Domain\Generator\SiteGenerator;

use function Pest\Laravel\mock;

test('refresh generates a published page and the index', function () {
    $page = aPublishedPage('Refresh Test', 'refresh-test', content: '# Content');

    app(SiteSynchronizer::class)->refresh($page);

    expect('site/refresh-test/index.html')->toExistOnDisk()
        ->and(disk()->get('site/index.html'))->toContain('Refresh Test');
});

test('refresh leaves the site untouched for a draft', function () {
    $page = aPage('Draft Refresh Test', 'draft-refresh-test');

    mock(SiteGenerator::class, function ($mock) {
        $mock->shouldNotReceive('generatePage');
        $mock->shouldNotReceive('removePage');
        $mock->shouldNotReceive('regenerateIndex');
    });

    app(SiteSynchronizer::class)->refresh($page);

    expect('site/draft-refresh-test')->toBeMissingFromDisk();
});

test('refresh also generates the page of each given tag', function () {
    config()->set('pluma.tags.create_pages', true);

    app(CreateTagPage::class)('Laravel');

    $page = aPublishedPage('Tagged Refresh Test', 'tagged-refresh-test', tags: ['Laravel']);

    app(SiteSynchronizer::class)->refresh($page, 'Laravel');

    expect(disk()->get('site/tags/laravel/index.html'))->toContain('Tagged Refresh Test');
});

test('refreshOrWithdraw generates a published page', function () {
    $page = aPublishedPage('Applied Test', 'applied-test', content: '# Content');

    app(SiteSynchronizer::class)->refreshOrWithdraw($page);

    expect('site/applied-test/index.html')->toExistOnDisk();
});

test('refreshOrWithdraw removes a draft from the site', function () {
    $page = aPublishedPage('Withdrawn Test', 'withdrawn-test', content: '# Content');

    app(SiteSynchronizer::class)->refresh($page);

    expect('site/withdrawn-test/index.html')->toExistOnDisk();

    $page->unpublish();
    repository()->save($page);

    app(SiteSynchronizer::class)->refreshOrWithdraw($page);

    expect('site/withdrawn-test')->toBeMissingFromDisk()
        ->and(disk()->get('site/index.html'))->not->toContain('Withdrawn Test');
});

test('move removes the old path and generates the new one', function () {
    $page = aPublishedPage('Move Test', 'move-test', content: '# Content');

    app(SiteSynchronizer::class)->refresh($page);

    $page->moveToPath(new PagePath('moved-test'));
    repository()->save($page, 'move-test');

    app(SiteSynchronizer::class)->move($page, 'move-test');

    expect('site/moved-test/index.html')->toExistOnDisk()
        ->and('site/move-test')->toBeMissingFromDisk();
});

test('move leaves the site untouched for a draft', function () {
    $page = aPage('Draft Move Test', 'draft-move-test');

    mock(SiteGenerator::class, function ($mock) {
        $mock->shouldNotReceive('generatePage');
        $mock->shouldNotReceive('removePage');
        $mock->shouldNotReceive('regenerateIndex');
    });

    app(SiteSynchronizer::class)->move($page, 'somewhere-else');
});

test('withdraw removes the page and refreshes the index', function () {
    $page = aPublishedPage('Withdraw Test', 'withdraw-test', content: '# Content');

    app(SiteSynchronizer::class)->refresh($page);

    expect('site/withdraw-test/index.html')->toExistOnDisk();

    app(SiteSynchronizer::class)->withdraw('withdraw-test');

    expect('site/withdraw-test')->toBeMissingFromDisk();
});

test('refreshIndex rebuilds the index without touching any page', function () {
    aPublishedPage('Index Only Test', 'index-only-test', content: '# Content');

    mock(SiteGenerator::class, function ($mock) {
        $mock->shouldNotReceive('generatePage');
        $mock->shouldReceive('regenerateIndex')->once();
    });

    app(SiteSynchronizer::class)->refreshIndex();
});
