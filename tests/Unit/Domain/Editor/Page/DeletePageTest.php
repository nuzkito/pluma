<?php

use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\DeletePage;
use App\Domain\Editor\Page\PublishPage;
use App\Domain\Generator\SiteGenerator;
use Illuminate\Support\Facades\Storage;

test('delete page removes it from repository', function () {
    $repository = initializeSite();

    $page = ContentPage::draft('Test Page', 'test-page');
    $repository->save($page);

    $action = new DeletePage($repository, app(SiteGenerator::class));
    $action->__invoke((string) $page->path);

    expect($repository->findByPath((string) $page->path))->toBeNull();
});

test('delete page removes generated site files', function () {
    $repository = initializeSite();
    chdir(Storage::disk('current')->path('/'));

    $page = ContentPage::draft('Test Page', 'test-page');
    $repository->save($page);

    new PublishPage($repository, app(SiteGenerator::class))->__invoke((string) $page->path);

    expect(Storage::disk('current')->exists("site/{$page->path}"))->toBeTrue();

    $action = new DeletePage($repository, app(SiteGenerator::class));
    $action->__invoke((string) $page->path);

    expect(Storage::disk('current')->exists("site/{$page->path}"))->toBeFalse();
});

test('delete page regenerates index when deleting published page', function () {
    $repository = initializeSite();
    chdir(Storage::disk('current')->path('/'));

    $page1 = ContentPage::draft('First Page', 'first-page');
    $repository->save($page1);

    $page2 = ContentPage::draft('Second Page', 'second-page');
    $repository->save($page2);

    new PublishPage($repository, app(SiteGenerator::class))->__invoke((string) $page1->path);
    new PublishPage($repository, app(SiteGenerator::class))->__invoke((string) $page2->path);

    expect(Storage::disk('current')->get('site/index.html'))->toContain('first-page')
        ->and(Storage::disk('current')->get('site/index.html'))->toContain('second-page');

    $action = new DeletePage($repository, app(SiteGenerator::class));
    $action->__invoke((string) $page1->path);

    expect(Storage::disk('current')->get('site/index.html'))->not->toContain('first-page')
        ->and(Storage::disk('current')->get('site/index.html'))->toContain('second-page');
});

test('delete page does not touch site files when deleting draft', function () {
    $repository = initializeSite();
    chdir(Storage::disk('current')->path('/'));

    $page = ContentPage::draft('Draft Page', 'draft-page');
    $repository->save($page);

    $action = new DeletePage($repository, app(SiteGenerator::class));
    $action->__invoke((string) $page->path);

    expect(Storage::disk('current')->exists("site/{$page->path}"))->toBeFalse();
});
