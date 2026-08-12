<?php

use App\Domain\Editor\Page\ChangePageCoverImage;
use App\Domain\Generator\SiteGenerator;

use function Pest\Laravel\mock;

test('sets the cover image on a page', function () {
    aPage('Cover Test', 'cover-test');

    $action = app(ChangePageCoverImage::class);

    $action('cover-test', 'header.png');

    expect(repository()->findByPath('cover-test')->cover_image)->toBe('header.png');
});

test('replaces an existing cover image', function () {
    aPage('Cover Replace Test', 'cover-replace-test', content: '# Content', cover_image: 'old.png');

    $action = app(ChangePageCoverImage::class);

    $action('cover-replace-test', 'new.png');

    expect(repository()->findByPath('cover-replace-test')->cover_image)->toBe('new.png');
});

test('regenerates the page and the index when the page is published', function () {
    aPublishedPage('Published Cover Test', 'published-cover-test', content: '# Content');

    app(ChangePageCoverImage::class)('published-cover-test', 'header.png');

    expect('site/published-cover-test/index.html')->toExistOnDisk()
        ->and('site/index.html')->toExistOnDisk();
});

test('leaves the generated site alone when the page is a draft', function () {
    aPage('Draft Cover Test', 'draft-cover-test');

    mock(SiteGenerator::class, function ($mock) {
        $mock->shouldNotReceive('generatePage');
        $mock->shouldNotReceive('regenerateIndex');
    });

    app(ChangePageCoverImage::class)('draft-cover-test', 'header.png');

    expect(repository()->findByPath('draft-cover-test')->cover_image)->toBe('header.png');
});
