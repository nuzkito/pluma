<?php

use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\CreateTagPage;
use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Page\MoveTagPages;
use App\Domain\Editor\Page\PagePath;
use App\Domain\Error;
use App\Domain\Ok;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

test('moves every tag page to the new directory', function () {
    initializeSite();
    config()->set('pluma.tags.create_pages', true);

    app(CreateTagPage::class)->__invoke('Laravel');
    app(CreateTagPage::class)->__invoke('Livewire');

    $result = app(MoveTagPages::class)->__invoke('tags', 'topics');

    $disk = Storage::disk('current');

    expect($result)->toBeInstanceOf(Ok::class)
        ->and($disk->exists('pages/topics/laravel.tag.md'))->toBeTrue()
        ->and($disk->exists('pages/topics/livewire.tag.md'))->toBeTrue()
        ->and($disk->exists('pages/tags/laravel.tag.md'))->toBeFalse()
        ->and($disk->exists('pages/tags'))->toBeFalse();
});

test('updates the path stored in the frontmatter', function () {
    initializeSite();
    config()->set('pluma.tags.create_pages', true);

    app(CreateTagPage::class)->__invoke('Laravel');

    app(MoveTagPages::class)->__invoke('tags', 'topics');

    expect(Storage::disk('current')->get('pages/topics/laravel.tag.md'))
        ->toContain('path: topics/laravel')
        ->toContain('title: Laravel');
});

test('keeps the tag page content and cover image', function () {
    $repository = initializeSite();
    config()->set('pluma.tags.create_pages', true);

    app(CreateTagPage::class)->__invoke('Laravel');

    $tagPage = $repository->findByPath('tags/laravel');
    $tagPage->setContent(new Markdown('My description'));
    $tagPage->changeCoverImage('cover.png');
    $repository->save($tagPage);

    app(MoveTagPages::class)->__invoke('tags', 'topics');

    expect(Storage::disk('current')->get('pages/topics/laravel.tag.md'))
        ->toContain('My description')
        ->toContain('cover_image: cover.png');
});

test('moves the assets of the tag pages', function () {
    initializeSite();
    config()->set('pluma.tags.create_pages', true);

    app(CreateTagPage::class)->__invoke('Laravel');

    $disk = Storage::disk('current');
    $disk->put('assets/tags/laravel/cover.png', 'image');

    app(MoveTagPages::class)->__invoke('tags', 'topics');

    expect($disk->exists('assets/topics/laravel/cover.png'))->toBeTrue()
        ->and($disk->exists('assets/tags/laravel/cover.png'))->toBeFalse();
});

test('removes the generated tag pages from the old directory', function () {
    initializeSite();
    config()->set('pluma.tags.create_pages', true);

    app(CreateTagPage::class)->__invoke('Laravel');

    $disk = Storage::disk('current');

    expect($disk->exists('site/tags/laravel/index.html'))->toBeTrue();

    app(MoveTagPages::class)->__invoke('tags', 'topics');

    expect($disk->exists('site/tags'))->toBeFalse();
});

test('returns an error when the new directory already exists', function () {
    initializeSite();
    config()->set('pluma.tags.create_pages', true);

    app(CreateTagPage::class)->__invoke('Laravel');

    Storage::disk('current')->makeDirectory('pages/topics');

    $result = app(MoveTagPages::class)->__invoke('tags', 'topics');

    expect($result)->toBeInstanceOf(Error::class)
        ->and($result->unwrapError())->toBe('A page or directory with this path already exists.')
        ->and(Storage::disk('current')->exists('pages/tags/laravel.tag.md'))->toBeTrue();
});

test('returns an error when a page already uses the new path', function () {
    $repository = initializeSite();
    config()->set('pluma.tags.create_pages', true);

    app(CreateTagPage::class)->__invoke('Laravel');

    $repository->save(new ContentPage(
        title: 'Topics',
        path: new PagePath('topics'),
        content: new Markdown('# Topics'),
        created_at: Carbon::parse('2025-01-01'),
    ));

    $result = app(MoveTagPages::class)->__invoke('tags', 'topics');

    expect($result)->toBeInstanceOf(Error::class)
        ->and(Storage::disk('current')->exists('pages/tags/laravel.tag.md'))->toBeTrue();
});

test('does nothing when the path does not change', function () {
    initializeSite();
    config()->set('pluma.tags.create_pages', true);

    app(CreateTagPage::class)->__invoke('Laravel');

    $result = app(MoveTagPages::class)->__invoke('tags', 'tags');

    expect($result)->toBeInstanceOf(Ok::class)
        ->and(Storage::disk('current')->exists('pages/tags/laravel.tag.md'))->toBeTrue();
});

test('keeps the old directory when it still holds other pages', function () {
    $repository = initializeSite();
    config()->set('pluma.tags.create_pages', true);

    app(CreateTagPage::class)->__invoke('Laravel');

    $repository->save(new ContentPage(
        title: 'Kept',
        path: new PagePath('tags/kept'),
        content: new Markdown('# Kept'),
        created_at: Carbon::parse('2025-01-01'),
    ));

    app(MoveTagPages::class)->__invoke('tags', 'topics');

    $disk = Storage::disk('current');

    expect($disk->exists('pages/tags/kept.md'))->toBeTrue()
        ->and($disk->exists('pages/topics/laravel.tag.md'))->toBeTrue();
});

test('does not create the new directory when there are no tag pages', function () {
    initializeSite();

    $result = app(MoveTagPages::class)->__invoke('tags', 'topics');

    expect($result)->toBeInstanceOf(Ok::class)
        ->and(Storage::disk('current')->exists('pages/topics'))->toBeFalse();
});
