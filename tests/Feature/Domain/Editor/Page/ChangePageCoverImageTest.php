<?php

use App\Domain\Editor\Page\ChangePageCoverImage;
use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Page\PagePath;
use App\Domain\Editor\Page\PageRepository;
use App\Domain\Generator\SiteGenerator;
use Carbon\Carbon;

test('sets the cover image on a page', function () {
    $repository = initializeSite();

    $page = ContentPage::draft('Cover Test', 'cover-test');
    $repository->save($page);

    $action = new ChangePageCoverImage(
        repository: app(PageRepository::class),
        siteGenerator: app(SiteGenerator::class),
    );

    $action->__invoke('cover-test', 'header.png');

    expect($repository->findByPath('cover-test')->cover_image)->toBe('header.png');
});

test('replaces an existing cover image', function () {
    $repository = initializeSite();

    $page = new ContentPage(
        title: 'Cover Replace Test',
        path: new PagePath('cover-replace-test'),
        content: new Markdown('# Content'),
        created_at: Carbon::now(),
        cover_image: 'old.png',
    );
    $repository->save($page);

    $action = new ChangePageCoverImage(
        repository: app(PageRepository::class),
        siteGenerator: app(SiteGenerator::class),
    );

    $action->__invoke('cover-replace-test', 'new.png');

    expect($repository->findByPath('cover-replace-test')->cover_image)->toBe('new.png');
});
