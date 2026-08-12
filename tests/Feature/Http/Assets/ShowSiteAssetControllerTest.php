<?php

use Illuminate\Http\UploadedFile;

test('serves a site asset', function () {
    disk()->put('assets/cover.png', UploadedFile::fake()->image('cover.png')->getContent());

    $this->get(route('site-assets.show', 'cover.png'))
        ->assertOk();
});

test('returns 404 when the site asset does not exist', function () {
    $this->get(route('site-assets.show', 'missing.png'))
        ->assertNotFound();
});
