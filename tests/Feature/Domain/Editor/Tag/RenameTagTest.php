<?php

use App\Domain\Editor\Tag\RenameTag;

test('renames the tag without changing its id', function () {
    aTag('Larvel');

    $result = app(RenameTag::class)('larvel', 'Laravel');

    expect($result)->toBeOk()
        ->and(tags()->find('larvel')->name)->toBe('Laravel');
});

test('regenerates the pages tagged with it so they show the new name', function () {
    aTag('PHP', id: 'php');
    aPublishedPage('Tagged Post', 'tagged-post', tags: ['php']);
    aPublishedPage('Nested Post', 'posts/nested-post', tags: ['php']);

    app(RenameTag::class)('php', 'PHP 8');

    expect(disk()->get('site/tagged-post/index.html'))->toContain('PHP 8')
        ->and(disk()->get('site/posts/nested-post/index.html'))->toContain('PHP 8');
});

test('moves the tag page when the slug follows the name', function () {
    config()->set('pluma.tags.create_pages', true);

    aTag('Larvel');
    aPublishedPage('Tagged Post', 'tagged-post', tags: ['larvel']);

    app(RenameTag::class)('larvel', 'Laravel');

    expect(tags()->find('larvel')->slug)->toBe('laravel')
        ->and('site/tags/laravel/index.html')->toExistOnDisk()
        ->and('site/tags/larvel')->toBeMissingFromDisk()
        ->and(disk()->get('site/tagged-post/index.html'))->toContain('/tags/laravel/');
});

test('keeps a custom slug', function () {
    aTag('Laravel', slug: 'framework');

    app(RenameTag::class)('laravel', 'Laravel framework');

    expect(tags()->find('laravel')->slug)->toBe('framework');
});

test('rejects an empty name', function () {
    aTag('Laravel');

    expect(app(RenameTag::class)('laravel', '  '))->toBeError('The name of a tag cannot be empty.')
        ->and(tags()->find('laravel')->name)->toBe('Laravel');
});

test('rejects the name of another tag', function () {
    aTag('Laravel');
    aTag('PHP');

    expect(app(RenameTag::class)('php', 'laravel'))->toBeError('Another tag already has this name.')
        ->and(tags()->find('php')->name)->toBe('PHP');
});

test('rejects a name that generates the slug of another tag', function () {
    aTag('Laravel');
    aTag('Larvel');

    expect(app(RenameTag::class)('larvel', 'Laravel!'))->toBeError('This name generates the same slug as another tag that already exists.')
        ->and(tags()->find('larvel')->name)->toBe('Larvel');
});
