<?php

use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\CreateTagPage;
use App\Domain\Editor\Page\DeleteDirectory;
use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Page\PagePath;
use App\Domain\Editor\Page\PublishPage;
use App\Domain\Generator\SiteGenerator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

test('deletes an empty directory', function () {
    initializeSite();
    Storage::disk('current')->makeDirectory('pages/posts');

    expect(app(DeleteDirectory::class)->__invoke('posts'))->toBeTrue()
        ->and(Storage::disk('current')->exists('pages/posts'))->toBeFalse();
});

test('deletes the directory in the generated site', function () {
    $repository = initializeSite();

    $page = new ContentPage(
        title: 'Post Page',
        path: new PagePath('posts/post-page'),
        content: new Markdown(''),
        created_at: Carbon::now(),
    );
    $repository->save($page);
    new PublishPage($repository, app(SiteGenerator::class))->__invoke((string) $page->path);

    expect(Storage::disk('current')->exists('site/posts'))->toBeTrue();

    $repository->delete((string) $page->path);

    expect(app(DeleteDirectory::class)->__invoke('posts'))->toBeTrue()
        ->and(Storage::disk('current')->exists('site/posts'))->toBeFalse();
});

test('does not delete a directory holding pages', function () {
    $repository = initializeSite();

    $repository->save(new ContentPage(
        title: 'Post Page',
        path: new PagePath('posts/post-page'),
        content: new Markdown(''),
        created_at: Carbon::now(),
    ));

    expect(app(DeleteDirectory::class)->__invoke('posts'))->toBeFalse()
        ->and(Storage::disk('current')->exists('pages/posts'))->toBeTrue();
});

test('does not delete a directory holding tag pages', function () {
    initializeSite();
    config()->set('pluma.tags.create_pages', true);

    app(CreateTagPage::class)->__invoke('Laravel');

    expect(app(DeleteDirectory::class)->__invoke('tags'))->toBeFalse()
        ->and(Storage::disk('current')->exists('pages/tags/laravel.tag.md'))->toBeTrue();
});

test('does not delete a directory holding subdirectories', function () {
    initializeSite();
    Storage::disk('current')->makeDirectory('pages/posts/2025');

    expect(app(DeleteDirectory::class)->__invoke('posts'))->toBeFalse()
        ->and(Storage::disk('current')->exists('pages/posts'))->toBeTrue();
});

test('does not delete the root directory', function (string $directory) {
    initializeSite();

    expect(app(DeleteDirectory::class)->__invoke($directory))->toBeFalse()
        ->and(Storage::disk('current')->exists('pages'))->toBeTrue();
})->with(['', '/', '//', '.', './', '..', 'posts/..']);

test('ignores the surrounding slashes of a directory', function () {
    initializeSite();
    Storage::disk('current')->makeDirectory('pages/posts');

    expect(app(DeleteDirectory::class)->__invoke('/posts/'))->toBeTrue()
        ->and(Storage::disk('current')->exists('pages/posts'))->toBeFalse();
});
