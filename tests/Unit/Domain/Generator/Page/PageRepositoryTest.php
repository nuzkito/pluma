<?php

use App\Domain\Generator\Page\Markdown;
use App\Domain\Generator\Page\Page;
use App\Domain\Generator\Page\PagePath;
use App\Domain\Generator\Page\PageRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

test('all() returns pages from every subdirectory', function () {
    Storage::fake('current');
    Storage::disk('current')->makeDirectory('pages');

    $repository = new PageRepository;

    $repository->save(new Page(
        title: 'Root Page',
        path: new PagePath('root-page'),
        content: new Markdown(''),
        created_at: Carbon::now(),
    ));

    $repository->save(new Page(
        title: 'Post Page',
        path: new PagePath('posts/post-page'),
        content: new Markdown(''),
        created_at: Carbon::now(),
    ));

    $repository->save(new Page(
        title: 'Deeply Nested Page',
        path: new PagePath('posts/2025/nested-page'),
        content: new Markdown(''),
        created_at: Carbon::now(),
    ));

    expect($repository->all()->map(fn (Page $page) => (string) $page->path)->all())
        ->toEqualCanonicalizing(['root-page', 'posts/post-page', 'posts/2025/nested-page']);
});

test('all() excludes tag pages even when nested', function () {
    Storage::fake('current');
    Storage::disk('current')->makeDirectory('pages');

    $repository = new PageRepository;

    $repository->save(new Page(
        title: 'Post Page',
        path: new PagePath('posts/post-page'),
        content: new Markdown(''),
        created_at: Carbon::now(),
    ));

    Storage::disk('current')->put('pages/tags/laravel.tag.md', "---\ntitle: Laravel\npath: tags/laravel\ncreated_at: '2025-01-01T00:00:00+00:00'\n---\n");

    expect($repository->all())->toHaveCount(1)
        ->and((string) $repository->all()->first()->path)->toBe('posts/post-page');
});
