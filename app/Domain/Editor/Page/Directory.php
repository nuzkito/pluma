<?php

namespace App\Domain\Editor\Page;

use Illuminate\Support\Str;

class Directory
{
    public function __construct(public readonly string $path) {}

    public function name(): string
    {
        return Str::afterLast($this->path, '/');
    }

    public function __toString(): string
    {
        return $this->path;
    }
}
