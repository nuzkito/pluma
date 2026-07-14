<?php

use App\Domain\Editor\Page\AddPageTag;
use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Page\PagePath;
use App\Domain\Editor\Page\PageRepository;
use App\Domain\Generator\SiteGenerator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

test('adds tag to page', function () {
    $repository = initializeSite();

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $page = new ContentPage(
        title: 'Tag Test',
        path: new PagePath('tag-test'),
        content: new Markdown('# Content'),
        created_at: Carbon::now(),
        tags: ['php'],
    );
    $repository->save($page);

    $action = new AddPageTag(
        repository: app(PageRepository::class),
        siteGenerator: app(SiteGenerator::class),
    );

    $result = $action->__invoke('tag-test', 'laravel');

    Carbon::setTestNow(null);

    expect($result)->toBeInstanceOf(ContentPage::class)
        ->and($repository->findByPath('tag-test')->tags)->toEqual(['php', 'laravel']);
});

test('does not add duplicate tag', function () {
    $repository = initializeSite();

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $page = new ContentPage(
        title: 'Duplicate Tag Test',
        path: new PagePath('duplicate-tag-test'),
        content: new Markdown('# Content'),
        created_at: Carbon::now(),
        tags: ['php'],
    );
    $repository->save($page);

    $action = new AddPageTag(
        repository: app(PageRepository::class),
        siteGenerator: app(SiteGenerator::class),
    );

    $result = $action->__invoke('duplicate-tag-test', 'php');

    Carbon::setTestNow(null);

    expect($result)->toBeInstanceOf(ContentPage::class)
        ->and($repository->findByPath('duplicate-tag-test')->tags)->toEqual(['php']);
});

test('adds tag to published page and regenerates site', function () {
    $repository = initializeSite();

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $page = new ContentPage(
        title: 'Published Tag Test',
        path: new PagePath('published-tag-test'),
        content: new Markdown('# Content'),
        created_at: Carbon::now(),
        published_at: Carbon::parse('2025-01-01 10:00:00'),
        tags: [],
    );
    $repository->save($page);

    $siteGenerator = mock(SiteGenerator::class, function ($mock) {
        $mock->shouldReceive('generatePage')->once();
        $mock->shouldReceive('generateTagPage')->once();
        $mock->shouldReceive('regenerateIndex')->once();
    });

    $action = new AddPageTag(
        repository: app(PageRepository::class),
        siteGenerator: $siteGenerator,
    );

    $result = $action->__invoke('published-tag-test', 'new-tag');

    Carbon::setTestNow(null);

    expect($result->tags)->toEqual(['new-tag']);
});

test('updates the static tag page when adding a tag to a published page', function () {
    $repository = initializeSite();
    config()->set('pluma.tags.create_pages', true);

    Storage::disk('current')->put(
        'pages/tags/laravel.tag.md',
        "---\ntitle: laravel\npath: tags/laravel\ncreated_at: '2025-01-01T10:00:00+00:00'\n---\n\n"
    );

    $repository->save(new ContentPage(
        title: 'Published Post',
        path: new PagePath('published-post'),
        content: new Markdown('# Content'),
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
    ));

    $action = new AddPageTag(
        repository: app(PageRepository::class),
        siteGenerator: app(SiteGenerator::class),
    );

    $action->__invoke('published-post', 'laravel');

    expect(Storage::disk('current')->get('site/tags/laravel/index.html'))
        ->toContain('Published Post');
});
