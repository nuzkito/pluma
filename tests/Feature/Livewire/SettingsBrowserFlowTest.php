<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Replays the HTTP requests the browser makes, instead of driving the component
 * in process like Livewire's testing helpers do, so that what the browser sends
 * back for the settings form cannot silently drop the uploaded cover image.
 */
function componentSnapshot(string $html): string
{
    expect(preg_match('/wire:snapshot="([^"]+)"/', $html, $matches))->toBe(1);

    return html_entity_decode($matches[1]);
}

/**
 * @param  array<string, mixed>  $updates
 * @param  array<int, array<string, mixed>>  $calls
 * @return array<string, mixed>
 */
function commit(string $snapshot, array $updates = [], array $calls = []): array
{
    $response = test()->postJson(route('default-livewire.update'), [
        'components' => [[
            'snapshot' => $snapshot,
            'updates' => $updates,
            'calls' => $calls,
        ]],
    ], ['X-Livewire' => 'true']);

    $response->assertOk();

    return $response->json('components.0');
}

/**
 * @return array<string, mixed>
 */
function uploadCoverImage(string $snapshot): array
{
    $component = commit($snapshot, calls: [[
        'path' => '',
        'method' => '_startUpload',
        'params' => ['newImages.cover_image', [['name' => 'cover.png', 'size' => 1024, 'type' => 'image/png']], false],
    ]]);

    $url = collect($component['effects']['dispatches'] ?? [])
        ->firstWhere('name', 'upload:generatedSignedUrl')['params']['url'] ?? null;

    expect($url)->not->toBeNull();

    $upload = test()->post($url, ['files' => [UploadedFile::fake()->image('cover.png')]]);
    $upload->assertOk();

    return commit($component['snapshot'], calls: [[
        'path' => '',
        'method' => '_finishUpload',
        'params' => ['newImages.cover_image', $upload->json('paths'), false],
    ]]);
}

test('the cover image survives a form submit that resends the values of the browser', function () {
    Storage::fake('tmp-for-tests');

    $snapshot = componentSnapshot(test()->get('/settings')->assertOk()->getContent());

    $component = uploadCoverImage($snapshot);

    // The browser owns the inputs bound with wire:model, and the values it sends
    // back were rendered before the upload, so they know nothing about the cover
    // image and must not be able to clear it.
    commit($component['snapshot'], ['values.title' => 'My Site', 'values.cover_image' => ''], [[
        'path' => '',
        'method' => 'save',
        'params' => [],
    ]]);

    $saved = json_decode(disk()->get('pluma-settings.json'), true);

    expect($saved['cover_image'])->toBe('cover.png')
        ->and($saved['title'])->toBe('My Site')
        ->and(disk()->get('site/index.html'))->toContain('<img src="cover.png"');
});

test('the cover image is saved without submitting the form', function () {
    Storage::fake('tmp-for-tests');

    $snapshot = componentSnapshot(test()->get('/settings')->assertOk()->getContent());

    uploadCoverImage($snapshot);

    $saved = json_decode(disk()->get('pluma-settings.json'), true);

    expect($saved['cover_image'])->toBe('cover.png');
});
