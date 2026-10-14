<?php

use App\Domain\Generator\Page\Page;
use App\Domain\Generator\Page\PageRepository;
use App\Domain\Generator\Tag\Tag;
use Illuminate\Support\Facades\Storage;

test('all() returns pages from every subdirectory', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    disk()->put('pages/root-page.md', "---\ntitle: Root Page\npath: root-page\ncreated_at: '2025-01-01T00:00:00+00:00'\n---\n");
    disk()->put('pages/posts/post-page.md', "---\ntitle: Post Page\npath: posts/post-page\ncreated_at: '2025-01-01T00:00:00+00:00'\n---\n");
    disk()->put('pages/posts/2025/nested-page.md', "---\ntitle: Deeply Nested Page\npath: posts/2025/nested-page\ncreated_at: '2025-01-01T00:00:00+00:00'\n---\n");

    $repository = new PageRepository;

    expect($repository->all()->map(fn (Page $page) => (string) $page->path)->all())
        ->toEqualCanonicalizing(['root-page', 'posts/post-page', 'posts/2025/nested-page']);
});

test('resolves the tags of a page from their ids', function () {
    Storage::fake('current');

    disk()->put('pages/tagged.md', "---\ntitle: Tagged\npath: tagged\ncreated_at: '2025-01-01T00:00:00+00:00'\ntags:\n    - laravel\n    - 'Legacy Tag'\n---\n");
    disk()->put('tags/laravel.md', "---\nname: 'Laravel framework'\nslug: framework\ncreated_at: '2025-01-01T00:00:00+00:00'\n---\n");

    config()->set('pluma.tags.create_pages', true);

    $tags = (new PageRepository)->findByPath('tagged')->tags;

    expect($tags)->toHaveCount(2)
        ->and($tags[0])->toBeInstanceOf(Tag::class)
        ->and($tags[0]->name)->toBe('Laravel framework')
        ->and((string) $tags[0]->path)->toBe('tags/framework')
        ->and($tags[0]->hasPage())->toBeTrue()
        ->and($tags[1]->name)->toBe('Legacy Tag')
        ->and($tags[1]->hasPage())->toBeFalse();
});

test('reads the cover image from the front matter', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    disk()->put('pages/cover-page.md', "---\ntitle: Cover Page\npath: cover-page\ncreated_at: '2025-01-01T00:00:00+00:00'\ncover_image: header.png\n---\n\n# Content");

    $repository = new PageRepository;

    expect($repository->findByPath('cover-page')->cover_image)->toBe('header.png');
});

test('retrieved page has null cover image when not set', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    disk()->put('pages/no-cover-page.md', "---\ntitle: No Cover Page\npath: no-cover-page\ncreated_at: '2025-01-01T00:00:00+00:00'\n---\n\n# Content");

    $repository = new PageRepository;

    expect($repository->findByPath('no-cover-page')->cover_image)->toBeNull();
});
