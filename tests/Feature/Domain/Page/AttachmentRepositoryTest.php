<?php

use App\Domain\Attachment\Attachment;
use App\Domain\Attachment\AttachmentRepository;
use App\Domain\Attachment\NewAttachment;
use App\Domain\Page\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('saves an attachment and returns slugified filename', function () {
    initializeSite();
    $attachments = new AttachmentRepository;
    $page = Page::draft('Test Page');
    app(App\Domain\Page\PageRepository::class)->save($page);

    $file = UploadedFile::fake()->create('My Document.pdf', 100);
    $attachment = new NewAttachment(pagePath: $page->path, name: 'my-document.pdf', file: $file);

    $attachments->save($attachment);

    expect(Storage::disk('current')->exists("assets/{$page->path}/my-document.pdf"))->toBeTrue();
});

test('saves an attachment without extension', function () {
    initializeSite();
    $attachments = new AttachmentRepository;
    $page = Page::draft('Test Page');
    app(App\Domain\Page\PageRepository::class)->save($page);

    $file = UploadedFile::fake()->createWithContent('Makefile', 'all:');
    $attachment = new NewAttachment(pagePath: $page->path, name: 'makefile', file: $file);

    $attachments->save($attachment);

    expect(Storage::disk('current')->exists("assets/{$page->path}/makefile"))->toBeTrue();
});

test('deletes an attachment', function () {
    initializeSite();
    $attachments = new AttachmentRepository;
    $page = Page::draft('Test Page');
    app(App\Domain\Page\PageRepository::class)->save($page);

    $disk = Storage::disk('current');
    $disk->put("assets/{$page->path}/test.txt", 'hello');

    $result = $attachments->delete(new Attachment(pagePath: $page->path, name: 'test.txt'));

    expect($result)->toBeTrue()
        ->and($disk->exists("assets/{$page->path}/test.txt"))->toBeFalse();
});

test('returns false when deleting non-existent attachment', function () {
    initializeSite();
    $attachments = new AttachmentRepository;
    $page = Page::draft('Test Page');
    app(App\Domain\Page\PageRepository::class)->save($page);

    $result = $attachments->delete(new Attachment(pagePath: $page->path, name: 'non-existent.txt'));

    expect($result)->toBeFalse();
});

test('checks if attachment exists', function () {
    initializeSite();
    $attachments = new AttachmentRepository;
    $page = Page::draft('Test Page');
    app(App\Domain\Page\PageRepository::class)->save($page);

    Storage::disk('current')->put("assets/{$page->path}/test.txt", 'hello');

    expect($attachments->exists($page->path, 'test.txt'))->toBeTrue()
        ->and($attachments->exists($page->path, 'missing.txt'))->toBeFalse();
});

test('returns absolute path for attachment', function () {
    initializeSite();
    $attachments = new AttachmentRepository;
    $page = Page::draft('Test Page');
    app(App\Domain\Page\PageRepository::class)->save($page);

    Storage::disk('current')->put("assets/{$page->path}/test.txt", 'hello');

    $path = $attachments->path($page->path, 'test.txt');

    expect($path)->toContain("assets/{$page->path}/test.txt")
        ->and(file_exists($path))->toBeTrue();
});

test('returns all attachments for a page', function () {
    initializeSite();
    $attachments = new AttachmentRepository;
    $page = Page::draft('Test Page');
    app(App\Domain\Page\PageRepository::class)->save($page);

    $disk = Storage::disk('current');
    $disk->put("assets/{$page->path}/file1.txt", 'a');
    $disk->put("assets/{$page->path}/file2.png", 'b');

    $all = $attachments->all($page->path);

    expect($all)->toHaveCount(2)
        ->and(collect($all)->pluck('filename')->all())->toEqualCanonicalizing(['file1.txt', 'file2.png']);

    foreach ($all as $attachment) {
        expect($attachment)->toHaveKeys(['filename', 'url']);
    }
});

test('returns empty array when page has no attachments', function () {
    initializeSite();
    $attachments = new AttachmentRepository;
    $page = Page::draft('Test Page');
    app(App\Domain\Page\PageRepository::class)->save($page);

    expect($attachments->all($page->path))->toBe([]);
});
