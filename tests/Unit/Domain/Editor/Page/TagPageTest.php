<?php

use App\Domain\Editor\Page\TagPage;
use Carbon\Carbon;

test('creates a tag page with slug path, empty content and current date', function () {
    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $tagPage = TagPage::create('Cosas varias');

    expect($tagPage->title)->toBe('Cosas varias')
        ->and((string) $tagPage->path)->toBe('tags/cosas-varias')
        ->and((string) $tagPage->content)->toBe('')
        ->and($tagPage->created_at->toIso8601String())->toBe(Carbon::now()->toIso8601String());

    Carbon::setTestNow(null);
});

test('builds the filename with the tag suffix', function () {
    $tagPage = TagPage::create('Cosas varias');

    expect($tagPage->filename())->toBe('tags/cosas-varias.tag.md');
});

test('serializes only title, path and created_at to the frontmatter', function () {
    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $tagPage = TagPage::create('Laravel');

    expect($tagPage->toArray())->toBe([
        'title' => 'Laravel',
        'path' => 'tags/laravel',
        'created_at' => Carbon::now()->toIso8601String(),
    ]);

    Carbon::setTestNow(null);
});
