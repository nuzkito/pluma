<?php

namespace App\Domain\Page;

class PagePath
{
    public function __construct(public readonly string $value) {}

    public function __toString(): string
    {
        return $this->value;
    }
}
