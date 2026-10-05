<?php

use App\Domain\Editor\Page\UpdatePageContent;
use App\Domain\Generator\SiteGenerator;
use Carbon\Carbon;

use function Pest\Laravel\mock;

test('updates page content successfully', function () {
    aPage('Content Update Test', 'content-test', content: '# Old Content');

    $action = app(UpdatePageContent::class);

    $action('content-test', '# New Content');

    expect((string) repository()->findByPath('content-test')->content)->toBe('# New Content');
});

test('clears content when empty string is provided', function () {
    aPage('Clear Content Test', 'clear-content-test', content: '# Has Content');

    $action = app(UpdatePageContent::class);

    $action('clear-content-test', '');

    expect((string) repository()->findByPath('clear-content-test')->content)->toBe('');
});

test('regenerates the page and the index when the page is published', function () {
    aPublishedPage('Published Content Test', 'published-content-test', content: '# Old Content');

    app(UpdatePageContent::class)('published-content-test', '# New Content');

    expect(disk()->get('site/published-content-test/index.html'))->toContain('New Content')
        ->and(disk()->get('site/index.html'))->toContain('Published Content Test');
});

test('leaves the generated site alone when the page is a draft', function () {
    aPage('Draft Content Test', 'draft-content-test');

    mock(SiteGenerator::class, function ($mock) {
        $mock->shouldNotReceive('generatePage');
        $mock->shouldNotReceive('regenerateIndex');
    });

    app(UpdatePageContent::class)('draft-content-test', '# New Content');

    expect((string) repository()->findByPath('draft-content-test')->content)->toBe('# New Content');
});

test('sets the revision date when the content of a published page changes', function () {
    Carbon::setTestNow(Carbon::parse('2025-07-01 09:00:00'));

    aPublishedPage('Revised Content Test', 'revised-content-test', content: '# Old Content', published_at: Carbon::parse('2025-06-01 12:00:00'));

    app(UpdatePageContent::class)('revised-content-test', '# New Content');

    expect(repository()->findByPath('revised-content-test')->revised_at?->format('Y-m-d H:i'))->toBe('2025-07-01 09:00');
});

test('does not set the revision date when the content does not change', function () {
    Carbon::setTestNow(Carbon::parse('2025-07-01 09:00:00'));

    aPublishedPage('Unchanged Content Test', 'unchanged-content-test', content: '# Same Content', published_at: Carbon::parse('2025-06-01 12:00:00'));

    app(UpdatePageContent::class)('unchanged-content-test', '# Same Content');

    expect(repository()->findByPath('unchanged-content-test')->revised_at)->toBeNull();
});

test('does not set the revision date when the content changes on the day of publication', function () {
    Carbon::setTestNow(Carbon::parse('2025-07-01 18:00:00'));

    aPublishedPage('Same Day Test', 'same-day-test', content: '# Old Content', published_at: Carbon::parse('2025-07-01 09:00:00'));

    app(UpdatePageContent::class)('same-day-test', '# New Content');

    expect(repository()->findByPath('same-day-test')->revised_at)->toBeNull();
});

test('does not set the revision date when the content of a draft changes', function () {
    aPage('Draft Revision Test', 'draft-revision-test');

    app(UpdatePageContent::class)('draft-revision-test', '# New Content');

    expect(repository()->findByPath('draft-revision-test')->revised_at)->toBeNull();
});

test('sets the revision date when the content of a tag page changes', function () {
    Carbon::setTestNow(Carbon::parse('2025-07-01 09:00:00'));

    $tagPage = aTagPage('Laravel', created_at: Carbon::parse('2025-06-01 12:00:00'));

    app(UpdatePageContent::class)((string) $tagPage->path, '# New Content');

    expect(repository()->findByPath((string) $tagPage->path)->revised_at?->format('Y-m-d H:i'))->toBe('2025-07-01 09:00');
});
