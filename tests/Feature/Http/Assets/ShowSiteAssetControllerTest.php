<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('serves a site asset', function () {
    initializeSite();

    Storage::disk('current')->put('assets/cover.png', UploadedFile::fake()->image('cover.png')->getContent());

    $this->get(route('site-assets.show', 'cover.png'))
        ->assertOk();
});

test('returns 404 when the site asset does not exist', function () {
    initializeSite();

    $this->get(route('site-assets.show', 'missing.png'))
        ->assertNotFound();
});
