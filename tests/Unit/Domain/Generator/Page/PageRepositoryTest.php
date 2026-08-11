<?php

use App\Domain\Generator\Page\Page;
use App\Domain\Generator\Page\PageRepository;
use App\Domain\Generator\Page\TagPage;
use Illuminate\Support\Facades\Storage;

test('all() returns pages from every subdirectory', function () {
    Storage::fake('current');
    Storage::disk('current')->makeDirectory('pages');

    Storage::disk('current')->put('pages/root-page.md', "---\ntitle: Root Page\npath: root-page\ncreated_at: '2025-01-01T00:00:00+00:00'\n---\n");
    Storage::disk('current')->put('pages/posts/post-page.md', "---\ntitle: Post Page\npath: posts/post-page\ncreated_at: '2025-01-01T00:00:00+00:00'\n---\n");
    Storage::disk('current')->put('pages/posts/2025/nested-page.md', "---\ntitle: Deeply Nested Page\npath: posts/2025/nested-page\ncreated_at: '2025-01-01T00:00:00+00:00'\n---\n");

    $repository = new PageRepository;

    expect($repository->all()->map(fn (Page $page) => (string) $page->path)->all())
        ->toEqualCanonicalizing(['root-page', 'posts/post-page', 'posts/2025/nested-page']);
});

test('all() excludes tag pages even when nested', function () {
    Storage::fake('current');
    Storage::disk('current')->makeDirectory('pages');

    Storage::disk('current')->put('pages/posts/post-page.md', "---\ntitle: Post Page\npath: posts/post-page\ncreated_at: '2025-01-01T00:00:00+00:00'\n---\n");
    Storage::disk('current')->put('pages/tags/laravel.tag.md', "---\ntitle: Laravel\npath: tags/laravel\ncreated_at: '2025-01-01T00:00:00+00:00'\n---\n");

    $repository = new PageRepository;

    expect($repository->all())->toHaveCount(1)
        ->and((string) $repository->all()->first()->path)->toBe('posts/post-page');
});

test('findByPath falls back to the tag page and reads its cover image', function () {
    Storage::fake('current');
    Storage::disk('current')->makeDirectory('pages');

    Storage::disk('current')->put('pages/tags/laravel.tag.md', "---\ntitle: Laravel\npath: tags/laravel\ncover_image: header.png\ncreated_at: '2025-01-01T00:00:00+00:00'\n---\n");

    $repository = new PageRepository;

    expect($repository->findByPath('tags/laravel'))->toBeInstanceOf(TagPage::class);

    expect($repository->findByPath('tags/laravel')->cover_image)->toBe('header.png');
});

test('reads the cover image from the front matter', function () {
    Storage::fake('current');
    Storage::disk('current')->makeDirectory('pages');

    Storage::disk('current')->put('pages/cover-page.md', "---\ntitle: Cover Page\npath: cover-page\ncreated_at: '2025-01-01T00:00:00+00:00'\ncover_image: header.png\n---\n\n# Content");

    $repository = new PageRepository;

    expect($repository->findByPath('cover-page')->cover_image)->toBe('header.png');
});

test('retrieved page has null cover image when not set', function () {
    Storage::fake('current');
    Storage::disk('current')->makeDirectory('pages');

    Storage::disk('current')->put('pages/no-cover-page.md', "---\ntitle: No Cover Page\npath: no-cover-page\ncreated_at: '2025-01-01T00:00:00+00:00'\n---\n\n# Content");

    $repository = new PageRepository;

    expect($repository->findByPath('no-cover-page')->cover_image)->toBeNull();
});
