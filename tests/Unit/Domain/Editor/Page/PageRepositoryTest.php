<?php

use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Page\PagePath;
use App\Domain\Editor\Page\PageRepository;
use App\Domain\Editor\Page\TagPage;
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

    $page = new ContentPage(
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

    $page = new ContentPage(
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

    $older = new ContentPage(
        title: 'Older',
        path: new PagePath('older'),
        content: new Markdown(''),
        created_at: Carbon::parse('2025-01-01'),
    );

    $newer = new ContentPage(
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

    $draft = new ContentPage(
        title: 'Draft',
        path: new PagePath('draft'),
        content: new Markdown(''),
        created_at: Carbon::now(),
    );

    $published = new ContentPage(
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
    disk()->makeDirectory('pages');

    $repository = new PageRepository;

    $page = new ContentPage(
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

    $repository->save(new ContentPage(
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
    $page = ContentPage::draft('Test Page', 'test-page');
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
    $page = ContentPage::draft('Test Page', 'test-page');
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
    $page = ContentPage::draft('Test Page', 'test-page');
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
    $page = ContentPage::draft('Test Page', 'test-page');
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

    $page = ContentPage::draft('Real Page', 'real-page');
    $repository->save($page);

    expect($repository->all())->toHaveCount(1);
});

test('persists and retrieves rss field', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;

    $page = new ContentPage(
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
    $page = new ContentPage(
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

test('saves a tag page using the .tag.md suffix', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;

    $repository->save(TagPage::create('Cosas varias'));

    expect('pages/tags/cosas-varias.tag.md')->toExistOnDisk();
});

test('moves the tag page file when it is saved with an old path', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;

    $tagPage = TagPage::create('Cosas varias');
    $repository->save($tagPage);

    $tagPage->moveToPath(new PagePath('topics/cosas-varias'));
    $repository->save($tagPage, 'tags/cosas-varias');

    expect('pages/topics/cosas-varias.tag.md')->toExistOnDisk()
        ->and('pages/tags/cosas-varias.tag.md')->toBeMissingFromDisk();
});

test('includes tag pages, typed as TagPage, alongside content pages', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;

    $repository->save(ContentPage::draft('Regular Page', 'regular-page'));
    $repository->save(new TagPage(
        path: new PagePath('root-tag'),
        title: 'Root Tag',
        content: new Markdown(''),
        created_at: Carbon::now(),
    ));

    $all = $repository->all();

    expect($all)->toHaveCount(2)
        ->and($all->whereInstanceOf(ContentPage::class)->pluck('title')->all())->toBe(['Regular Page'])
        ->and($all->whereInstanceOf(TagPage::class)->pluck('title')->all())->toBe(['Root Tag']);
});

test('reads a tag page from its directory', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));
    $repository->save(TagPage::create('Cosas varias'));

    $tags = $repository->searchByDirectory('tags');

    expect($tags)->toHaveCount(1)
        ->and($tags->first())->toBeInstanceOf(TagPage::class)
        ->and($tags->first()->title)->toBe('Cosas varias')
        ->and((string) $tags->first()->path)->toBe('tags/cosas-varias')
        ->and($tags->first()->created_at->toIso8601String())->toBe(Carbon::parse('2025-01-01 10:00:00')->toIso8601String());
});

test('a tag page only shows up in its own directory', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;

    $repository->save(ContentPage::draft('Root Page', 'root-page'));
    $repository->save(TagPage::create('Cosas varias'));

    expect($repository->searchByDirectory('')->pluck('title')->all())->toBe(['Root Page'])
        ->and($repository->searchByDirectory('tags')->pluck('title')->all())->toBe(['Cosas varias']);
});

test('excludes tag pages from published()', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;

    $repository->save(new ContentPage(
        title: 'Published',
        path: new PagePath('published'),
        content: new Markdown(''),
        created_at: Carbon::now(),
        published_at: Carbon::now(),
    ));
    $repository->save(new TagPage(
        path: new PagePath('root-tag'),
        title: 'Root Tag',
        content: new Markdown(''),
        created_at: Carbon::now(),
    ));

    expect($repository->published()->pluck('title')->all())->toBe(['Published']);
});

test('searchByDirectory returns only pages directly in that directory', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;

    $repository->save(ContentPage::draft('Root Page', 'root-page'));

    $repository->save(new ContentPage(
        title: 'Posts Page',
        path: new PagePath('posts/posts-page'),
        content: new Markdown(''),
        created_at: Carbon::now(),
    ));

    $repository->save(new ContentPage(
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

    $repository->save(ContentPage::draft('Root One', 'root-one'));

    $repository->save(new ContentPage(
        title: 'Posts Page',
        path: new PagePath('posts/posts-page'),
        content: new Markdown(''),
        created_at: Carbon::now(),
    ));

    expect($repository->all()->pluck('title')->all())
        ->toBe($repository->searchByDirectory('')->pluck('title')->all())
        ->and($repository->all())->toHaveCount(1);
});

test('findByPath resolves a tag page from its path', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;

    $repository->save(TagPage::create('Cosas varias'));

    $found = $repository->findByPath('tags/cosas-varias');

    expect($found)->toBeInstanceOf(TagPage::class)
        ->and($found->title)->toBe('Cosas varias');
});

test('keeps the cover image of a tag page between saves', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;

    $tagPage = TagPage::create('Cosas varias');
    $tagPage->changeCoverImage('header.png');

    $repository->save($tagPage);

    expect($repository->findByPath('tags/cosas-varias')->cover_image)->toBe('header.png');
});

test('tagExists reflects whether a tag page file is present', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages');

    $repository = new PageRepository;

    expect($repository->tagExists('tags/cosas-varias'))->toBeFalse();

    $repository->save(TagPage::create('Cosas varias'));

    expect($repository->tagExists('tags/cosas-varias'))->toBeTrue();
});
