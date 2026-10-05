<?php

use App\Domain\Editor\Page\UpdatePageRevisedAt;
use Carbon\Carbon;

test('changes the revised_at of a page', function () {
    aPublishedPage('Revised Date Test', 'revised-date-test');

    $page = app(UpdatePageRevisedAt::class)('revised-date-test', '2025-07-01T09:00');

    expect($page->revised_at?->format('Y-m-d H:i'))->toBe('2025-07-01 09:00')
        ->and(repository()->findByPath('revised-date-test')->revised_at?->format('Y-m-d H:i'))->toBe('2025-07-01 09:00');
});

test('clears the revised_at of a page', function () {
    aPublishedPage('Revised Clear Test', 'revised-clear-test', revised_at: Carbon::parse('2025-07-01 09:00:00'));

    app(UpdatePageRevisedAt::class)('revised-clear-test', null);

    expect(repository()->findByPath('revised-clear-test')->revised_at)->toBeNull();
});

test('regenerates a published page with its new revised_at', function () {
    aPublishedPage('Site Revised Test', 'site-revised-test', content: '# Content');

    app(UpdatePageRevisedAt::class)('site-revised-test', '2025-07-01T09:00');

    expect(disk()->get('site/site-revised-test/index.html'))->toContain('Updated on <time datetime="2025-07-01">');
});
