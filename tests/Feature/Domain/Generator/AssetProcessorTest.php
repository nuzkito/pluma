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
