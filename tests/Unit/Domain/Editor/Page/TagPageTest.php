<?php

use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Page\TagPage;
use Carbon\Carbon;

test('creates a tag page with slug path, empty content and current date', function () {
    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $tagPage = TagPage::create('Cosas varias');

    expect($tagPage->title)->toBe('Cosas varias')
        ->and((string) $tagPage->path)->toBe('tags/cosas-varias')
        ->and((string) $tagPage->content)->toBe('')
        ->and($tagPage->cover_image)->toBeNull()
        ->and($tagPage->created_at->toIso8601String())->toBe(Carbon::now()->toIso8601String());
});

test('builds the filename with the tag suffix', function () {
    $tagPage = TagPage::create('Cosas varias');

    expect($tagPage->filename())->toBe('tags/cosas-varias.tag.md');
});

test('serializes title, path, cover image and created_at to the frontmatter', function () {
    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $tagPage = TagPage::create('Laravel');
    $tagPage->changeCoverImage('header.png');

    expect($tagPage->toArray())->toBe([
        'title' => 'Laravel',
        'path' => 'tags/laravel',
        'cover_image' => 'header.png',
        'created_at' => Carbon::now()->toIso8601String(),
    ]);
});

test('is never included in the rss feed', function () {
    expect(TagPage::create('Laravel')->rss)->toBeFalse();
});

test('never has tags of its own', function () {
    expect(TagPage::create('Laravel')->tags)->toBe([]);
});

test('the rss and tags properties cannot be written', function () {
    $tagPage = TagPage::create('Laravel');

    expect(fn () => $tagPage->rss = true)->toThrow(Error::class)
        ->and(fn () => $tagPage->tags = ['php'])->toThrow(Error::class);
});

test('is published at its creation date', function () {
    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $tagPage = TagPage::create('Laravel');

    expect($tagPage->published_at)->toEqual($tagPage->created_at)
        ->and($tagPage->isPublished())->toBeTrue();
});

test('updates its content', function () {
    $tagPage = TagPage::create('Laravel');

    $tagPage->setContent(new Markdown('# Everything about Laravel'));

    expect((string) $tagPage->content)->toBe('# Everything about Laravel');
});

test('changes and removes its cover image', function () {
    $tagPage = TagPage::create('Laravel');

    $tagPage->changeCoverImage('header.png');

    expect($tagPage->cover_image)->toBe('header.png');

    $tagPage->removeCoverImage();

    expect($tagPage->cover_image)->toBeNull();
});
