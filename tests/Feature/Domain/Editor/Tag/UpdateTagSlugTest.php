<?php

use App\Domain\Editor\Tag\UpdateTagSlug;

test('changes the slug without changing the id or the name', function () {
    aTag('Laravel');

    $result = app(UpdateTagSlug::class)('laravel', 'framework');

    $tag = tags()->find('laravel');

    expect($result)->toBeOk()
        ->and($tag->slug)->toBe('framework')
        ->and($tag->name)->toBe('Laravel');
});

test('moves the tag page and updates the links of the pages tagged with it', function () {
    config()->set('pluma.tags.create_pages', true);

    aTag('Laravel');
    aPublishedPage('Tagged Post', 'tagged-post', tags: ['laravel']);

    app(UpdateTagSlug::class)('laravel', 'framework');

    expect('site/tags/framework/index.html')->toExistOnDisk()
        ->and('site/tags/laravel')->toBeMissingFromDisk()
        ->and(disk()->get('site/tagged-post/index.html'))->toContain('/tags/framework/');
});

test('ignores the surrounding spaces', function () {
    aTag('Laravel');

    app(UpdateTagSlug::class)('laravel', ' framework ');

    expect(tags()->find('laravel')->slug)->toBe('framework');
});

test('rejects a slug with characters other than letters, numbers and hyphens', function (string $slug) {
    aTag('Laravel');

    expect(app(UpdateTagSlug::class)('laravel', $slug))->toBeError('The slug may only contain letters, numbers and hyphens.')
        ->and(tags()->find('laravel')->slug)->toBe('laravel');
})->with(['', '/', 'php/laravel', '..', '.', 'my tag', '/framework/']);

test('rejects the slug of another tag', function () {
    aTag('Laravel');
    aTag('PHP', slug: 'framework');

    expect(app(UpdateTagSlug::class)('laravel', 'framework'))->toBeError('Another tag already uses this slug.')
        ->and(tags()->find('laravel')->slug)->toBe('laravel');
});
