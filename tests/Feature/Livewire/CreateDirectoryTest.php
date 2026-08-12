<?php

use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('creates a directory at the root and redirects to the index', function () {
    initializeSite();

    Livewire::test('pages::page.create-directory')
        ->set('name', 'projects')
        ->call('create')
        ->assertHasNoErrors()
        ->assertRedirectToRoute('pages.index', ['directory' => '']);

    expect(Storage::disk('current')->exists('pages/projects'))->toBeTrue();
});

test('creates a directory inside the current directory', function () {
    initializeSite();
    Storage::disk('current')->makeDirectory('pages/posts');

    Livewire::withQueryParams(['directory' => 'posts'])
        ->test('pages::page.create-directory')
        ->set('name', '2025')
        ->call('create')
        ->assertHasNoErrors()
        ->assertRedirectToRoute('pages.index', ['directory' => 'posts']);

    expect(Storage::disk('current')->exists('pages/posts/2025'))->toBeTrue();
});

test('rejects an invalid directory name', function () {
    initializeSite();

    Livewire::test('pages::page.create-directory')
        ->set('name', 'Invalid Name!')
        ->call('create')
        ->assertHasErrors('name');

    expect(Storage::disk('current')->exists('pages/Invalid Name!'))->toBeFalse();
});

test('rejects a duplicate directory', function () {
    initializeSite();
    Storage::disk('current')->makeDirectory('pages/posts');

    Livewire::test('pages::page.create-directory')
        ->set('name', 'posts')
        ->call('create')
        ->assertHasErrors('name');
});

test('the create directory page resolves', function () {
    initializeSite();

    $this->get('/directories/create')
        ->assertSuccessful()
        ->assertSee('New directory');
});

test('the create directory page keeps the directory it was opened from', function () {
    initializeSite();
    Storage::disk('current')->makeDirectory('pages/posts/2025');

    $this->get(route('directories.create', ['directory' => 'posts/2025']))
        ->assertSuccessful()
        ->assertSee('posts/2025')
        ->assertSee(route('pages.index', ['directory' => 'posts/2025']), escape: false);
});

test('the index links to the create directory page for the current directory', function () {
    initializeSite();
    Storage::disk('current')->makeDirectory('pages/posts');

    Livewire::test('pages::page.index', ['directory' => 'posts'])
        ->assertSee(route('directories.create', ['directory' => 'posts']), escape: false);
});

test('creates a directory inside a numeric directory', function () {
    initializeSite();
    Storage::disk('current')->makeDirectory('pages/2025');

    Livewire::withQueryParams(['directory' => '2025'])
        ->test('pages::page.create-directory')
        ->set('name', 'january')
        ->call('create')
        ->assertHasNoErrors()
        ->assertRedirectToRoute('pages.index', ['directory' => '2025']);

    expect(Storage::disk('current')->exists('pages/2025/january'))->toBeTrue();
});
