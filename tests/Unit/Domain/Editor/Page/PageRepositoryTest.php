<?php

use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Page\Page;
use App\Domain\Editor\Page\PagePath;
use App\Domain\Editor\Page\PageRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

test('returns empty collection when no pages exist', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;

    expect($repository->all())->toBeEmpty();
});

test('saves and retrieves a page', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;

    $page = new Page(
        title: 'Test Page',
        path: new PagePath('test-page'),
        content: new Markdown('# Hello World'),
        created_at: Carbon::parse('2025-01-01'),

    );

    $repository->save($page);

    $retrieved = $repository->findByPath('test-page');

    expect($retrieved)->not->toBeNull()
        ->and($retrieved->title)->toBe('Test Page')
        ->and((string) $retrieved->content)->toBe('# Hello World')
        ->and((string) $retrieved->path)->toBe('test-page');
});

test('saves and retrieves a page cover image', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;

    $page = new Page(
        title: 'Test Page',
        path: new PagePath('test-page'),
        content: new Markdown('# Hello World'),
        created_at: Carbon::parse('2025-01-01'),
        cover_image: 'header.png',
    );

    $repository->save($page);

    expect($repository->findByPath('test-page')->cover_image)->toBe('header.png');
});

test('lists all pages sorted by created_at descending', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;

    $older = new Page(
        title: 'Older',
        path: new PagePath('older'),
        content: new Markdown(''),
        created_at: Carbon::parse('2025-01-01'),
    );

    $newer = new Page(
        title: 'Newer',
        path: new PagePath('newer'),
        content: new Markdown(''),
        created_at: Carbon::parse('2025-06-01'),
    );

    $repository->save($older);
    $repository->save($newer);

    $all = $repository->all();

    expect($all)->toHaveCount(2)
        ->and($all->first()->title)->toBe('Newer');
});

test('filters published pages', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;

    $draft = new Page(
        title: 'Draft',
        path: new PagePath('draft'),
        content: new Markdown(''),
        created_at: Carbon::now(),
    );

    $published = new Page(
        title: 'Published',
        path: new PagePath('published'),
        content: new Markdown(''),
        created_at: Carbon::now(),
        published_at: Carbon::now(),
        draft: false,
    );

    $repository->save($draft);
    $repository->save($published);

    $publishedPages = $repository->published();

    expect($publishedPages)->toHaveCount(1)
        ->and($publishedPages->first()->title)->toBe('Published');
});

test('detects duplicate paths', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;

    $page = new Page(
        title: 'Page',
        path: new PagePath('shared-path'),
        content: new Markdown(''),
        created_at: Carbon::now(),
    );

    $repository->save($page);

    expect($repository->pathExists('shared-path'))->toBeTrue()
        ->and($repository->pathExists('shared-path', 'shared-path'))->toBeFalse()
        ->and($repository->pathExists('other-path'))->toBeFalse();
});

test('detects duplicate paths inside a directory', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;

    $repository->save(new Page(
        title: 'Post',
        path: new PagePath('posts/shared-path'),
        content: new Markdown(''),
        created_at: Carbon::now(),
    ));

    expect($repository->pathExists('posts/shared-path'))->toBeTrue()
        ->and($repository->pathExists('posts/shared-path', 'posts/shared-path'))->toBeFalse()
        ->and($repository->pathExists('posts/other-path'))->toBeFalse()
        ->and($repository->pathExists('shared-path'))->toBeFalse();
});

test('moves page file and assets when path changes', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');
    disk()->makeDirectory('assets');

    $repository = new PageRepository;
    $page = Page::draft('Test Page', 'test-page');
    $repository->save($page);

    disk()->put("assets/{$page->path}/image.jpg", 'fake image');

    $oldPath = (string) $page->path;
    $page->path = new PagePath('new-path');
    $repository->save($page, $oldPath);

    expect('pages/new-path.md')->toExistOnDisk()
        ->and("pages/$oldPath.md")->toBeMissingFromDisk()
        ->and('assets/new-path/image.jpg')->toExistOnDisk()
        ->and("assets/$oldPath/image.jpg")->toBeMissingFromDisk();
});

test('moves page file when path changes with no assets directory', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;
    $page = Page::draft('Test Page', 'test-page');
    $repository->save($page);
    $oldPath = (string) $page->path;

    $page->path = new PagePath('renamed-path');
    $repository->save($page, $oldPath);

    expect('pages/renamed-path.md')->toExistOnDisk()
        ->and("pages/$oldPath.md")->toBeMissingFromDisk();
});

