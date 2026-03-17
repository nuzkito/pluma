<?php

use App\Domain\Page\Page;
use Illuminate\Support\Facades\Storage;

test('shows the edit page', function () {
    $repository = initializeSite();

    $page = Page::draft('Test Page');
    $repository->save($page);

    $this->get("/pages/{$page->path}/edit")
        ->assertSuccessful()
        ->assertSee('Test Page');
});

test('returns 404 for non-existent page edit', function () {
    initializeSite();

    $this->get('/pages/non-existent-uuid/edit')
        ->assertNotFound();
});

test('shows existing attachments on edit page', function () {
    $repository = initializeSite();

    $page = Page::draft('Test Page');
    $repository->save($page);

    $disk = Storage::disk('current');
    $disk->put("assets/{$page->path}/photo.jpg", 'fake-image');
    $disk->put("assets/{$page->path}/document.pdf", 'fake-pdf');

    $this->get("/pages/{$page->path}/edit")
        ->assertSuccessful()
        ->assertSee('photo.jpg')
        ->assertSee('document.pdf');
});

test('shows edit page without attachments when none exist', function () {
    $repository = initializeSite();

    $page = Page::draft('Test Page');
    $repository->save($page);

    $this->get("/pages/{$page->path}/edit")
        ->assertSuccessful();
});
