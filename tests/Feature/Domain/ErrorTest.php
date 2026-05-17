<?php

use App\Domain\Error;

test('error returns false for isOk', function () {
    $error = new Error('something went wrong');

    expect($error->isOk())->toBeFalse();
});

test('error returns true for isError', function () {
    $error = new Error('something went wrong');

    expect($error->isError())->toBeTrue();
});

test('error unwrap throws RuntimeException with error message', function () {
    $error = new Error('conflict detected');
    $error->unwrap();
})->throws(RuntimeException::class, 'Called `Result::unwrap()` on an `Error` value: %sconflict detected');

test('error unwrapError returns the stored error string', function () {
    $error = new Error('not found');

    expect($error->unwrapError())->toBe('not found');
});

test('error match executes error callback with stored message', function () {
    $error = new Error('validation failed');

    $result = $error->match(
        ok: fn ($value) => null,
        error: fn ($msg) => "error: {$msg}",
    );

    expect($result)->toBe('error: validation failed');
});

test('error match ignores ok callback', function () {
    $error = new Error('timeout');

    $called = false;
    $result = $error->match(
        ok: fn ($value) => $called = true,
        error: fn ($msg) => $msg,
    );

    expect($result)->toBe('timeout')
        ->and($called)->toBeFalse();
});
