<?php

use App\Domain\Ok;

test('ok returns true for isOk', function () {
    $ok = new Ok('success');

    expect($ok->isOk())->toBeTrue();
});

test('ok returns false for isError', function () {
    $ok = new Ok('success');

    expect($ok->isError())->toBeFalse();
});

test('ok unwrap returns the stored value', function () {
    $value = ['key' => 'value'];
    $ok = new Ok($value);

    expect($ok->unwrap())->toBe($value);
});

test('ok unwrapError throws RuntimeException', function () {
    $ok = new Ok('success');
    $ok->unwrapError();
})->throws(RuntimeException::class, 'Called `Result::unwrapError()` on an `Ok` value: string');

test('ok unwrapError throws RuntimeException for array value', function () {
    $ok = new Ok(['key' => 'value']);
    $ok->unwrapError();
})->throws(RuntimeException::class, 'Called `Result::unwrapError()` on an `Ok` value: array');

test('ok match executes ok callback with stored value', function () {
    $ok = new Ok('hello');

    $result = $ok->match(
        ok: fn ($value) => "received: {$value}",
        error: fn ($error) => null,
    );

    expect($result)->toBe('received: hello');
});

test('ok match ignores error callback', function () {
    $ok = new Ok(['data' => 42]);

    $called = false;
    $result = $ok->match(
        ok: fn ($value) => $value['data'],
        error: fn ($error) => $called = true,
    );

    expect($result)->toBe(42)
        ->and($called)->toBeFalse();
});
