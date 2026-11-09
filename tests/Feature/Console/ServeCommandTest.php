<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Process;

use function Pest\Laravel\artisan;

beforeEach(fn () => initializeSite());

test('generates the site and starts the development servers', function () {
    aPublishedPage(
        'Hello World',
        'hello-world',
        content: '# Hello World',
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
    );

    Process::fake();

    artisan('pluma:serve')
        ->expectsOutputToContain('Generating static site')
        ->expectsOutputToContain('Site generated')
        ->assertSuccessful();

    expect('site/index.html')->toExistOnDisk()
        ->and('site/hello-world/index.html')->toExistOnDisk();
});

test('binds both servers to 127.0.0.1 by default, with the ports from the configured urls', function () {
    aPublishedPage(
        'Hello World',
        'hello-world',
        content: '# Hello World',
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
    );

    Process::fake();

    artisan('pluma:serve')
        ->assertSuccessful();

    Process::assertRan(fn ($process) => $process->command === [
        PHP_BINARY, 'artisan', 'serve', '--host=127.0.0.1', '--port=8000', '--no-reload',
    ]);

    Process::assertRan(fn ($process) => $process->command === [
        PHP_BINARY, '-S', '127.0.0.1:8001', '-t', disk()->path('site'), base_path('resources/preview-server.php'),
    ]);
});

test('binds both servers to the host given by the --host option', function () {
    aPublishedPage(
        'Hello World',
        'hello-world',
        content: '# Hello World',
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
    );

    Process::fake();

    artisan('pluma:serve', ['--host' => '0.0.0.0'])
        ->assertSuccessful();

    Process::assertRan(fn ($process) => $process->command[3] === '--host=0.0.0.0');

    Process::assertRan(fn ($process) => $process->command[2] === '0.0.0.0:8001');
});

test('passes CURRENT_PATH as process environment variable', function () {
    aPublishedPage(
        'Hello World',
        'hello-world',
        content: '# Hello World',
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
    );

    Process::fake();

    artisan('pluma:serve')
        ->assertSuccessful();

    $expectedPath = disk()->path('/');

    Process::assertRan(function ($process) use ($expectedPath) {
        return ($process->environment['CURRENT_PATH'] ?? null) === $expectedPath;
    });
});

test('passes the bound host and the hosts of the configured urls to both servers as allowed hosts', function () {
    config(['pluma.url' => 'http://preview.test:8001']);

    Process::fake();

    artisan('pluma:serve', ['--host' => '192.168.1.10'])
        ->assertSuccessful();

    Process::assertRanTimes(function ($process) {
        return ($process->environment['PLUMA_ALLOWED_HOSTS'] ?? null) === '192.168.1.10,127.0.0.1,preview.test';
    }, 2);
});

test('does not allow wildcard bind addresses as hosts', function (string $host) {
    Process::fake();

    artisan('pluma:serve', ['--host' => $host])
        ->assertSuccessful();

    Process::assertRanTimes(function ($process) {
        return ($process->environment['PLUMA_ALLOWED_HOSTS'] ?? null) === '127.0.0.1';
    }, 2);
})->with(['0.0.0.0', '::']);

test('wraps an IPv6 host in brackets for the preview server', function () {
    Process::fake();

    artisan('pluma:serve', ['--host' => '::1'])
        ->assertSuccessful();

    Process::assertRan(fn ($process) => $process->command[3] === '--host=::1');

    Process::assertRan(fn ($process) => $process->command[2] === '[::1]:8001');
});

test('passes a site directory with spaces as a single argument', function () {
    initializeSite('my site');

    Process::fake();

    artisan('pluma:serve')
        ->assertSuccessful();

    Process::assertRan(fn ($process) => $process->command[3] === '-t'
        && $process->command[4] === disk()->path('site')
        && str_contains($process->command[4], ' '));
});

test('fails without starting the servers when the host is not a valid host name or IP address', function (string $host) {
    Process::fake();

    artisan('pluma:serve', ['--host' => $host])
        ->expectsOutputToContain('is not a valid host name or IP address')
        ->assertFailed();

    Process::assertNothingRan();
})->with(['localhost;id', '$(id)', '"localhost"', 'local host', '']);

test('shows the output of the preview server while the editor is still running', function () {
    Process::fake([
        "*'artisan' 'serve'*" => Process::describe()
            ->output(['editor 1', 'editor 2', 'editor 3'])
            ->iterations(3),
        "*'-S'*" => Process::describe()
            ->output('preview 1'),
    ]);

    Process::preventStrayProcesses();

    ob_start();

    try {
        artisan('pluma:serve')->run();
    } finally {
        $output = (string) ob_get_clean();
    }

    expect($output)->toContain('editor 1', 'preview 1', 'editor 3')
        ->and(strpos($output, 'preview 1'))->toBeLessThan(strpos($output, 'editor 3'));
});
