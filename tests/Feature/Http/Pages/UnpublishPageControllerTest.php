<?php

use App\Domain\Page\Page;
use Illuminate\Support\Facades\Storage;

test('unpublishes a published page', function () {
    $repository = initializeSite();

    $page = Page::draft('Test Page');
    $repository->save($page);

    $this->postJson("/pages/{$page->path}/publish")
        ->assertSuccessful();

    $disk = Storage::disk('current');

    expect($disk->exists("site/{$page->path}"))->toBeTrue();

    $this->postJson("/pages/{$page->path}/unpublish")
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    $updated = $repository->findByPath((string) $page->path);

    expect($updated->isPublished())->toBeFalse()
        ->and($disk->exists("site/{$page->path}"))->toBeFalse();
});

test('regenerates index when unpublishing', function () {
    $repository = initializeSite();

    $page = Page::draft('Test Page');
    $repository->save($page);

    $this->postJson("/pages/{$page->path}/publish")
        ->assertSuccessful();

    $disk = Storage::disk('current');

    expect($disk->get('site/index.html'))->toContain($page->title);

    $this->postJson("/pages/{$page->path}/unpublish")
        ->assertSuccessful();

    expect($disk->get('site/index.html'))->not->toContain($page->title);
});
