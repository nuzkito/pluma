<?php

use App\Domain\Editor\Tag\MoveTagPages;
use App\Domain\Generator\SiteGenerator;
use Carbon\Carbon;

test('leaves the files and the assets of the tags in place', function () {
    aTag('Laravel', content: 'My description');
    disk()->put('assets/tags/laravel/cover.png', 'image');

    $result = app(MoveTagPages::class)('tags', 'etiquetas');

    expect($result)->toBeOk()
        ->and(disk()->get('tags/laravel.md'))->toContain('My description')
        ->and('assets/tags/laravel/cover.png')->toExistOnDisk()
        ->and('assets/etiquetas')->toBeMissingFromDisk()
        ->and('pages/etiquetas')->toBeMissingFromDisk();
});

test('removes the generated tag pages from the old directory', function () {
    config()->set('pluma.tags.create_pages', true);

    aTag('Laravel', slug: 'framework');

    app(SiteGenerator::class)->generateTagPage('laravel');

    expect('site/tags/framework/index.html')->toExistOnDisk();

    app(MoveTagPages::class)('tags', 'etiquetas');

    expect('site/tags')->toBeMissingFromDisk();
});

test('keeps the old directory of the site when it still holds other pages', function () {
    aTag('Laravel');
    aPublishedPage('Kept', 'tags/kept', content: '# Kept', created_at: Carbon::parse('2025-01-01'));

    app(SiteGenerator::class)->generatePage('tags/kept');

    app(MoveTagPages::class)('tags', 'etiquetas');

    expect('site/tags/kept/index.html')->toExistOnDisk();
});

test('returns an error when the new directory already exists', function () {
    aTag('Laravel');
    disk()->put('assets/tags/laravel/cover.png', 'image');
    disk()->makeDirectory('pages/etiquetas');

    $result = app(MoveTagPages::class)('tags', 'etiquetas');

    expect($result)->toBeError('A page or directory with this path already exists.')
        ->and('assets/tags/laravel/cover.png')->toExistOnDisk();
});

test('returns an error when a page already uses the new path', function () {
    aTag('Laravel');
    aPage('Etiquetas', 'etiquetas');

    expect(app(MoveTagPages::class)('tags', 'etiquetas'))->toBeError();
});

test('does nothing when the path does not change', function () {
    aTag('Laravel');
    disk()->put('assets/tags/laravel/cover.png', 'image');

    expect(app(MoveTagPages::class)('/tags/', 'tags'))->toBeOk()
        ->and('assets/tags/laravel/cover.png')->toExistOnDisk();
});
