<?php

use App\Domain\Page\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('uploads an attachment', function () {
    $repository = initializeSite();

    $page = Page::draft('Test Page');
    $repository->save($page);

    $file = UploadedFile::fake()->create('document.pdf', 100);

    $this->postJson("/pages/{$page->path}/attachments", ['file' => $file])
        ->assertSuccessful()
        ->assertJson(['success' => true, 'filename' => 'document.pdf']);

    expect(Storage::disk('current')->exists("assets/{$page->path}/document.pdf"))->toBeTrue();
});
