<?php

use App\Domain\Editor\Tag\Tag;
use Carbon\Carbon;

test('creates a tag whose slug is its id, with empty content and the current date', function () {
    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $tag = Tag::create('cosas-varias', 'Cosas varias');

    expect($tag->id)->toBe('cosas-varias')
        ->and($tag->name)->toBe('Cosas varias')
        ->and($tag->slug)->toBe('cosas-varias')
        ->and((string) $tag->content)->toBe('')
        ->and($tag->cover_image)->toBeNull()
        ->and($tag->created_at->toIso8601String())->toBe(Carbon::now()->toIso8601String());
});

test('is stored in a file named after its id', function () {
    expect(Tag::create('cosas-varias', 'Cosas varias')->filename())->toBe('cosas-varias.md');
});

test('the slug follows the name while it is the one generated from it', function () {
    $tag = Tag::create('larvel', 'Larvel');

    $tag->rename('Laravel');

    expect($tag->name)->toBe('Laravel')
        ->and($tag->slug)->toBe('laravel')
        ->and($tag->id)->toBe('larvel');
});

test('a custom slug is kept when the tag is renamed', function () {
    $tag = Tag::create('laravel', 'Laravel');
    $tag->changeSlug('framework');

    $tag->rename('Laravel framework');

    expect($tag->slug)->toBe('framework');
});

test('its page lives under the tag pages path and its assets under the tags directory', function () {
    config()->set('pluma.tags.pages_path', 'etiquetas');

    $tag = Tag::create('laravel', 'Laravel');
    $tag->changeSlug('framework');

    expect($tag->path)->toBe('etiquetas/framework')
        ->and((string) $tag->assetsPath())->toBe('tags/laravel');
});
