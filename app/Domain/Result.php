<?php

namespace App\Domain;

/**
 * @template TValue
 */
interface Result
{
    public function isOk(): bool;

    public function isError(): bool;

    /** @return TValue */
    public function unwrap(): mixed;

    public function unwrapError(): string;

    public function match(callable $ok, callable $error): mixed;
}
