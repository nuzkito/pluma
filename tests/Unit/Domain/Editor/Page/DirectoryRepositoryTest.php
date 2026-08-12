<?php

use App\Domain\Editor\Page\Directory;
use App\Domain\Editor\Page\DirectoryRepository;
use Illuminate\Support\Facades\Storage;

test('returns empty collection when the pages directory does not exist', function () {
    Storage::fake('current');

    expect((new DirectoryRepository)->searchByDirectory(''))->toBeEmpty();
});

test('lists immediate subdirectories of the root sorted by path', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages/projects');
    disk()->makeDirectory('pages/posts');
    disk()->makeDirectory('pages/posts/2025');

    $directories = (new DirectoryRepository)->searchByDirectory('');

    expect($directories->map(fn (Directory $directory) => $directory->path)->all())
        ->toBe(['posts', 'projects']);
});

test('lists immediate subdirectories of a nested directory', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages/posts/2025');
    disk()->makeDirectory('pages/posts/2024');

    $directories = (new DirectoryRepository)->searchByDirectory('posts');

    expect($directories->map(fn (Directory $directory) => $directory->path)->all())
        ->toBe(['posts/2024', 'posts/2025'])
        ->and($directories->first()->name())->toBe('2024');
});

test('includes the tag pages directory', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages/tags');

    $directories = (new DirectoryRepository)->searchByDirectory('');

    expect($directories->map(fn (Directory $directory) => $directory->path)->all())
        ->toContain('tags');
});

test('deletes an empty directory', function () {
    Storage::fake('current');
    disk()->makeDirectory('pages/tags');

    $repository = new DirectoryRepository;

    expect($repository->deleteIfEmpty('tags'))->toBeTrue()
        ->and($repository->exists('tags'))->toBeFalse();
});

test('keeps a directory that holds files or subdirectories', function () {
    Storage::fake('current');
    disk()->put('pages/tags/laravel.tag.md', 'content');
    disk()->makeDirectory('pages/posts/2025');

    $repository = new DirectoryRepository;

    expect($repository->deleteIfEmpty('tags'))->toBeFalse()
        ->and($repository->deleteIfEmpty('posts'))->toBeFalse()
        ->and($repository->deleteIfEmpty('missing'))->toBeFalse()
        ->and('pages/tags/laravel.tag.md')->toExistOnDisk();
});

test('creates a directory and reflects its existence', function () {
    Storage::fake('current');

    $repository = new DirectoryRepository;

    expect($repository->exists('posts'))->toBeFalse();

    $repository->create('posts');

    expect($repository->exists('posts'))->toBeTrue()
        ->and('pages/posts')->toExistOnDisk();
});
