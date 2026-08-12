<?php

use App\Domain\Generator\ImageOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Image;

test('reduces the size of a large image', function () {
    $content = UploadedFile::fake()->image('photo.jpg', 3000, 2000)->getContent();
    disk()->put('assets/hello-world/photo.jpg', $content);

    (new ImageOptimizer)->optimize('assets/hello-world/photo.jpg', 'site/hello-world/photo.jpg');

    expect(Image::fromStorage('site/hello-world/photo.jpg', 'current')->width())->toBe(1600)
        ->and(disk()->size('site/hello-world/photo.jpg'))->toBeLessThan(disk()->size('assets/hello-world/photo.jpg'));
});

test('does not upscale images smaller than the maximum width', function () {
    $content = UploadedFile::fake()->image('photo.jpg', 400, 300)->getContent();
    disk()->put('assets/hello-world/photo.jpg', $content);

    (new ImageOptimizer)->optimize('assets/hello-world/photo.jpg', 'site/hello-world/photo.jpg');

    $image = Image::fromStorage('site/hello-world/photo.jpg', 'current');

    expect($image->width())->toBe(400)
        ->and($image->height())->toBe(300);
});

test('keeps the file name and extension', function () {
    $content = UploadedFile::fake()->image('photo.jpg', 400, 300)->getContent();
    disk()->put('assets/hello-world/photo.jpg', $content);

    (new ImageOptimizer)->optimize('assets/hello-world/photo.jpg', 'site/hello-world/photo.jpg');

    expect('site/hello-world/photo.jpg')->toExistOnDisk()
        ->and('site/hello-world/photo.webp')->toBeMissingFromDisk();
});

test('identifies optimizable extensions', function () {
    $optimizer = new ImageOptimizer;

    expect($optimizer->isOptimizable('photo.jpg'))->toBeTrue()
        ->and($optimizer->isOptimizable('photo.JPEG'))->toBeTrue()
        ->and($optimizer->isOptimizable('photo.png'))->toBeTrue()
        ->and($optimizer->isOptimizable('photo.webp'))->toBeTrue()
        ->and($optimizer->isOptimizable('animation.gif'))->toBeFalse()
        ->and($optimizer->isOptimizable('icon.svg'))->toBeFalse()
        ->and($optimizer->isOptimizable('notes.txt'))->toBeFalse();
});
