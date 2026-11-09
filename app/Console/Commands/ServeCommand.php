<?php

namespace App\Console\Commands;

use App\Domain\Generator\SiteGenerator;
use Composer\Autoload\ClassLoader;
use Illuminate\Console\Command;
use Illuminate\Process\InvokedProcessPool;
use Illuminate\Process\Pool;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;
use Uri\Rfc3986\Uri;

class ServeCommand extends Command
{
    protected $signature = 'pluma:serve {--host=127.0.0.1 : The host both servers bind to}';

    protected $description = 'Generate the site and start the development server';

    public function handle(): int
    {
        $host = (string) $this->option('host');

        if (! $this->isValidHost($host)) {
            $this->error("The host [$host] is not a valid host name or IP address.");

            return self::FAILURE;
        }

        $disk = Storage::disk('current');

        $generator = $this->laravel->make(SiteGenerator::class);

        $this->info('Generating static site...');
        $generator->generateAll();
        $this->info('Site generated.');

        $path = $disk->path('/');
        $editorUrl = Uri::parse((string) config('pluma.editor_url'));
        $previewUrl = Uri::parse((string) config('pluma.url'));
        $editorPort = $editorUrl?->getPort() ?? 8000;
        $previewPort = $previewUrl?->getPort() ?? 8001;
        $allowedHosts = $this->allowedHosts($host, $editorUrl?->getHost(), $previewUrl?->getHost());
        $siteDirectory = $disk->path('site');
        $previewServerPath = base_path('resources/preview-server.php');

        $autoloadPath = dirname((new ReflectionClass(ClassLoader::class))->getFileName(), 2).'/autoload.php';

        $servers = Process::pool(function (Pool $pool) use (
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
                ->command([PHP_BINARY, 'artisan', 'serve', "--host=$host", "--port=$editorPort", '--no-reload']);
            $pool->forever()
                ->env(['PLUMA_ALLOWED_HOSTS' => $allowedHosts])
                ->tty(Process::supportsTty())
                ->command([PHP_BINARY, '-S', $this->hostWithPort($host, $previewPort), '-t', $siteDirectory, $previewServerPath]);
        })->start(function (string $type, string $output, int $key) {
            echo $output;
        });

        $this->waitWhileReadingOutput($servers);

        return self::SUCCESS;
    }

    private function waitWhileReadingOutput(InvokedProcessPool $servers): void
    {
        while ($servers->running()->isNotEmpty()) {
            usleep(100_000);
        }
    }

    private function isValidHost(string $host): bool
    {
        $unbracketedHost = preg_match('/^\[(.+)\]$/', $host, $matches) === 1 ? $matches[1] : $host;

        if ($unbracketedHost !== $host) {
            return filter_var($unbracketedHost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
        }

        return filter_var($host, FILTER_VALIDATE_IP) !== false
            || filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
    }

    private function hostWithPort(string $host, int $port): string
    {
        if (str_contains($host, ':') && ! str_starts_with($host, '[')) {
            return "[$host]:$port";
        }

        return "$host:$port";
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
