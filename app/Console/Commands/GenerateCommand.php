<?php

namespace App\Console\Commands;

use App\Domain\Generator\SiteGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateCommand extends Command
{
    protected $signature = 'pluma:generate';

    protected $description = 'Generate the static site';

    public function handle(): int
    {
        $generator = $this->laravel->make(SiteGenerator::class);

        $this->info('Generating static site...');

        $generator->generateAll();

        $this->info('Site generated in: '.Storage::disk('current')->path('/'));

        return self::SUCCESS;
    }
}
