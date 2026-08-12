<?php

test('shows the edit page', function () {
    $page = aPage('Test Page', 'test-page');

    $this->get("/pages/{$page->path}/edit")
        ->assertSuccessful()
        ->assertSee('Test Page');
});

test('returns 404 for non-existent page edit', function () {
    $this->get('/pages/non-existent-uuid/edit')
        ->assertNotFound();
});

test('shows the edit page for a nested page', function () {
    aPage('Nested Page', 'posts/nested-page');

    $this->get('/pages/posts/nested-page/edit')
        ->assertSuccessful()
        ->assertSee('Nested Page');
});

test('shows existing assets on edit page', function () {
    $page = aPage('Test Page', 'test-page');

    disk()->put("assets/{$page->path}/photo.jpg", 'fake-image');
    disk()->put("assets/{$page->path}/document.pdf", 'fake-pdf');

    $this->get("/pages/{$page->path}/edit")
        ->assertSuccessful()
        ->assertSee('photo.jpg')
        ->assertSee('document.pdf');
});

test('shows edit page without assets when none exist', function () {
    $page = aPage('Test Page', 'test-page');

    $this->get("/pages/{$page->path}/edit")
        ->assertSuccessful();
});
