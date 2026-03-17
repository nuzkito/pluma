<?php

use App\Domain\Page\Markdown;
use App\Domain\Page\Page;
use App\Domain\Page\PagePath;
use App\Domain\Page\PageRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

test('returns empty collection when no pages exist', function () {
    Storage::fake('current');
    Storage::disk('current')->makeDirectory('pages');

    $repository = new PageRepository;

    expect($repository->all())->toBeEmpty();
});

test('saves and retrieves a page', function () {
    Storage::fake('current');
    Storage::disk('current')->makeDirectory('pages');

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

test('lists all pages sorted by created_at descending', function () {
    Storage::fake('current');
    Storage::disk('current')->makeDirectory('pages');

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
    Storage::disk('current')->makeDirectory('pages');

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
    );

    $repository->save($draft);
    $repository->save($published);

    $publishedPages = $repository->published();

    expect($publishedPages)->toHaveCount(1)
        ->and($publishedPages->first()->title)->toBe('Published');
});

test('detects duplicate paths', function () {
    Storage::fake('current');
    Storage::disk('current')->makeDirectory('pages');

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

test('moves page file and assets when path changes', function () {
    Storage::fake('current');
    Storage::disk('current')->makeDirectory('pages');
    Storage::disk('current')->makeDirectory('assets');

    $repository = new PageRepository;
    $disk = Storage::disk('current');

    $page = Page::draft('Test Page');
    $repository->save($page);

    $disk->put("assets/{$page->path}/image.jpg", 'fake image');

    $oldPath = (string) $page->path;
    $page->path = new PagePath('new-path');
    $repository->save($page, $oldPath);

    expect($disk->exists('pages/new-path.md'))->toBeTrue()
        ->and($disk->exists("pages/$oldPath.md"))->toBeFalse()
        ->and($disk->exists('assets/new-path/image.jpg'))->toBeTrue()
        ->and($disk->exists("assets/$oldPath/image.jpg"))->toBeFalse();
});

test('moves page file when path changes with no assets directory', function () {
    Storage::fake('current');
    Storage::disk('current')->makeDirectory('pages');

    $repository = new PageRepository;
    $disk = Storage::disk('current');

    $page = Page::draft('Test Page');
    $repository->save($page);
    $oldPath = (string) $page->path;

    $page->path = new PagePath('renamed-path');
    $repository->save($page, $oldPath);

    expect($disk->exists('pages/renamed-path.md'))->toBeTrue()
        ->and($disk->exists("pages/$oldPath.md"))->toBeFalse();
});

test('deletes page file and assets directory', function () {
    Storage::fake('current');
    Storage::disk('current')->makeDirectory('pages');
    Storage::disk('current')->makeDirectory('assets');

    $repository = new PageRepository;
    $disk = Storage::disk('current');

    $page = Page::draft('Test Page');
    $repository->save($page);

    $disk->put("assets/{$page->path}/image.jpg", 'fake image');

    $repository->delete((string) $page->path);

    expect($disk->exists("pages/{$page->path}.md"))->toBeFalse()
        ->and($disk->exists("assets/{$page->path}"))->toBeFalse();
});

test('deletes page file when no assets directory exists', function () {
    Storage::fake('current');
    Storage::disk('current')->makeDirectory('pages');

    $repository = new PageRepository;
    $disk = Storage::disk('current');

    $page = Page::draft('Test Page');
    $repository->save($page);

    $repository->delete((string) $page->path);

    expect($disk->exists("pages/{$page->path}.md"))->toBeFalse();
});

test('returns null when page not found by path', function () {
    Storage::fake('current');
    Storage::disk('current')->makeDirectory('pages');

    $repository = new PageRepository;

    expect($repository->findByPath('non-existent'))->toBeNull();
});

test('ignores non-markdown files in all()', function () {
    Storage::fake('current');
    Storage::disk('current')->makeDirectory('pages');

    $repository = new PageRepository;
    $disk = Storage::disk('current');

    $disk->put('pages/readme.txt', 'some text');

    $page = Page::draft('Real Page');
    $repository->save($page);

    expect($repository->all())->toHaveCount(1);
});

test('persists and retrieves rss field', function () {
    Storage::fake('current');
    Storage::disk('current')->makeDirectory('pages');

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
    Storage::disk('current')->makeDirectory('pages');

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
