<?php

use App\Domain\Editor\Page\UpdatePageType;
use App\Domain\Generator\SiteGenerator;

use function Pest\Laravel\mock;

test('sets the type on a page', function () {
    aPage('Type Test', 'type-test');

    app(UpdatePageType::class)('type-test', 'article');

    expect(repository()->findByPath('type-test')->type)->toBe('article');
});

test('trims the surrounding whitespace of the type', function () {
    aPage('Trimmed Type Test', 'trimmed-type-test');

    app(UpdatePageType::class)('trimmed-type-test', '  article  ');

    expect(repository()->findByPath('trimmed-type-test')->type)->toBe('article');
});

test('removes the type when it is blank', function (?string $type) {
    aPage('Blank Type Test', 'blank-type-test', type: 'article');

    app(UpdatePageType::class)('blank-type-test', $type);

    expect(repository()->findByPath('blank-type-test')->type)->toBeNull()
        ->and(disk()->get('pages/blank-type-test.md'))->not->toContain('type:');
})->with(['empty' => '', 'spaces' => '   ', 'null' => null]);

test('regenerates the page and the index when the page is published', function () {
    aPublishedPage('Published Type Test', 'published-type-test');

    mock(SiteGenerator::class, function ($mock) {
        $mock->shouldReceive('generatePage')->once()->with('published-type-test');
        $mock->shouldReceive('regenerateIndex')->once();
    });

    app(UpdatePageType::class)('published-type-test', 'article');
});

test('leaves the generated site alone when the page is a draft', function () {
    aPage('Draft Type Test', 'draft-type-test');

    mock(SiteGenerator::class, function ($mock) {
        $mock->shouldNotReceive('generatePage');
        $mock->shouldNotReceive('regenerateIndex');
    });

    app(UpdatePageType::class)('draft-type-test', 'article');

    expect(repository()->findByPath('draft-type-test')->type)->toBe('article');
});
