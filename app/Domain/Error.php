<?php

namespace App\Domain;

use RuntimeException;

final readonly class Error implements Result
{
    public function __construct(private string $error) {}

    public function isOk(): bool
    {
        return false;
    }

    public function isError(): bool
    {
        return true;
    }

    /** @throws RuntimeException */
    public function unwrap(): mixed
    {
        throw new RuntimeException("Called `Result::unwrap()` on an `Error` value: {$this->error}");
    }

    public function unwrapError(): string
    {
        return $this->error;
    }

    public function match(callable $ok, callable $error): mixed
    {
        return $error($this->error);
    }
}
