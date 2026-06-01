<?php

namespace App\Domain\Editor\Page;

class Markdown
{
    public function __construct(public readonly string $value) {}

    public function __toString(): string
    {
        return $this->value;
    }
}
