<?php

use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\CreateTagPage;
use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Page\PagePath;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

test('creates a tag page file with the slug-based name', function () {
    initializeSite();
    config()->set('pluma.create_tag_pages', true);

    $action = app(CreateTagPage::class);

    $action->__invoke('Cosas varias');

    expect(Storage::disk('current')->exists('pages/tags/cosas-varias.tag.md'))->toBeTrue();
});

test('does not create a tag page when the option is disabled', function () {
    initializeSite();
    config()->set('pluma.create_tag_pages', false);

    $action = app(CreateTagPage::class);

    $action->__invoke('Cosas varias');

    expect(Storage::disk('current')->exists('pages/tags/cosas-varias.tag.md'))->toBeFalse();
});

test('stores title, path and created_at in the frontmatter with empty content', function () {
    initializeSite();
    config()->set('pluma.create_tag_pages', true);

    Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

    $action = app(CreateTagPage::class);

    $action->__invoke('Laravel');

    Carbon::setTestNow(null);

    $contents = Storage::disk('current')->get('pages/tags/laravel.tag.md');

    expect($contents)
        ->toContain('title: Laravel')
        ->toContain('path: tags/laravel')
        ->toContain('created_at:')
        ->not->toContain('rss:')
        ->not->toContain('tags:')
        ->and(trim(explode("---\n", $contents, 3)[2] ?? ''))->toBe('');
});

test('does not overwrite an existing tag page', function () {
    initializeSite();
    config()->set('pluma.create_tag_pages', true);

    Storage::disk('current')->put('pages/tags/laravel.tag.md', "---\ntitle: Laravel\npath: laravel\ncreated_at: '2025-01-01T10:00:00+00:00'\n---\n\nMy description");

    $action = app(CreateTagPage::class);

    $action->__invoke('Laravel');

    expect(Storage::disk('current')->get('pages/tags/laravel.tag.md'))->toContain('My description');
});

test('generates the tag page in the static site with its posts', function () {
    $repository = initializeSite();
    config()->set('pluma.create_tag_pages', true);

    $repository->save(new ContentPage(
        title: 'Tagged Post',
        path: new PagePath('tagged-post'),
        content: new Markdown('# Tagged'),
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
        tags: ['Laravel'],
    ));

    app(CreateTagPage::class)->__invoke('Laravel');

    $disk = Storage::disk('current');

    expect($disk->exists('site/tags/laravel/index.html'))->toBeTrue()
        ->and($disk->get('site/tags/laravel/index.html'))
        ->toContain('Laravel')
        ->toContain('Tagged Post');
});

test('does not show tag pages in the regular page listing', function () {
    $repository = initializeSite();
    config()->set('pluma.create_tag_pages', true);

    $action = app(CreateTagPage::class);

    $action->__invoke('Cosas varias');

    expect($repository->all())->toBeEmpty();
});
