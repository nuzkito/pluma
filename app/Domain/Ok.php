<?php

namespace App\Domain;

use RuntimeException;

/**
 * @template TValue
 */
final readonly class Ok implements Result
{
    /** @param TValue $value */
    public function __construct(private mixed $value) {}

    public function isOk(): bool
    {
        return true;
    }

    public function isError(): bool
    {
        return false;
    }

    /** @return TValue */
    public function unwrap(): mixed
    {
        return $this->value;
    }

    /** @throws RuntimeException */
    public function unwrapError(): string
    {
        throw new RuntimeException('Called `Result::unwrapError()` on an `Ok` value: '.get_debug_type($this->value));
    }

    public function match(callable $ok, callable $error): mixed
    {
        return $ok($this->value);
    }
}