test('deletes page file and assets directory', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');
    disk()->makeDirectory('assets');

    $repository = new PageRepository;
    $page = Page::draft('Test Page', 'test-page');
    $repository->save($page);

    disk()->put("assets/{$page->path}/image.jpg", 'fake image');

    $repository->delete((string) $page->path);

    expect("pages/{$page->path}.md")->toBeMissingFromDisk()
        ->and("assets/{$page->path}")->toBeMissingFromDisk();
});

test('deletes page file when no assets directory exists', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;
    $page = Page::draft('Test Page', 'test-page');
    $repository->save($page);

    $repository->delete((string) $page->path);

    expect("pages/{$page->path}.md")->toBeMissingFromDisk();
});

test('returns null when page not found by path', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;

    expect($repository->findByPath('non-existent'))->toBeNull();
});

test('ignores non-markdown files in all()', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;
    disk()->put('pages/readme.txt', 'some text');

    $page = Page::draft('Real Page', 'real-page');
    $repository->save($page);

    expect($repository->all())->toHaveCount(1);
});

test('persists and retrieves rss field', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;

    $page = new Page(
        title: 'RSS Page',
        path: new PagePath('rss-page'),
        content: new Markdown(''),
        created_at: Carbon::now(),
        rss: true,
    );

    $repository->save($page);

    expect($repository->findByPath('rss-page')->rss)->toBeTrue();
});

test('persists and retrieves published_at field', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;

    $publishedAt = Carbon::parse('2025-03-15 12:00:00');
    $page = new Page(
        title: 'Published Page',
        path: new PagePath('published-page'),
        content: new Markdown(''),
        created_at: Carbon::now(),
        published_at: $publishedAt,
    );

    $repository->save($page);

    $retrieved = $repository->findByPath('published-page');

    expect($retrieved->published_at)->not->toBeNull()
        ->and($retrieved->published_at->toIso8601String())->toBe($publishedAt->toIso8601String());
});

test('persists and retrieves the draft flag', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;

    $repository->save(new Page(
        title: 'Unpublished Page',
        path: new PagePath('unpublished-page'),
        content: new Markdown(''),
        created_at: Carbon::now(),
        published_at: Carbon::parse('2025-03-15 12:00:00'),
        draft: true,
    ));

    $retrieved = $repository->findByPath('unpublished-page');

    expect($retrieved->isDraft())->toBeTrue()
        ->and($retrieved->published_at)->not->toBeNull();
});

test('persists and retrieves the revised_at field', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;

    $revisedAt = Carbon::parse('2025-04-01 09:00:00');
    $repository->save(new Page(
        title: 'Revised Page',
        path: new PagePath('revised-page'),
        content: new Markdown(''),
        created_at: Carbon::now(),
        published_at: Carbon::parse('2025-03-15 12:00:00'),
        draft: false,
        revised_at: $revisedAt,
    ));

    expect($repository->findByPath('revised-page')->revised_at->toIso8601String())->toBe($revisedAt->toIso8601String());
});

test('reads a page without the draft flag as published', function () {
    Storage::fake('current');
    disk()->put('pages/legacy-published.md', "---\ntitle: Legacy Published\npath: legacy-published\ncreated_at: '2025-01-01T10:00:00+00:00'\npublished_at: '2025-01-01T10:00:00+00:00'\n---\n");

    $repository = new PageRepository;

    expect($repository->findByPath('legacy-published')->isPublished())->toBeTrue();
});

test('searchByDirectory returns only pages directly in that directory', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;

    $repository->save(Page::draft('Root Page', 'root-page'));

    $repository->save(new Page(
        title: 'Posts Page',
        path: new PagePath('posts/posts-page'),
        content: new Markdown(''),
        created_at: Carbon::now(),
    ));

    $repository->save(new Page(
        title: 'Nested Page',
        path: new PagePath('posts/2025/nested-page'),
        content: new Markdown(''),
        created_at: Carbon::now(),
    ));

    $inPosts = $repository->searchByDirectory('posts');

    expect($inPosts)->toHaveCount(1)
        ->and($inPosts->first()->title)->toBe('Posts Page');
});

test('all() is equivalent to searchByDirectory() at the root', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;

    $repository->save(Page::draft('Root One', 'root-one'));

    $repository->save(new Page(
        title: 'Posts Page',
        path: new PagePath('posts/posts-page'),
        content: new Markdown(''),
        created_at: Carbon::now(),
    ));

    expect($repository->all()->pluck('title')->all())
        ->toBe($repository->searchByDirectory('')->pluck('title')->all())
        ->and($repository->all())->toHaveCount(1);
});
