<?php

namespace App\Console;

use Illuminate\Foundation\Console\Kernel;

class PlumaKernel extends Kernel
{
    protected function getArtisan(): PlumaApplication
    {
        if (is_null($this->artisan)) {
            $this->artisan = new PlumaApplication(
                $this->app,
                $this->events,
            );
        }

        return $this->artisan;
    }

    protected function shouldDiscoverCommands(): bool
    {
        return false;
    }
}
