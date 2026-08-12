<?php

use App\Domain\Editor\Page\CreateTagPage;
use App\Domain\Editor\Page\DeleteDirectory;
use App\Domain\Editor\Page\PublishPage;

test('deletes an empty directory', function () {
    disk()->makeDirectory('pages/posts');

    expect(app(DeleteDirectory::class)('posts'))->toBeTrue()
        ->and('pages/posts')->toBeMissingFromDisk();
});

test('deletes the directory in the generated site', function () {
    $page = aPage('Post Page', 'posts/post-page');
    app(PublishPage::class)((string) $page->path);

    expect('site/posts')->toExistOnDisk();

    repository()->delete((string) $page->path);

    expect(app(DeleteDirectory::class)('posts'))->toBeTrue()
        ->and('site/posts')->toBeMissingFromDisk();
});

test('does not delete a directory holding pages', function () {
    aPage('Post Page', 'posts/post-page');

    expect(app(DeleteDirectory::class)('posts'))->toBeFalse()
        ->and('pages/posts')->toExistOnDisk();
});

test('does not delete a directory holding tag pages', function () {
    config()->set('pluma.tags.create_pages', true);

    app(CreateTagPage::class)('Laravel');

    expect(app(DeleteDirectory::class)('tags'))->toBeFalse()
        ->and('pages/tags/laravel.tag.md')->toExistOnDisk();
});

test('does not delete a directory holding subdirectories', function () {
    disk()->makeDirectory('pages/posts/2025');

    expect(app(DeleteDirectory::class)('posts'))->toBeFalse()
        ->and('pages/posts')->toExistOnDisk();
});

test('does not delete the root directory', function (string $directory) {
    expect(app(DeleteDirectory::class)($directory))->toBeFalse()
        ->and('pages')->toExistOnDisk();
})->with(['', '/', '//', '.', './', '..', 'posts/..']);

test('ignores the surrounding slashes of a directory', function () {
    disk()->makeDirectory('pages/posts');

    expect(app(DeleteDirectory::class)('/posts/'))->toBeTrue()
        ->and('pages/posts')->toBeMissingFromDisk();
});
