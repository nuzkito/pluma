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
    protected $signature = 'pluma:serve {--host=localhost : The host both servers bind to}';

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
        $host = $this->option('host');
        $editorPort = parse_url(config('pluma.editor_url'), PHP_URL_PORT) ?? 8000;
        $previewPort = parse_url(config('pluma.url'), PHP_URL_PORT) ?? 8001;
        $siteDirectory = $disk->path('site');
        $previewServerPath = base_path('resources/preview-server.php');

        $autoloadPath = dirname((new ReflectionClass(ClassLoader::class))->getFileName(), 2).'/autoload.php';

        Process::pool(function (Pool $pool) use (
            $path,
            $host,
            $editorPort,
            $previewPort,
            $siteDirectory,
            $previewServerPath,
            $autoloadPath,
        ) {
            $pool->path(base_path())
                ->forever()
                ->env([
                    'CURRENT_PATH' => $path,
                    'AUTOLOAD_PATH' => $autoloadPath,
                ])
                ->tty(Process::supportsTty())
                ->command("php artisan serve --host=$host --port=$editorPort --no-reload");
            $pool->forever()
                ->tty(Process::supportsTty())
                ->command("php -S $host:$previewPort -t $siteDirectory $previewServerPath");
        })->start(function (string $type, string $output, int $key) {
            echo $output;
        })->wait();

        return self::SUCCESS;
    }
}
