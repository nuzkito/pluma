<?php

use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\DraftNameGenerator;
use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Page\PagePath;
use App\Domain\Editor\Page\PageRepository;
use App\Domain\Editor\Page\TagPage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('current');
    Storage::disk('current')->makeDirectory('pages');
});

test('returns Draft when no pages exist', function () {
    $repository = new PageRepository;
    $generator = new DraftNameGenerator($repository);

    expect($generator->__invoke(''))->toBe('Draft');
});

test('returns Draft when no drafts named Draft exist', function () {
    $repository = new PageRepository;

    $page = ContentPage::draft('My custom page', 'my-custom-page');
    $repository->save($page);

    $generator = new DraftNameGenerator($repository);

    expect($generator->__invoke(''))->toBe('Draft');
});

test('returns Draft 2 when Draft exists', function () {
    $repository = new PageRepository;

    $page = ContentPage::draft('Draft', 'draft');
    $repository->save($page);

    $generator = new DraftNameGenerator($repository);

    expect($generator->__invoke(''))->toBe('Draft 2');
});

test('returns next sequential number', function () {
    $repository = new PageRepository;

    $repository->save(ContentPage::draft('Draft', 'draft'));
    $repository->save(ContentPage::draft('Draft 2', 'draft-2'));

    $generator = new DraftNameGenerator($repository);

    expect($generator->__invoke(''))->toBe('Draft 3');
});

test('uses highest number even with gaps', function () {
    $repository = new PageRepository;

    $repository->save(ContentPage::draft('Draft', 'draft'));
    $repository->save(ContentPage::draft('Draft 5', 'draft-5'));

    $generator = new DraftNameGenerator($repository);

    expect($generator->__invoke(''))->toBe('Draft 6');
});

test('treats non-numeric Draft suffix as zero', function () {
    $repository = new PageRepository;

    $repository->save(ContentPage::draft('Draft especial', 'draft-especial'));

    $generator = new DraftNameGenerator($repository);

    expect($generator->__invoke(''))->toBe('Draft 1');
});

test('ignores tag pages in the same directory', function () {
    $repository = new PageRepository;

    $repository->save(ContentPage::draft('Draft', 'draft'));
    $repository->save(new TagPage(
        path: new PagePath('draft-9'),
        title: 'Draft 9',
        content: new Markdown(''),
        created_at: Carbon::now(),
    ));

    $generator = new DraftNameGenerator($repository);

    expect($generator->__invoke(''))->toBe('Draft 2');
});

test('handles mix of numbered and unnumbered drafts', function () {
    $repository = new PageRepository;

    $repository->save(ContentPage::draft('Draft', 'draft'));
    $repository->save(ContentPage::draft('Draft 3', 'draft-3'));
    $repository->save(ContentPage::draft('Draft especial', 'draft-especial'));

    $generator = new DraftNameGenerator($repository);

    expect($generator->__invoke(''))->toBe('Draft 4');
});
