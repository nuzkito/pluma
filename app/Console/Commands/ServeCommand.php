<?php

namespace App\Console\Commands;

use App\Domain\Generator\SiteGenerator;
use Composer\Autoload\ClassLoader;
use Illuminate\Console\Command;
use Illuminate\Process\Pool;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;
use Uri\Rfc3986\Uri;

class ServeCommand extends Command
{
    protected $signature = 'pluma:serve {--host=localhost : The host both servers bind to}';

    protected $description = 'Generate the site and start the development server';

    public function handle(): int
    {
        $disk = Storage::disk('current');

        $generator = $this->laravel->make(SiteGenerator::class);

        $this->info('Generating static site...');
        $generator->generateAll();
        $this->info('Site generated.');

        $path = $disk->path('/');
        $host = $this->option('host');
        $editorUrl = Uri::parse((string) config('pluma.editor_url'));
        $previewUrl = Uri::parse((string) config('pluma.url'));
        $editorPort = $editorUrl?->getPort() ?? 8000;
        $previewPort = $previewUrl?->getPort() ?? 8001;
        $allowedHosts = $this->allowedHosts($host, $editorUrl?->getHost(), $previewUrl?->getHost());
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
            $allowedHosts,
        ) {
            $pool->path(base_path())
                ->forever()
                ->env([
                    'CURRENT_PATH' => $path,
                    'AUTOLOAD_PATH' => $autoloadPath,
                    'PLUMA_ALLOWED_HOSTS' => $allowedHosts,
                ])
                ->tty(Process::supportsTty())
                ->command("php artisan serve --host=$host --port=$editorPort --no-reload");
            $pool->forever()
                ->env(['PLUMA_ALLOWED_HOSTS' => $allowedHosts])
                ->tty(Process::supportsTty())
                ->command("php -S $host:$previewPort -t $siteDirectory $previewServerPath");
        })->start(function (string $type, string $output, int $key) {
            echo $output;
        })->wait();

        return self::SUCCESS;
    }

    private function allowedHosts(string $boundHost, ?string ...$urlHosts): string
    {
        $hosts = [
            ...explode(',', (string) config('pluma.allowed_hosts')),
            in_array($boundHost, ['0.0.0.0', '::', '[::]'], true) ? '' : $boundHost,
            ...$urlHosts,
        ];

        return implode(',', array_unique(array_filter(array_map(fn (?string $host): string => trim((string) $host), $hosts))));
    }
}
