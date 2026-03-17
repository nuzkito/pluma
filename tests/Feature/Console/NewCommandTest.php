<?php

use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\artisan;

test('copies template files to the target directory', function () {
    Storage::fake('current');

    artisan('pluma:new')
        ->expectsOutputToContain('Site initialized')
        ->assertSuccessful();

    $disk = Storage::disk('current');

    expect($disk->exists('views/layout.blade.php'))->toBeTrue()
        ->and($disk->exists('views/index.blade.php'))->toBeTrue()
        ->and($disk->exists('views/page.blade.php'))->toBeTrue()
        ->and($disk->exists('views/404.blade.php'))->toBeTrue()
        ->and($disk->exists('resources/styles.css'))->toBeTrue()
        ->and($disk->exists('resources/scripts.js'))->toBeTrue()
        ->and($disk->exists('.gitignore'))->toBeTrue()
        ->and($disk->exists('config.php'))->toBeTrue();
});

test('creates config.php with correct content', function () {
    Storage::fake('current');

    artisan('pluma:new')
        ->assertSuccessful();

    $configContent = Storage::disk('current')->get('config.php');

    expect($configContent)->toContain('editor_url')
        ->and($configContent)->toContain('url');
});

test('creates .gitignore that ignores site directory', function () {
    Storage::fake('current');

    artisan('pluma:new')
        ->assertSuccessful();

    $gitignoreContent = Storage::disk('current')->get('.gitignore');

    expect($gitignoreContent)->toContain('site/');
});
