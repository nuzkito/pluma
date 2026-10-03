<?php

use App\Domain\Editor\Page\UpdatePagePublishedAt;
use App\Domain\Generator\SiteGenerator;
use Carbon\Carbon;

test('changes the published_at of a draft without publishing it', function () {
    aPage('Draft Date Test', 'draft-date-test');

    app(UpdatePagePublishedAt::class)('draft-date-test', '2025-06-15T14:30');

    $updated = repository()->findByPath('draft-date-test');

    expect($updated->isDraft())->toBeTrue()
        ->and($updated->published_at?->format('Y-m-d H:i'))->toBe('2025-06-15 14:30')
        ->and('site/draft-date-test')->toBeMissingFromDisk();
});

test('clears the published_at of a draft', function () {
    aPage('Draft Clear Test', 'draft-clear-test', published_at: Carbon::parse('2025-06-15 14:30:00'));

    app(UpdatePagePublishedAt::class)('draft-clear-test', null);

    expect(repository()->findByPath('draft-clear-test')->published_at)->toBeNull();
});

test('keeps the published_at of a published page when it is cleared', function () {
    aPublishedPage(
        'Published Clear Test',
        'published-clear-test',
        published_at: Carbon::parse('2025-06-15 14:30:00'),
    );

    $page = app(UpdatePagePublishedAt::class)('published-clear-test', null);

    $updated = repository()->findByPath('published-clear-test');

    expect($page->published_at?->format('Y-m-d H:i'))->toBe('2025-06-15 14:30')
        ->and($updated->isPublished())->toBeTrue()
        ->and($updated->published_at?->format('Y-m-d H:i'))->toBe('2025-06-15 14:30');
});

test('regenerates a published page with its new published_at', function () {
    aPublishedPage('Site Date Test', 'site-date-test', content: '# Content');

    app(SiteGenerator::class)->generatePage('site-date-test');

    app(UpdatePagePublishedAt::class)('site-date-test', '2025-06-15T14:30');

    expect(disk()->get('site/site-date-test/index.html'))->toContain('2025-06-15');
});
