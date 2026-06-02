<?php

use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Page\PagePath;
use Carbon\Carbon;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\artisan;

test('generates the site and starts the development servers', function () {
    $repository = initializeSite();

    $repository->save(new ContentPage(
        title: 'Hello World',
        path: new PagePath('hello-world'),
        content: new Markdown('# Hello World'),
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
    ));

    Process::fake();

    artisan('pluma:serve')
        ->expectsOutputToContain('Generating static site')
        ->expectsOutputToContain('Site generated')
        ->assertSuccessful();

    expect(Storage::disk('current')->exists('site/index.html'))->toBeTrue()
        ->and(Storage::disk('current')->exists('site/hello-world/index.html'))->toBeTrue();
});

test('uses default host and port when config is not set', function () {
    $repository = initializeSite();

    $repository->save(new ContentPage(
        title: 'Hello World',
        path: new PagePath('hello-world'),
        content: new Markdown('# Hello World'),
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
    ));

    Process::fake();

    artisan('pluma:serve')
        ->assertSuccessful();

    Process::assertRan(function ($process) {
        return str_contains($process->command, '--port=8000');
    });

    Process::assertRan(function ($process) {
        return str_contains($process->command, 'localhost:8001');
    });
});

test('passes CURRENT_PATH as process environment variable', function () {
    $repository = initializeSite();

    $repository->save(new ContentPage(
        title: 'Hello World',
        path: new PagePath('hello-world'),
        content: new Markdown('# Hello World'),
        created_at: Carbon::parse('2025-01-01'),
        published_at: Carbon::parse('2025-01-15'),
    ));

    Process::fake();

    artisan('pluma:serve')
        ->assertSuccessful();

    $expectedPath = Storage::disk('current')->path('/');

    Process::assertRan(function ($process) use ($expectedPath) {
        return ($process->environment['CURRENT_PATH'] ?? null) === $expectedPath;
    });
});
