<?php

use App\Domain\Editor\Page\UpdatePageType;
use App\Domain\Generator\SiteGenerator;

use function Pest\Laravel\mock;

test('stores the id of the type with that name on the page', function () {
    aPage('Type Test', 'type-test');

    app(UpdatePageType::class)('type-test', '  post  ');

    expect(repository()->findByPath('type-test')->type)->toBe('post')
        ->and(types()->all())->toHaveCount(2);
});

test('creates the type when no type has that name', function () {
    aPage('New Type Test', 'new-type-test');

    app(UpdatePageType::class)('new-type-test', 'Recipe');

    expect(repository()->findByPath('new-type-test')->type)->toBe('recipe')
        ->and(types()->find('recipe')->name)->toBe('Recipe');
});

test('removes the type when it is blank', function (?string $type) {
    aPage('Blank Type Test', 'blank-type-test', type: 'post');

    app(UpdatePageType::class)('blank-type-test', $type);

    expect(repository()->findByPath('blank-type-test')->type)->toBeNull()
        ->and(disk()->get('pages/blank-type-test.md'))->not->toContain('type:')
        ->and(types()->all())->toHaveCount(2);
})->with(['empty' => '', 'spaces' => '   ', 'null' => null]);

test('regenerates the page and the index when the page is published', function () {
    aPublishedPage('Published Type Test', 'published-type-test');

    mock(SiteGenerator::class, function ($mock) {
        $mock->shouldReceive('generatePage')->once()->with('published-type-test');
        $mock->shouldReceive('regenerateIndex')->once();
    });

    app(UpdatePageType::class)('published-type-test', 'Post');
});

test('leaves the generated site alone when the page is a draft', function () {
    aPage('Draft Type Test', 'draft-type-test');

    mock(SiteGenerator::class, function ($mock) {
        $mock->shouldNotReceive('generatePage');
        $mock->shouldNotReceive('regenerateIndex');
    });

    app(UpdatePageType::class)('draft-type-test', 'Post');

    expect(repository()->findByPath('draft-type-test')->type)->toBe('post');
});
