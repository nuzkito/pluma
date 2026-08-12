<?php

use App\Domain\Editor\Page\UpdatePagePublishedAt;
use App\Domain\Generator\SiteGenerator;
use Carbon\Carbon;

test('publishes page when published_at is provided', function () {
    $page = aPage('Publish Test', 'publish-test');

    expect($page->isDraft())->toBeTrue();

    $action = app(UpdatePagePublishedAt::class);

    $action('publish-test', '2025-06-15T14:30');

    expect(repository()->findByPath('publish-test')->isPublished())->toBeTrue()
        ->and(repository()->findByPath('publish-test')->published_at?->format('Y-m-d H:i'))->toBe('2025-06-15 14:30');
});

test('unpublishes page when published_at is null', function () {
    aPublishedPage(
        'Unpublish Test',
        'unpublish-test',
        content: '# Content',
        published_at: Carbon::parse('2025-06-15 14:30:00'),
    );

    $action = app(UpdatePagePublishedAt::class);

    $action('unpublish-test', null);

    expect(repository()->findByPath('unpublish-test')->isDraft())->toBeTrue()
        ->and(repository()->findByPath('unpublish-test')->published_at)->toBeNull();
});

test('generates the page in the site when it is published', function () {
    aPage('Site Publish Test', 'site-publish-test');

    app(UpdatePagePublishedAt::class)('site-publish-test', '2025-06-15T14:30');

    expect('site/site-publish-test/index.html')->toExistOnDisk()
        ->and(disk()->get('site/index.html'))->toContain('Site Publish Test');
});

test('removes the page from the site when it is unpublished', function () {
    aPublishedPage('Site Unpublish Test', 'site-unpublish-test', content: '# Content');

    app(SiteGenerator::class)->generatePage('site-unpublish-test');

    app(UpdatePagePublishedAt::class)('site-unpublish-test', null);

    expect('site/site-unpublish-test')->toBeMissingFromDisk()
        ->and(disk()->get('site/index.html'))->not->toContain('Site Unpublish Test');
});
