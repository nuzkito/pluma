<?php

namespace App\Domain\Editor\Page;

class PagePath
{
    public function __construct(public readonly string $value) {}

    public static function isValid(string $path): bool
    {
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }

            if (str_contains($segment, '\\') || preg_match('/[\x00-\x1F\x7F]/', $segment)) {
                return false;
            }
        }

        return true;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
