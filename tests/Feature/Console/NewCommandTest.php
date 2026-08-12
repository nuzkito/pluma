<?php

use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\artisan;

test('copies template files to the target directory', function () {
    Storage::fake('current');

    artisan('pluma:new')
        ->expectsOutputToContain('Site initialized')
        ->assertSuccessful();

    expect('views/layout.blade.php')->toExistOnDisk()
        ->and('views/index.blade.php')->toExistOnDisk()
        ->and('views/page.blade.php')->toExistOnDisk()
        ->and('views/404.blade.php')->toExistOnDisk()
        ->and('resources/styles.css')->toExistOnDisk()
        ->and('resources/scripts.js')->toExistOnDisk()
        ->and('.gitignore')->toExistOnDisk()
        ->and('pluma-settings.json')->toExistOnDisk();
});

test('creates pluma-settings.json with correct content', function () {
    Storage::fake('current');

    artisan('pluma:new')
        ->assertSuccessful();

    $configContent = disk()->get('pluma-settings.json');

    expect($configContent)->toContain('editor_url')
        ->and($configContent)->toContain('url');
});

test('creates .gitignore that ignores site directory', function () {
    Storage::fake('current');

    artisan('pluma:new')
        ->assertSuccessful();

    $gitignoreContent = disk()->get('.gitignore');

    expect($gitignoreContent)->toContain('site/');
});
