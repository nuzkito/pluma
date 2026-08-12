<?php

use App\Domain\Generator\CoverImage\EmptyCoverImage;

test('is not defined', function () {
    expect((new EmptyCoverImage)->isDefined())->toBeFalse();
});

test('is converted to an empty string', function () {
    expect((string) new EmptyCoverImage)->toBe('');
});

test('has an empty url', function () {
    expect((string) (new EmptyCoverImage)->url())->toBe('');
});
