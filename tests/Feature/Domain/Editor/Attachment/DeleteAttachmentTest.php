<?php

use App\Domain\Editor\Attachment\AttachmentRepository;
use App\Domain\Editor\Attachment\DeleteAttachment;
use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\PageRepository;
use Illuminate\Support\Facades\Storage;

test('deletes an attachment', function () {
    initializeSite();
    $page = ContentPage::draft('Test Page');
    app(PageRepository::class)->save($page);

    Storage::disk('current')->put("assets/{$page->path}/file.txt", 'content');

    $deleteAttachment = new DeleteAttachment(
        app(PageRepository::class),
        app(AttachmentRepository::class)
    );

    $deleteAttachment((string) $page->path, 'file.txt');

    expect(Storage::disk('current')->exists("assets/{$page->path}/file.txt"))->toBeFalse();
});

test('deletes attachment and removes empty assets directory', function () {
    initializeSite();
    $page = ContentPage::draft('Test Page');
    app(PageRepository::class)->save($page);

    Storage::disk('current')->put("assets/{$page->path}/file.txt", 'content');

    $deleteAttachment = new DeleteAttachment(
        app(PageRepository::class),
        app(AttachmentRepository::class)
    );

    $deleteAttachment((string) $page->path, 'file.txt');

    expect(Storage::disk('current')->exists("assets/{$page->path}"))->toBeFalse();
});

test('deletes attachment from site directory when page is published', function () {
    initializeSite();
    $page = ContentPage::draft('Test Page');
    app(PageRepository::class)->save($page);

    Storage::disk('current')->put("assets/{$page->path}/file.txt", 'content');
    Storage::disk('current')->put("site/{$page->path}/file.txt", 'published content');

    $page->publish(Carbon\Carbon::now());
    app(PageRepository::class)->save($page);

    $deleteAttachment = new DeleteAttachment(
        app(PageRepository::class),
        app(AttachmentRepository::class)
    );

    $deleteAttachment((string) $page->path, 'file.txt');

    expect(Storage::disk('current')->exists("site/{$page->path}/file.txt"))->toBeFalse();
});

test('does not delete site file when page is not published', function () {
    initializeSite();
    $page = ContentPage::draft('Test Page');
    app(PageRepository::class)->save($page);

    Storage::disk('current')->put("assets/{$page->path}/file.txt", 'content');
    Storage::disk('current')->put("site/{$page->path}/file.txt", 'stale content');

    $deleteAttachment = new DeleteAttachment(
        app(PageRepository::class),
        app(AttachmentRepository::class)
    );

    $deleteAttachment((string) $page->path, 'file.txt');

    expect(Storage::disk('current')->exists("site/{$page->path}/file.txt"))->toBeTrue();
});

test('does nothing when page does not exist', function () {
    initializeSite();

    $deleteAttachment = new DeleteAttachment(
        app(PageRepository::class),
        app(AttachmentRepository::class)
    );

    $deleteAttachment('non-existent-page', 'file.txt');

    expect(true)->toBeTrue();
});
