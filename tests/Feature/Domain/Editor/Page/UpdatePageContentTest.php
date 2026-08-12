<?php

use App\Domain\Editor\Page\UpdatePageContent;
use App\Domain\Generator\SiteGenerator;

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
