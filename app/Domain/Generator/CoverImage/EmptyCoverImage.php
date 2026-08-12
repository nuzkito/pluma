<?php

namespace App\Domain\Generator\CoverImage;

use App\Domain\Generator\Url;

class EmptyCoverImage implements CoverImage
{
    public function __toString(): string
    {
        return '';
    }

    public function isDefined(): bool
    {
        return false;
    }

    public function url(): Url
    {
        return new Url('');
    }
}
