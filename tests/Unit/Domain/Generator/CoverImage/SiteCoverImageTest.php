<?php

use App\Domain\Generator\CoverImage\SiteCoverImage;
use App\Domain\Generator\Url;

test('is defined', function () {
    $coverImage = new SiteCoverImage('cover.png', new Url('https://example.com'));

    expect($coverImage->isDefined())->toBeTrue();
});

test('is converted to the image value as a string', function () {
    $coverImage = new SiteCoverImage('cover.png', new Url('https://example.com'));

    expect((string) $coverImage)->toBe('cover.png');
});

test('builds the full url of the image', function () {
    $coverImage = new SiteCoverImage('cover.png', new Url('https://example.com'));

    expect((string) $coverImage->url())->toBe('https://example.com/cover.png');
});

test('encodes the image value in the url', function () {
    $coverImage = new SiteCoverImage('site cover.jpg', new Url('https://example.com'));

    expect((string) $coverImage->url())->toBe('https://example.com/site%20cover.jpg');
});
