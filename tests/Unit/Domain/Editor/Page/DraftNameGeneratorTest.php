<?php

use App\Domain\Editor\Page\DraftNameGenerator;
use App\Domain\Editor\Page\Page;
use App\Domain\Editor\Page\PageRepository;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('current');
    Storage::disk('current')->makeDirectory('pages');
});

test('returns Draft when no pages exist', function () {
    $repository = new PageRepository;
    $generator = new DraftNameGenerator($repository);

    expect($generator())->toBe('Draft');
});

test('returns Draft when no drafts named Draft exist', function () {
    $repository = new PageRepository;

    $page = Page::draft('My custom page');
    $repository->save($page);

    $generator = new DraftNameGenerator($repository);

    expect($generator())->toBe('Draft');
});

test('returns Draft 2 when Draft exists', function () {
    $repository = new PageRepository;

    $page = Page::draft('Draft');
    $repository->save($page);

    $generator = new DraftNameGenerator($repository);

    expect($generator())->toBe('Draft 2');
});

test('returns next sequential number', function () {
    $repository = new PageRepository;

    $repository->save(Page::draft('Draft'));
    $repository->save(Page::draft('Draft 2'));

    $generator = new DraftNameGenerator($repository);

    expect($generator())->toBe('Draft 3');
});

test('uses highest number even with gaps', function () {
    $repository = new PageRepository;

    $repository->save(Page::draft('Draft'));
    $repository->save(Page::draft('Draft 5'));

    $generator = new DraftNameGenerator($repository);

    expect($generator())->toBe('Draft 6');
});

test('treats non-numeric Draft suffix as zero', function () {
    $repository = new PageRepository;

    $repository->save(Page::draft('Draft especial'));

    $generator = new DraftNameGenerator($repository);

    expect($generator())->toBe('Draft 1');
});

test('handles mix of numbered and unnumbered drafts', function () {
    $repository = new PageRepository;

    $repository->save(Page::draft('Draft'));
    $repository->save(Page::draft('Draft 3'));
    $repository->save(Page::draft('Draft especial'));

    $generator = new DraftNameGenerator($repository);

    expect($generator())->toBe('Draft 4');
});
