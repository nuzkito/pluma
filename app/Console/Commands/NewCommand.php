<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class NewCommand extends Command
{
    protected $signature = 'pluma:new';

    protected $description = 'Create a new static site project';

    public function handle(): int
    {
        $path = Storage::disk('current')->path('/');

        $templatePath = Storage::disk('template')->path('/');

        $files = new Filesystem;
        $files->copyDirectory($templatePath, $path);

        $this->info("Site initialized in: {$path}");

        return self::SUCCESS;
    }
}
