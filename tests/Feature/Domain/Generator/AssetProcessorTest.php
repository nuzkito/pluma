<?php

use App\Domain\Generator\AssetProcessor;
use App\Domain\Generator\ImageOptimizer;
use Illuminate\Http\UploadedFile;

test('optimizes images when copying them', function () {
    $content = UploadedFile::fake()->image('photo.jpg', 3000, 2000)->getContent();
    disk()->put('assets/hello-world/photo.jpg', $content);

    (new AssetProcessor(new ImageOptimizer))->copy('assets/hello-world/photo.jpg', 'site/hello-world/photo.jpg');

    expect(disk()->size('site/hello-world/photo.jpg'))->toBeLessThan(disk()->size('assets/hello-world/photo.jpg'));
});

test('copies non-image files unmodified', function () {
    disk()->put('assets/hello-world/document.pdf', 'pdf content');
    disk()->put('assets/hello-world/notes.txt', 'text content');

    $processor = new AssetProcessor(new ImageOptimizer);
    $processor->copy('assets/hello-world/document.pdf', 'site/hello-world/document.pdf');
    $processor->copy('assets/hello-world/notes.txt', 'site/hello-world/notes.txt');

    expect(disk()->get('site/hello-world/document.pdf'))->toBe('pdf content')
        ->and(disk()->get('site/hello-world/notes.txt'))->toBe('text content');
});

test('copies gifs unmodified', function () {
    $content = UploadedFile::fake()->image('animation.gif', 400, 300)->getContent();
    disk()->put('assets/hello-world/animation.gif', $content);

    (new AssetProcessor(new ImageOptimizer))->copy('assets/hello-world/animation.gif', 'site/hello-world/animation.gif');

    expect(disk()->get('site/hello-world/animation.gif'))->toBe($content);
});

test('copies unmodified image-extension files that cannot be decoded', function () {
    disk()->put('assets/hello-world/broken.png', 'binary');

    (new AssetProcessor(new ImageOptimizer))->copy('assets/hello-world/broken.png', 'site/hello-world/broken.png');

    expect(disk()->get('site/hello-world/broken.png'))->toBe('binary');
});

test('describes an image with the size of the copy it writes', function () {
    disk()->put('assets/photo.jpg', UploadedFile::fake()->image('photo.jpg', 3000, 2000)->getContent());
    $processor = new AssetProcessor(new ImageOptimizer);
    $processor->copy('assets/photo.jpg', 'site/photo.jpg');

    $details = $processor->imageDetails('assets/photo.jpg');

    expect($details->width)->toBe(1600)
        ->and($details->height)->toBe(1067)
        ->and($details->type)->toBe('image/jpeg')
        ->and(array_slice(getimagesize(disk()->path('site/photo.jpg')), 0, 2))->toBe([1600, 1067]);
});

test('describes a file that is not an image only by its type', function () {
    disk()->put('assets/document.pdf', '%PDF-1.4 content');

    $details = (new AssetProcessor(new ImageOptimizer))->imageDetails('assets/document.pdf');

    expect($details->width)->toBeNull()
        ->and($details->type)->toBe('application/pdf');
});

test('describes an svg image only by its type', function () {
    disk()->put('assets/logo.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"></svg>');

    $details = (new AssetProcessor(new ImageOptimizer))->imageDetails('assets/logo.svg');

    expect($details->width)->toBeNull()
        ->and($details->height)->toBeNull()
        ->and($details->type)->toBe('image/svg+xml');
});

test('does not describe an image that does not exist', function () {
    expect((new AssetProcessor(new ImageOptimizer))->imageDetails('assets/missing.png'))->toBeNull();
});
