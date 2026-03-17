<?php

namespace App\Domain\Page;

use Illuminate\Support\Str;

class Markdown
{
    public function __construct(public readonly string $value) {}

    public function __toString(): string
    {
        return $this->value;
    }

    public function html(): string
    {
        return Str::of($this->value)->markdown();
    }
}
