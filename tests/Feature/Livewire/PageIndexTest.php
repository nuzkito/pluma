<?php

use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Page\PagePath;
use App\Domain\Editor\Page\TagPage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('root shows root pages and folders', function () {
    $repository = initializeSite();
    $repository->save(ContentPage::draft('Root Page', 'root-page'));
    Storage::disk('current')->makeDirectory('pages/posts');

    Livewire::test('pages::page.index')
        ->assertSee('Root Page')
        ->assertSee('posts');
});

test('a directory only shows its own pages', function () {
    $repository = initializeSite();
    $repository->save(ContentPage::draft('Root Page', 'root-page'));
    $repository->save(new ContentPage(
        title: 'Post Page',
        path: new PagePath('posts/post-page'),
        content: new Markdown(''),
        created_at: Carbon::now(),
    ));

    Livewire::test('pages::page.index', ['directory' => 'posts'])
        ->assertSee('Post Page')
        ->assertDontSee('Root Page');
});

test('the tags directory shows its tag pages', function () {
    $repository = initializeSite();
    $repository->save(TagPage::create('Laravel'));

    Livewire::test('pages::page.index', ['directory' => 'tags'])
        ->assertSee('Laravel');
});

test('a tag page links to its edit screen', function () {
    $repository = initializeSite();
    $repository->save(TagPage::create('Laravel'));

    Livewire::test('pages::page.index', ['directory' => 'tags'])
        ->assertSeeHtml('href="'.route('pages.edit', 'tags/laravel').'"');
});

test('tag pages are not shown at the root', function () {
    $repository = initializeSite();
    $repository->save(ContentPage::draft('Root Page', 'root-page'));
    $repository->save(TagPage::create('Laravel'));

    Livewire::test('pages::page.index')
        ->assertSee('Root Page')
        ->assertDontSee('Laravel');
});

test('shows the pages index', function () {
    $repository = initializeSite();

    $page = ContentPage::draft('Test Page', 'test-page');
    $repository->save($page);

    $this->get('/pages')
        ->assertSuccessful()
        ->assertSee('Test Page');
});

test('redirects the root to the pages index', function () {
    initializeSite();

    $this->get('/')->assertRedirect('/pages');
});

test('an empty directory offers to create a page or delete itself', function () {
    initializeSite();
    Storage::disk('current')->makeDirectory('pages/posts');

    Livewire::test('pages::page.index', ['directory' => 'posts'])
        ->assertSee('There are no pages in this directory.')
        ->assertSeeHtml('wire:click="delete"');
});

test('a directory with pages does not show the empty state', function () {
    $repository = initializeSite();
    $repository->save(new ContentPage(
        title: 'Post Page',
        path: new PagePath('posts/post-page'),
        content: new Markdown(''),
        created_at: Carbon::now(),
    ));

    Livewire::test('pages::page.index', ['directory' => 'posts'])
        ->assertDontSee('There are no pages in this directory.')
        ->assertDontSeeHtml('wire:click="delete"');
});

test('a directory with tag pages does not show the empty state', function () {
    $repository = initializeSite();
    $repository->save(TagPage::create('Laravel'));

    Livewire::test('pages::page.index', ['directory' => 'tags'])
        ->assertDontSee('There are no pages in this directory.')
        ->assertDontSeeHtml('wire:click="delete"');
});

test('a directory with subdirectories cannot be deleted', function () {
    initializeSite();
    Storage::disk('current')->makeDirectory('pages/posts/2025');

    Livewire::test('pages::page.index', ['directory' => 'posts'])
        ->assertSee('There are no pages in this directory.')
        ->assertDontSeeHtml('wire:click="delete"');
});

test('the root directory cannot be deleted', function () {
    initializeSite();

    Livewire::test('pages::page.index')
        ->assertSee('There are no pages in this directory.')
        ->assertDontSeeHtml('wire:click="delete"');
});

test('deleting a directory redirects to its parent', function () {
    initializeSite();
    Storage::disk('current')->makeDirectory('pages/posts/2025');

    Livewire::test('pages::page.index', ['directory' => 'posts/2025'])
        ->call('delete')
        ->assertRedirectToRoute('pages.index', ['directory' => 'posts']);

    expect(Storage::disk('current')->exists('pages/posts/2025'))->toBeFalse();
});

test('deleting a root level directory redirects to the root', function () {
    initializeSite();
    Storage::disk('current')->makeDirectory('pages/posts');

    Livewire::test('pages::page.index', ['directory' => 'posts'])
        ->call('delete')
        ->assertRedirectToRoute('pages.index', ['directory' => '']);

    expect(Storage::disk('current')->exists('pages/posts'))->toBeFalse();
});

test('a directory with pages is not deleted', function () {
    $repository = initializeSite();
    $repository->save(new ContentPage(
        title: 'Post Page',
        path: new PagePath('posts/post-page'),
        content: new Markdown(''),
        created_at: Carbon::now(),
    ));

    Livewire::test('pages::page.index', ['directory' => 'posts'])
        ->call('delete')
        ->assertNoRedirect();

    expect(Storage::disk('current')->exists('pages/posts'))->toBeTrue();
});

test('a directory url resolves the index for that directory', function () {
    $repository = initializeSite();
    Storage::disk('current')->makeDirectory('pages/posts');

    $repository->save(new ContentPage(
        title: 'Inside Posts',
        path: new PagePath('posts/inside-posts'),
        content: new Markdown(''),
        created_at: Carbon::now(),
    ));

    $this->get('/pages/posts')
        ->assertSuccessful()
        ->assertSee('Inside Posts');
});
