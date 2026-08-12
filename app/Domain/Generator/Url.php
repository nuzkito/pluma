<?php

namespace App\Domain\Generator;

use Stringable;

class Url
{
    public readonly string $value;

    public function __construct(string|Stringable $value)
    {
        $this->value = rtrim((string) $value, '/');
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Build a new url by adding a segment to this one.
     */
    public function append(string|Stringable $segment): self
    {
        return new self($this->value.'/'.trim((string) $segment, '/'));
    }
}
