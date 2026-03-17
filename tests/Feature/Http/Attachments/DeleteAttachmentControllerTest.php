<?php

use App\Domain\Page\Markdown;
use App\Domain\Page\Page;
use App\Domain\Page\PagePath;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

test('deletes an attachment', function () {
    $repository = initializeSite();

    $page = Page::draft('Test Page');
    $repository->save($page);

    $disk = Storage::disk('current');
    $disk->put("assets/{$page->path}/test.txt", 'hello');

    $this->deleteJson("/pages/{$page->path}/attachments/test.txt")
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    expect($disk->exists("assets/{$page->path}/test.txt"))->toBeFalse();
});

test('deletes attachment from generated site when page is published', function () {
    $repository = initializeSite();

    $page = new Page(
        title: 'Published Page',
        path: new PagePath('published-page'),
        content: new Markdown('# Content'),
        created_at: Carbon::now(),
        published_at: Carbon::now(),
    );
    $repository->save($page);

    $disk = Storage::disk('current');
    $disk->put('assets/published-page/image.png', 'fake-image');
    $disk->put('site/published-page/image.png', 'fake-image');

    $this->deleteJson('/pages/published-page/attachments/image.png')
        ->assertSuccessful();

    expect($disk->exists('assets/published-page/image.png'))->toBeFalse()
        ->and($disk->exists('site/published-page/image.png'))->toBeFalse();
});

test('cleans empty assets directory after deletion', function () {
    $repository = initializeSite();

    $page = Page::draft('Test Page');
    $repository->save($page);

    $disk = Storage::disk('current');
    $disk->put("assets/{$page->path}/only-file.txt", 'content');

    $this->deleteJson("/pages/{$page->path}/attachments/only-file.txt")
        ->assertSuccessful();

    expect($disk->exists("assets/{$page->path}"))->toBeFalse();
});
