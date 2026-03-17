<?php

use App\Domain\Page\Page;
use Illuminate\Support\Facades\Storage;

test('deletes a draft page and removes its files', function () {
    $repository = initializeSite();

    $page = Page::draft('Test Page');
    $repository->save($page);

    $disk = Storage::disk('current');

    expect($disk->exists("pages/{$page->path}.md"))->toBeTrue();

    $this->delete('/pages/'.$page->path)
        ->assertRedirect(route('pages.index'));

    expect($disk->exists("pages/{$page->path}.md"))->toBeFalse()
        ->and($repository->findByPath((string) $page->path))->toBeNull();
});

test('deletes a published page and removes generated site files', function () {
    $repository = initializeSite();

    $page = Page::draft('Test Page');
    $repository->save($page);

    $this->postJson("/pages/{$page->path}/publish")
        ->assertSuccessful();

    $disk = Storage::disk('current');

    expect($disk->exists("site/{$page->path}"))->toBeTrue();

    $this->delete("/pages/{$page->path}")
        ->assertRedirect(route('pages.index'));

    expect($disk->exists("pages/{$page->path}.md"))->toBeFalse()
        ->and($disk->exists("site/{$page->path}"))->toBeFalse();
});

test('regenerates the index without the deleted page', function () {
    $repository = initializeSite();

    $page1 = Page::draft('First Page');
    $repository->save($page1);

    $page2 = Page::draft('Second Page');
    $repository->save($page2);

    $this->postJson("/pages/{$page1->path}/publish")->assertSuccessful();
    $this->postJson("/pages/{$page2->path}/publish")->assertSuccessful();

    $disk = Storage::disk('current');

    $indexBefore = $disk->get('site/index.html');
    expect($indexBefore)->toContain('first-page')
        ->and($indexBefore)->toContain('second-page');

    $this->delete('/pages/'.$page1->path)
        ->assertRedirect(route('pages.index'));

    $indexAfter = $disk->get('site/index.html');
    expect($indexAfter)->not->toContain('first-page')
        ->and($indexAfter)->toContain('second-page');
});

test('returns 404 for non-existent page', function () {
    initializeSite();

    $this->delete('/pages/non-existent-path')
        ->assertNotFound();
});
