<?php

use App\Domain\Generator\CoverImage\EmptyCoverImage;
use App\Domain\Generator\CoverImage\SiteCoverImage;
use App\Domain\Generator\Url;
use App\Domain\Generator\Web;

test('builds the site data from the configuration', function () {
    config([
        'pluma.url' => 'https://example.com',
        'pluma.title' => 'My Site',
        'pluma.description' => 'A short summary',
        'pluma.cover_image' => 'cover.png',
    ]);

    $web = Web::fromConfig();

    expect((string) $web->url)->toBe('https://example.com')
        ->and($web->title)->toBe('My Site')
        ->and($web->description)->toBe('A short summary')
        ->and($web->cover_image)->toBeInstanceOf(SiteCoverImage::class)
        ->and($web->cover_image->isDefined())->toBeTrue()
        ->and((string) $web->cover_image)->toBe('cover.png')
        ->and((string) $web->cover_image->url())->toBe('https://example.com/cover.png');
});

test('builds the url as a url object', function () {
    config(['pluma.url' => 'https://example.com/']);

    expect(Web::fromConfig()->url)->toBeInstanceOf(Url::class)
        ->and((string) Web::fromConfig()->url)->toBe('https://example.com');
});

test('has an empty cover image when none is configured', function () {
    config(['pluma.cover_image' => '']);

    $coverImage = Web::fromConfig()->cover_image;

    expect($coverImage)->toBeInstanceOf(EmptyCoverImage::class)
        ->and($coverImage->isDefined())->toBeFalse();
});
