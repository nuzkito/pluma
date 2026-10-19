<?php

use App\Domain\Editor\Page\DeleteType;
use App\Domain\Generator\SiteGenerator;

use function Pest\Laravel\mock;

test('removes the type from every page that has it', function () {
    aPage('First Note', 'first-note', type: 'note');
    aPage('Second Note', 'nested/second-note', type: 'note');
    aPage('An Article', 'an-article', type: 'article');

    app(DeleteType::class)('note');

    expect(repository()->findByPath('first-note')->type)->toBeNull()
        ->and(repository()->findByPath('nested/second-note')->type)->toBeNull()
        ->and(repository()->findByPath('an-article')->type)->toBe('article');
});

test('regenerates the published pages that had the type and the index', function () {
    aPublishedPage('Published Note', 'published-note', type: 'note');
    aPage('Draft Note', 'draft-note', type: 'note');

    mock(SiteGenerator::class, function ($mock) {
        $mock->shouldReceive('generatePage')->once()->with('published-note');
        $mock->shouldReceive('regenerateIndex')->once();
    });

    app(DeleteType::class)('note');
});

test('leaves the generated site alone when no page has the type', function () {
    aPublishedPage('Published Article', 'published-article', type: 'article');

    mock(SiteGenerator::class, function ($mock) {
        $mock->shouldNotReceive('generatePage');
        $mock->shouldNotReceive('regenerateIndex');
    });

    app(DeleteType::class)('note');

    expect(repository()->findByPath('published-article')->type)->toBe('article');
});
