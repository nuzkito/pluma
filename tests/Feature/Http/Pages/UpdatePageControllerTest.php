<?php

use App\Domain\Page\Markdown;
use App\Domain\Page\Page;
use App\Domain\Page\PagePath;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

test('updates a page via JSON', function () {
    $repository = initializeSite();

    $page = Page::draft('Test Page');
    $repository->save($page);

    $this->putJson('/pages/'.$page->path, [
        'title' => 'Updated Title',
        'content' => '# New Content',
    ])
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    $updated = $repository->findByPath((string) $page->path);

    expect($updated->title)->toBe('Updated Title')
        ->and((string) $updated->content)->toBe('# New Content');
});

test('validates duplicate paths on update', function () {
    $repository = initializeSite();

    $page1 = new Page(
        title: 'Page 1',
        path: new PagePath('my-path'),
        content: new Markdown(''),
        created_at: Carbon::now(),
    );
    $repository->save($page1);

    $draft = Page::draft('Test Page');
    $repository->save($draft);

    $this->putJson("/pages/{$draft->path}", [
        'path' => 'my-path',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('path');
});

test('removes feed.xml when rss is disabled on the only rss-enabled published page', function () {
    $repository = initializeSite();

    $page = new Page(
        title: 'RSS Page',
        path: new PagePath('rss-page'),
        content: new Markdown('# RSS'),
        created_at: Carbon::now(),
        published_at: Carbon::now(),
        rss: true,
    );
    $repository->save($page);

    Storage::disk('current')->makeDirectory('site');
    Storage::disk('current')->put('site/feed.xml', '<rss>old feed</rss>');

    $this->putJson('/pages/rss-page', ['rss' => false])
        ->assertSuccessful();

    expect(Storage::disk('current')->exists('site/feed.xml'))->toBeFalse();
});

test('regenerates feed.xml when rss is disabled on a page but others still have rss enabled', function () {
    $repository = initializeSite();

    $pageA = new Page(
        title: 'RSS Page A',
        path: new PagePath('rss-page-a'),
        content: new Markdown('# RSS A'),
        created_at: Carbon::now(),
        published_at: Carbon::now(),
        rss: true,
    );
    $repository->save($pageA);

    $pageB = new Page(
        title: 'RSS Page B',
        path: new PagePath('rss-page-b'),
        content: new Markdown('# RSS B'),
        created_at: Carbon::now(),
        published_at: Carbon::now(),
        rss: true,
    );
    $repository->save($pageB);

    Storage::disk('current')->makeDirectory('site');

    $this->putJson('/pages/rss-page-a', ['rss' => false])
        ->assertSuccessful();

    $disk = Storage::disk('current');

    expect($disk->exists('site/feed.xml'))->toBeTrue()
        ->and($disk->get('site/feed.xml'))->not->toContain('RSS Page A')
        ->and($disk->get('site/feed.xml'))->toContain('RSS Page B');
});

test('regenerates published page on update', function () {
    $repository = initializeSite();

    $page = new Page(
        title: 'Published Page',
        path: new PagePath('published-page'),
        content: new Markdown('# Original'),
        created_at: Carbon::now(),
        published_at: Carbon::now(),
    );
    $repository->save($page);

    Storage::disk('current')->makeDirectory('site');

    $this->putJson('/pages/published-page', [
        'content' => '# Updated',
    ])
        ->assertSuccessful();

    $disk = Storage::disk('current');

    expect($disk->exists('site/published-page/index.html'))->toBeTrue();

    $html = $disk->get('site/published-page/index.html');
    expect($html)->toContain('<h1>Updated</h1>');
});

test('updates published_at date on a published page', function () {
    $repository = initializeSite();

    $page = new Page(
        title: 'Published Page',
        path: new PagePath('published-page'),
        content: new Markdown('# Content'),
        created_at: Carbon::now(),
        published_at: Carbon::parse('2025-01-01 10:00:00'),
    );
    $repository->save($page);

    $this->putJson('/pages/published-page', [
        'published_at' => '2025-06-15T14:30',
    ])
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    $updated = $repository->findByPath('published-page');

    expect($updated->published_at->format('Y-m-d H:i'))->toBe('2025-06-15 14:30');
});

test('clearing published_at unpublishes a page', function () {
    $repository = initializeSite();

    $page = new Page(
        title: 'Published Page',
        path: new PagePath('published-page'),
        content: new Markdown('# Content'),
        created_at: Carbon::now(),
        published_at: Carbon::now(),
    );
    $repository->save($page);

    $this->putJson('/pages/published-page', [
        'published_at' => null,
    ])
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    $updated = $repository->findByPath('published-page');

    expect($updated->published_at)->toBeNull()
        ->and($updated->isDraft())->toBeTrue();
});
