<?php

namespace App\Console\Commands;

use App\Domain\Generator\Page\PageRepository;
use App\Domain\Generator\SiteGenerator;
use Composer\Autoload\ClassLoader;
use Illuminate\Console\Command;
use Illuminate\Process\Pool;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;

class ServeCommand extends Command
{
    protected $signature = 'pluma:serve';

    protected $description = 'Generate the site and start the development server';

    public function handle(): int
    {
        $disk = Storage::disk('current');

        $repository = new PageRepository;
        $generator = new SiteGenerator($repository);

        $this->info('Generating static site...');
        $generator->generateAll();
        $this->info('Site generated.');

        $path = $disk->path('/');
        $editorUrl = config('pluma.editor_url');
        $editorHost = parse_url($editorUrl, PHP_URL_HOST) ?? 'localhost';
        $editorPort = parse_url($editorUrl, PHP_URL_PORT) ?? 8000;
        $previewUrl = config('pluma.url');
        $previewHost = parse_url($previewUrl, PHP_URL_HOST) ?? 'localhost';
        $previewPort = parse_url($previewUrl, PHP_URL_PORT) ?? 8001;
        $siteDirectory = $disk->path('site');

        $autoloadPath = dirname((new ReflectionClass(ClassLoader::class))->getFileName(), 2).'/autoload.php';

        Process::pool(function (Pool $pool) use (
            $path,
            $editorHost,
            $editorPort,
            $previewHost,
            $previewPort,
            $siteDirectory,
            $autoloadPath,
        ) {
            $pool->path(base_path())
                ->forever()
                ->env([
                    'CURRENT_PATH' => $path,
                    'AUTOLOAD_PATH' => $autoloadPath,
                ])
                ->tty(Process::supportsTty())
                ->command("php artisan serve --host=$editorHost --port=$editorPort --no-reload");
            $pool->forever()
                ->tty(Process::supportsTty())
                ->command("php -S $previewHost:$previewPort -t $siteDirectory");
        })->start(function (string $type, string $output, int $key) {
            echo $output;
        })->wait();

        return self::SUCCESS;
    }
}
