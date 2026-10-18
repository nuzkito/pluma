<?php

use App\Domain\Editor\Tag\DeleteTag;
use App\Domain\Generator\SiteGenerator;
use Illuminate\Http\UploadedFile;

test('deletes the tag file and its assets', function () {
    aTag('Laravel');
    disk()->putFileAs('assets/tags/laravel', UploadedFile::fake()->image('cover.png'), 'cover.png');

    app(DeleteTag::class)('laravel');

    expect(tags()->find('laravel'))->toBeNull()
        ->and('tags/laravel.md')->toBeMissingFromDisk()
        ->and('assets/tags/laravel')->toBeMissingFromDisk();
});

test('removes the tag from every page that uses it, in any directory', function () {
    aTag('Laravel');
    aPage('Draft', 'draft', tags: ['php', 'laravel']);
    aPublishedPage('Nested Post', 'posts/nested-post', tags: ['laravel', 'testing']);
    aPage('Untagged', 'untagged', tags: ['php']);

    app(DeleteTag::class)('laravel');

    expect(repository()->findByPath('draft')->tags)->toBe(['php'])
        ->and(repository()->findByPath('posts/nested-post')->tags)->toBe(['testing'])
        ->and(repository()->findByPath('untagged')->tags)->toBe(['php']);
});

test('removes the tag page and regenerates the pages that were tagged with it', function () {
    config()->set('pluma.tags.create_pages', true);

    aTag('Laravel');
    aPublishedPage('Tagged Post', 'tagged-post', tags: ['laravel']);
    app(SiteGenerator::class)->generateAll();

    expect('site/tags/laravel/index.html')->toExistOnDisk()
        ->and(disk()->get('site/tagged-post/index.html'))->toContain('/tags/laravel/');

    app(DeleteTag::class)('laravel');

    expect('site/tags/laravel')->toBeMissingFromDisk()
        ->and(disk()->get('site/tagged-post/index.html'))->not->toContain('/tags/laravel/');
});
