<?php

use App\Domain\Editor\Page\UpdatePageRss;
use App\Domain\Generator\SiteGenerator;

use function Pest\Laravel\mock;

test('enables RSS when enabled is true', function () {
    aPage('RSS Enable Test', 'rss-enable-test', content: '# Content');

    $action = app(UpdatePageRss::class);

    $action('rss-enable-test', true);

    expect(repository()->findByPath('rss-enable-test')->rss)->toBeTrue();
});

test('disables RSS when enabled is false', function () {
    aPage('RSS Disable Test', 'rss-disable-test', content: '# Content', rss: true);

    $action = app(UpdatePageRss::class);

    $action('rss-disable-test', false);

    expect(repository()->findByPath('rss-disable-test')->rss)->toBeFalse();
});

test('refreshes the index without regenerating the page', function () {
    aPublishedPage('RSS Index Test', 'rss-index-test', content: '# Content');

    mock(SiteGenerator::class, function ($mock) {
        $mock->shouldNotReceive('generatePage');
        $mock->shouldReceive('regenerateIndex')->once();
    });

    app(UpdatePageRss::class)('rss-index-test', true);

    expect(repository()->findByPath('rss-index-test')->rss)->toBeTrue();
});
