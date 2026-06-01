<?php

use App\Domain\Editor\Page\Page;
use App\Domain\Editor\Page\PublishPage;
use App\Domain\Generator\SiteGenerator;
use Illuminate\Support\Facades\Storage;

test('publish page sets published_at and syncs site generation', function () {
    $repository = initializeSite();
    chdir(Storage::disk('current')->path('/'));

    $page = Page::draft('Test Page');
    $repository->save($page);

    $generator = app(SiteGenerator::class);
    $action = new PublishPage($repository, $generator);

    $result = $action->__invoke((string) $page->path);

    expect($result->isPublished())->toBeTrue()
        ->and($result->published_at)->not->toBeNull();
});

test('publish page always syncs site generation', function () {
    $repository = initializeSite();
    chdir(Storage::disk('current')->path('/'));

    $page = Page::draft('Test Page');
    $repository->save($page);

    $generator = app(SiteGenerator::class);
    $action = new PublishPage($repository, $generator);

    $result = $action->__invoke((string) $page->path);

    expect($result->isPublished())->toBeTrue()
        ->and(Storage::disk('current')->exists("site/{$page->path}"))->toBeTrue();
});
