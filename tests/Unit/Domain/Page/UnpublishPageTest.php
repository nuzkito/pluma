<?php

use App\Domain\Page\Page;
use App\Domain\Page\PublishPage;
use App\Domain\Page\SiteGenerator;
use App\Domain\Page\UnpublishPage;
use Illuminate\Support\Facades\Storage;

test('unpublish page clears published_at and syncs site generation', function () {
    $repository = initializeSite();
    chdir(Storage::disk('current')->path('/'));

    $page = Page::draft('Test Page');
    $repository->save($page);

    $generator = app(SiteGenerator::class);
    new PublishPage($repository, $generator)->__invoke((string) $page->path);

    expect(Storage::disk('current')->exists("site/{$page->path}"))->toBeTrue();

    $action = new UnpublishPage($repository, $generator);
    $action->__invoke((string) $page->path);

    $updated = $repository->findByPath((string) $page->path);

    expect($updated->isPublished())->toBeFalse()
        ->and($updated->published_at)->toBeNull();

    expect(Storage::disk('current')->exists("site/{$page->path}"))->toBeFalse();
});

test('unpublish regenerates index', function () {
    $repository = initializeSite();
    chdir(Storage::disk('current')->path('/'));

    $page = Page::draft('Test Page');
    $repository->save($page);

    $generator = app(SiteGenerator::class);
    new PublishPage($repository, $generator)->__invoke((string) $page->path);

    expect(Storage::disk('current')->get('site/index.html'))->toContain($page->title);

    $action = new UnpublishPage($repository, $generator);
    $action->__invoke((string) $page->path);

    expect(Storage::disk('current')->get('site/index.html'))->not->toContain($page->title);
});
