<?php

use App\Domain\Editor\Type\DeleteType;
use App\Domain\Generator\SiteGenerator;

use function Pest\Laravel\mock;

test('deletes the type and removes it from every page that has it', function () {
    aType('Note');
    aPage('First Note', 'first-note', type: 'note');
    aPage('Second Note', 'nested/second-note', type: 'note');
    aPage('A Post', 'a-post', type: 'post');

    app(DeleteType::class)('note');

    expect('types/note.md')->toBeMissingFromDisk()
        ->and(repository()->findByPath('first-note')->type)->toBeNull()
        ->and(repository()->findByPath('nested/second-note')->type)->toBeNull()
        ->and(repository()->findByPath('a-post')->type)->toBe('post');
});

test('regenerates the published pages that had the type and the index', function () {
    aType('Note');
    aPublishedPage('Published Note', 'published-note', type: 'note');
    aPage('Draft Note', 'draft-note', type: 'note');

    mock(SiteGenerator::class, function ($mock) {
        $mock->shouldReceive('generatePage')->once()->with('published-note');
        $mock->shouldReceive('regenerateIndex')->once();
    });

    app(DeleteType::class)('note');
});

test('leaves the generated site alone when no page has the type', function () {
    aType('Note');
    aPublishedPage('A Post', 'a-post', type: 'post');

    mock(SiteGenerator::class, function ($mock) {
        $mock->shouldNotReceive('generatePage');
        $mock->shouldNotReceive('regenerateIndex');
    });

    app(DeleteType::class)('note');

    expect('types/note.md')->toBeMissingFromDisk()
        ->and(repository()->findByPath('a-post')->type)->toBe('post');
});
