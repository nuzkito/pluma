<?php

namespace App\Domain\Generator\CoverImage;

use App\Domain\Generator\Url;
use Stringable;

interface CoverImage extends Stringable
{
    public function isDefined(): bool;

    public function url(): Url;
}
