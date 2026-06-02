<?php

use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Page\PagePath;
use App\Domain\Editor\Page\PageRepository;
use App\Domain\Editor\Page\RemovePageTag;
use App\Domain\Generator\SiteGenerator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

test('removes tag by index', function () {
    $repository = initializeSite();

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $page = new ContentPage(
        title: 'Remove Tag Test',
        path: new PagePath('remove-tag-test'),
        content: new Markdown('# Content'),
        created_at: Carbon::now(),
        tags: ['php', 'laravel', 'testing'],
    );
    $repository->save($page);

    $action = new RemovePageTag(
        repository: app(PageRepository::class),
        siteGenerator: app(SiteGenerator::class),
    );

    $result = $action->__invoke('remove-tag-test', 1);

    Carbon::setTestNow(null);

    expect($result)->toBeInstanceOf(ContentPage::class)
        ->and($repository->findByPath('remove-tag-test')->tags)->toEqual(['php', 'testing']);
});

test('removes first tag when index is 0', function () {
    $repository = initializeSite();

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $page = new ContentPage(
        title: 'First Tag Test',
        path: new PagePath('first-tag-test'),
        content: new Markdown('# Content'),
        created_at: Carbon::now(),
        tags: ['php', 'laravel'],
    );
    $repository->save($page);

    $action = new RemovePageTag(
        repository: app(PageRepository::class),
        siteGenerator: app(SiteGenerator::class),
    );

    $result = $action->__invoke('first-tag-test', 0);

    Carbon::setTestNow(null);

    expect($repository->findByPath('first-tag-test')->tags)->toEqual(['laravel']);
});

test('removes last tag when index is last', function () {
    $repository = initializeSite();

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $page = new ContentPage(
        title: 'Last Tag Test',
        path: new PagePath('last-tag-test'),
        content: new Markdown('# Content'),
        created_at: Carbon::now(),
        tags: ['php', 'laravel'],
    );
    $repository->save($page);

    $action = new RemovePageTag(
        repository: app(PageRepository::class),
        siteGenerator: app(SiteGenerator::class),
    );

    $result = $action->__invoke('last-tag-test', 1);

    Carbon::setTestNow(null);

    expect($repository->findByPath('last-tag-test')->tags)->toEqual(['php']);
});

test('removes tag from published page and regenerates site', function () {
    $repository = initializeSite();

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $page = new ContentPage(
        title: 'Published Remove Tag Test',
        path: new PagePath('published-remove-tag-test'),
        content: new Markdown('# Content'),
        created_at: Carbon::now(),
        published_at: Carbon::parse('2025-01-01 10:00:00'),
        tags: ['php', 'laravel'],
    );
    $repository->save($page);

    $siteGenerator = mock(SiteGenerator::class, function ($mock) {
        $mock->shouldReceive('generatePage')->once();
        $mock->shouldReceive('generateTagPage')->once();
        $mock->shouldReceive('regenerateIndex')->once();
    });

    $action = new RemovePageTag(
        repository: app(PageRepository::class),
        siteGenerator: $siteGenerator,
    );

    $result = $action->__invoke('published-remove-tag-test', 0);

    Carbon::setTestNow(null);

    expect($result->tags)->toEqual(['laravel']);
});

test('updates the static tag page when removing a tag from a published page', function () {
    $repository = initializeSite();
    config()->set('pluma.create_tag_pages', true);

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
        tags: ['laravel'],
    ));

    $generator = app(SiteGenerator::class);
    $generator->generateTagPage('tags/laravel');

    expect(Storage::disk('current')->get('site/tags/laravel/index.html'))->toContain('Published Post');

    $action = new RemovePageTag(
        repository: app(PageRepository::class),
        siteGenerator: $generator,
    );

    $action->__invoke('published-post', 0);

    expect(Storage::disk('current')->get('site/tags/laravel/index.html'))->not->toContain('Published Post');
});
