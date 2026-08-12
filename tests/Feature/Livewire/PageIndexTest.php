<?php

use App\Domain\Editor\Page\TagPage;
use Livewire\Livewire;

test('root shows root pages and folders', function () {
    aPage('Root Page', 'root-page');
    disk()->makeDirectory('pages/posts');

    Livewire::test('pages::page.index')
        ->assertSee('Root Page')
        ->assertSee('posts');
});

test('a directory only shows its own pages', function () {
    aPage('Root Page', 'root-page');
    aPage('Post Page', 'posts/post-page');

    Livewire::test('pages::page.index', ['directory' => 'posts'])
        ->assertSee('Post Page')
        ->assertDontSee('Root Page');
});

test('the tags directory shows its tag pages', function () {
    repository()->save(TagPage::create('Laravel'));

    Livewire::test('pages::page.index', ['directory' => 'tags'])
        ->assertSee('Laravel');
});

test('a tag page links to its edit screen', function () {
    repository()->save(TagPage::create('Laravel'));

    Livewire::test('pages::page.index', ['directory' => 'tags'])
        ->assertSeeHtml('href="'.route('pages.edit', 'tags/laravel').'"');
});

test('tag pages are not shown at the root', function () {
    aPage('Root Page', 'root-page');
    repository()->save(TagPage::create('Laravel'));

    Livewire::test('pages::page.index')
        ->assertSee('Root Page')
        ->assertDontSee('Laravel');
});

test('shows the pages index', function () {
    aPage('Test Page', 'test-page');

    $this->get('/pages')
        ->assertSuccessful()
        ->assertSee('Test Page');
});

test('redirects the root to the pages index', function () {
    $this->get('/')->assertRedirect('/pages');
});

test('an empty directory offers to create a page or delete itself', function () {
    disk()->makeDirectory('pages/posts');

    Livewire::test('pages::page.index', ['directory' => 'posts'])
        ->assertSee('There are no pages in this directory.')
        ->assertSeeHtml('wire:click="delete"');
});

test('a directory with pages does not show the empty state', function () {
    aPage('Post Page', 'posts/post-page');

    Livewire::test('pages::page.index', ['directory' => 'posts'])
        ->assertDontSee('There are no pages in this directory.')
        ->assertDontSeeHtml('wire:click="delete"');
});

test('a directory with tag pages does not show the empty state', function () {
    repository()->save(TagPage::create('Laravel'));

    Livewire::test('pages::page.index', ['directory' => 'tags'])
        ->assertDontSee('There are no pages in this directory.')
        ->assertDontSeeHtml('wire:click="delete"');
});

test('a directory with subdirectories cannot be deleted', function () {
    disk()->makeDirectory('pages/posts/2025');

    Livewire::test('pages::page.index', ['directory' => 'posts'])
        ->assertSee('There are no pages in this directory.')
        ->assertDontSeeHtml('wire:click="delete"');
});

test('the root directory cannot be deleted', function () {
    Livewire::test('pages::page.index')
        ->assertSee('There are no pages in this directory.')
        ->assertDontSeeHtml('wire:click="delete"');
});

test('deleting a directory redirects to its parent', function () {
    disk()->makeDirectory('pages/posts/2025');

    Livewire::test('pages::page.index', ['directory' => 'posts/2025'])
        ->call('delete')
        ->assertRedirectToRoute('pages.index', ['directory' => 'posts']);

    expect('pages/posts/2025')->toBeMissingFromDisk();
});

test('deleting a root level directory redirects to the root', function () {
    disk()->makeDirectory('pages/posts');

    Livewire::test('pages::page.index', ['directory' => 'posts'])
        ->call('delete')
        ->assertRedirectToRoute('pages.index', ['directory' => '']);

    expect('pages/posts')->toBeMissingFromDisk();
});

test('a directory with pages is not deleted', function () {
    aPage('Post Page', 'posts/post-page');

    Livewire::test('pages::page.index', ['directory' => 'posts'])
        ->call('delete')
        ->assertNoRedirect();

    expect('pages/posts')->toExistOnDisk();
});

test('a directory url resolves the index for that directory', function () {
    disk()->makeDirectory('pages/posts');

    aPage('Inside Posts', 'posts/inside-posts');

    $this->get('/pages/posts')
        ->assertSuccessful()
        ->assertSee('Inside Posts');
});
