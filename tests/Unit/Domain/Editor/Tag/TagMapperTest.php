<?php

use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Tag\Tag;
use App\Domain\Editor\Tag\TagMapper;
use Carbon\Carbon;

test('serializes name, slug, cover image and created_at', function () {
    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $tag = Tag::create('laravel', 'Laravel');
    $tag->changeCoverImage('header.png');

    expect((new TagMapper)->toFrontMatter($tag))->toBe([
        'name' => 'Laravel',
        'slug' => 'laravel',
        'cover_image' => 'header.png',
        'created_at' => Carbon::now()->toIso8601String(),
    ]);
});

test('rebuilds a tag from its id, front matter and content', function () {
    $tag = (new TagMapper)->fromFrontMatter('laravel', [
        'name' => 'Laravel',
        'slug' => 'framework',
        'cover_image' => 'header.png',
        'created_at' => '2025-01-01T10:00:00+00:00',
    ], new Markdown('Body'));

    expect($tag->id)->toBe('laravel')
        ->and($tag->name)->toBe('Laravel')
        ->and($tag->slug)->toBe('framework')
        ->and($tag->cover_image)->toBe('header.png')
        ->and((string) $tag->content)->toBe('Body')
        ->and($tag->created_at->toIso8601String())->toBe('2025-01-01T10:00:00+00:00');
});

test('falls back to the id for a missing name and slug', function () {
    $tag = (new TagMapper)->fromFrontMatter('laravel', ['created_at' => '2025-01-01T10:00:00+00:00'], new Markdown(''));

    expect($tag->name)->toBe('laravel')
        ->and($tag->slug)->toBe('laravel')
        ->and($tag->cover_image)->toBeNull();
});
