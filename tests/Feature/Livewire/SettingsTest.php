<?php

use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('renders the grouped settings form', function () {
    initializeSite();

    Livewire::test('pages::settings')
        ->assertOk()
        ->assertSee('General')
        ->assertSee('Tags')
        ->assertSee('RSS')
        ->assertSee('Embedding')
        ->assertSee('Create tag pages')
        ->assertSee('Allowed domains');
});

test('shows the description of each setting', function () {
    initializeSite();

    Livewire::test('pages::settings')
        ->assertSee('The public URL to preview the generated site.')
        ->assertSee('Generate a page for each tag listing the pages tagged with it.')
        ->assertSee('Domains that embedded content may be loaded from, one per line.');
});

test('loads current config values into the form', function () {
    initializeSite();
    config([
        'pluma.title' => 'My Site',
        'pluma.tags.create_pages' => true,
        'pluma.tags.pages_path' => 'topics',
        'pluma.embedding.allowed_domains' => ['youtube.com', 'github.com'],
    ]);

    Livewire::test('pages::settings')
        ->assertSet('values.title', 'My Site')
        ->assertSet('values.tags.create_pages', true)
        ->assertSet('values.tags.pages_path', 'topics')
        ->assertSet('values.embedding.allowed_domains', "youtube.com\ngithub.com");
});

test('saves values to pluma-settings.json with grouped structure', function () {
    initializeSite();

    Livewire::test('pages::settings')
        ->set('values.title', 'New Title')
        ->set('values.description', 'A description')
        ->set('values.tags.create_pages', true)
        ->set('values.tags.pages_path', 'topics')
        ->set('values.rss.enabled', true)
        ->set('values.embedding.enabled', true)
        ->set('values.embedding.allowed_domains', "youtube.com\nx.com")
        ->call('save')
        ->assertHasNoErrors();

    $saved = json_decode(Storage::disk('current')->get('pluma-settings.json'), true);

    expect($saved)->toMatchArray([
        'title' => 'New Title',
        'description' => 'A description',
        'tags' => ['create_pages' => true, 'pages_path' => 'topics'],
        'rss' => ['enabled' => true],
        'embedding' => ['enabled' => true, 'allowed_domains' => ['youtube.com', 'x.com']],
    ]);
});

test('saving updates the live config', function () {
    initializeSite();

    Livewire::test('pages::settings')
        ->set('values.tags.create_pages', true)
        ->set('values.tags.pages_path', 'topics')
        ->call('save')
        ->assertHasNoErrors();

    expect(config('pluma.tags.create_pages'))->toBeTrue()
        ->and(config('pluma.tags.pages_path'))->toBe('topics');
});

test('validates that urls are required and valid', function () {
    initializeSite();

    Livewire::test('pages::settings')
        ->set('values.url', 'not-a-url')
        ->call('save')
        ->assertHasErrors(['values.url']);
});
