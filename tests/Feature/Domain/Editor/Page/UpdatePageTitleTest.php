<?php

use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\UpdatePageTitle;
use App\Domain\Generator\SiteGenerator;

use function Pest\Laravel\mock;

test('returns Ok with page when title is updated successfully', function () {
    aPage('Original Title', 'original-title');

    $action = app(UpdatePageTitle::class);

    $result = $action('original-title', 'New Title');

    expect($result)->toBeOk()
        ->and($result->unwrap())->toBeInstanceOf(ContentPage::class);
});

test('returns Ok with page when title is updated without path change', function () {
    aPage('Original Title', 'custom-path', content: '# Content');

    $action = app(UpdatePageTitle::class);

    $result = $action('custom-path', 'New Title');

    expect($result)->toBeOk()
        ->and(repository()->findByPath('custom-path')->title)->toBe('New Title');
});

test('returns Error when new title generates conflicting slug with another page', function () {
    aPage('Existing Title', 'existing-title');

    aPage('Different Title', 'different-title', content: '# Content');

    $action = app(UpdatePageTitle::class);

    $result = $action('different-title', 'Existing Title');

    expect($result)->toBeError('This title generates the same slug as another page that already exists.');
});

test('moves a published page in the generated site when the slug changes', function () {
    aPublishedPage('Original Site Title', 'original-site-title', content: '# Content');

    app(SiteGenerator::class)->generatePage('original-site-title');

    app(UpdatePageTitle::class)('original-site-title', 'Renamed Site Title');

    expect('site/renamed-site-title/index.html')->toExistOnDisk()
        ->and(disk()->get('site/index.html'))->toContain('Renamed Site Title');
});

test('does not generate the page in the site when it is a draft', function () {
    aPage('Draft Site Title', 'draft-site-title');

    mock(SiteGenerator::class, function ($mock) {
        $mock->shouldNotReceive('generatePage');
        $mock->shouldReceive('removePage')->once();
        $mock->shouldReceive('regenerateIndex')->zeroOrMoreTimes();
    });

    expect(app(UpdatePageTitle::class)('draft-site-title', 'Renamed Draft Title'))->toBeOk();
});
