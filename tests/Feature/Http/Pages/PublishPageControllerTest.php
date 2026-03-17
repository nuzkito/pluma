<?php

use App\Domain\Page\Page;

test('publishes a draft', function () {
    $repository = initializeSite();

    $page = Page::draft('Test Page');
    $repository->save($page);

    $this->postJson("/pages/{$page->path}/publish")
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    $updated = $repository->findByPath((string) $page->path);

    expect($updated->isPublished())->toBeTrue();
});
