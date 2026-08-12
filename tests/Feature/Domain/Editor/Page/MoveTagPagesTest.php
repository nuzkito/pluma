<?php

use App\Domain\Editor\Page\CreateTagPage;
use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Page\MoveTagPages;
use Carbon\Carbon;

test('moves every tag page to the new directory', function () {
    config()->set('pluma.tags.create_pages', true);

    app(CreateTagPage::class)('Laravel');
    app(CreateTagPage::class)('Livewire');

    $result = app(MoveTagPages::class)('tags', 'topics');

    expect($result)->toBeOk()
        ->and('pages/topics/laravel.tag.md')->toExistOnDisk()
        ->and('pages/topics/livewire.tag.md')->toExistOnDisk()
        ->and('pages/tags/laravel.tag.md')->toBeMissingFromDisk()
        ->and('pages/tags')->toBeMissingFromDisk();
});

test('updates the path stored in the frontmatter', function () {
    config()->set('pluma.tags.create_pages', true);

    app(CreateTagPage::class)('Laravel');

    app(MoveTagPages::class)('tags', 'topics');

    expect(disk()->get('pages/topics/laravel.tag.md'))
        ->toContain('path: topics/laravel')
        ->toContain('title: Laravel');
});

test('keeps the tag page content and cover image', function () {
    config()->set('pluma.tags.create_pages', true);

    app(CreateTagPage::class)('Laravel');

    $tagPage = repository()->findByPath('tags/laravel');
    $tagPage->setContent(new Markdown('My description'));
    $tagPage->changeCoverImage('cover.png');
    repository()->save($tagPage);

    app(MoveTagPages::class)('tags', 'topics');

    expect(disk()->get('pages/topics/laravel.tag.md'))
        ->toContain('My description')
        ->toContain('cover_image: cover.png');
});

test('moves the assets of the tag pages', function () {
    config()->set('pluma.tags.create_pages', true);

    app(CreateTagPage::class)('Laravel');

    disk()->put('assets/tags/laravel/cover.png', 'image');

    app(MoveTagPages::class)('tags', 'topics');

    expect('assets/topics/laravel/cover.png')->toExistOnDisk()
        ->and('assets/tags/laravel/cover.png')->toBeMissingFromDisk();
});

test('removes the generated tag pages from the old directory', function () {
    config()->set('pluma.tags.create_pages', true);

    app(CreateTagPage::class)('Laravel');

    expect('site/tags/laravel/index.html')->toExistOnDisk();

    app(MoveTagPages::class)('tags', 'topics');

    expect('site/tags')->toBeMissingFromDisk();
});

test('returns an error when the new directory already exists', function () {
    config()->set('pluma.tags.create_pages', true);

    app(CreateTagPage::class)('Laravel');

    disk()->makeDirectory('pages/topics');

    $result = app(MoveTagPages::class)('tags', 'topics');

    expect($result)->toBeError('A page or directory with this path already exists.')
        ->and('pages/tags/laravel.tag.md')->toExistOnDisk();
});

test('returns an error when a page already uses the new path', function () {
    config()->set('pluma.tags.create_pages', true);

    app(CreateTagPage::class)('Laravel');

    aPage('Topics', 'topics', content: '# Topics', created_at: Carbon::parse('2025-01-01'));

    $result = app(MoveTagPages::class)('tags', 'topics');

    expect($result)->toBeError()
        ->and('pages/tags/laravel.tag.md')->toExistOnDisk();
});

test('does nothing when the path does not change', function () {
    config()->set('pluma.tags.create_pages', true);

    app(CreateTagPage::class)('Laravel');

    $result = app(MoveTagPages::class)('tags', 'tags');

    expect($result)->toBeOk()
        ->and('pages/tags/laravel.tag.md')->toExistOnDisk();
});

test('keeps the old directory when it still holds other pages', function () {
    config()->set('pluma.tags.create_pages', true);

    app(CreateTagPage::class)('Laravel');

    aPage('Kept', 'tags/kept', content: '# Kept', created_at: Carbon::parse('2025-01-01'));

    app(MoveTagPages::class)('tags', 'topics');

    expect('pages/tags/kept.md')->toExistOnDisk()
        ->and('pages/topics/laravel.tag.md')->toExistOnDisk();
});

test('does not create the new directory when there are no tag pages', function () {
    $result = app(MoveTagPages::class)('tags', 'topics');

    expect($result)->toBeOk()
        ->and('pages/topics')->toBeMissingFromDisk();
});
