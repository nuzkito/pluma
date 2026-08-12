<?php

use App\Domain\Editor\Page\CreateTagPage;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

test('renders the grouped settings form', function () {
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
    Livewire::test('pages::settings')
        ->assertSee('The public URL to preview the generated site.')
        ->assertSee('Generate a page for each tag listing the pages tagged with it.')
        ->assertSee('Domains that embedded content may be loaded from, one per line.');
});

test('loads current config values into the form', function () {
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

    $saved = json_decode(disk()->get('pluma-settings.json'), true);

    expect($saved)->toMatchArray([
        'title' => 'New Title',
        'description' => 'A description',
        'tags' => ['create_pages' => true, 'pages_path' => 'topics'],
        'rss' => ['enabled' => true],
        'embedding' => ['enabled' => true, 'allowed_domains' => ['youtube.com', 'x.com']],
    ]);
});

test('saving updates the live config', function () {
    Livewire::test('pages::settings')
        ->set('values.tags.create_pages', true)
        ->set('values.tags.pages_path', 'topics')
        ->call('save')
        ->assertHasNoErrors();

    expect(config('pluma.tags.create_pages'))->toBeTrue()
        ->and(config('pluma.tags.pages_path'))->toBe('topics');
});

test('validates that urls are required and valid', function () {
    Livewire::test('pages::settings')
        ->set('values.url', 'not-a-url')
        ->call('save')
        ->assertHasErrors(['values.url']);
});

describe('tag pages path', function () {
    test('moves the existing tag pages to the new directory', function () {
        config()->set('pluma.tags.create_pages', true);

        app(CreateTagPage::class)('Laravel');

        Livewire::test('pages::settings')
            ->set('values.tags.pages_path', 'topics')
            ->call('save')
            ->assertHasNoErrors();

        expect('pages/topics/laravel.tag.md')->toExistOnDisk()
            ->and('pages/tags/laravel.tag.md')->toBeMissingFromDisk()
            ->and(disk()->get('pages/topics/laravel.tag.md'))->toContain('path: topics/laravel');
    });

    test('regenerates the tag pages in the new directory of the site', function () {
        config()->set('pluma.tags.create_pages', true);

        app(CreateTagPage::class)('Laravel');

        Livewire::test('pages::settings')
            ->set('values.tags.pages_path', 'topics')
            ->call('save')
            ->assertHasNoErrors();

        expect('site/topics/laravel/index.html')->toExistOnDisk()
            ->and('site/tags')->toBeMissingFromDisk();
    });

    test('regenerates the pages so their tag links point to the new directory', function () {
        config()->set('pluma.tags.create_pages', true);

        aPublishedPage(
            'Tagged Post',
            'tagged-post',
            content: '# Tagged',
            created_at: Carbon::parse('2025-01-01'),
            published_at: Carbon::parse('2025-01-15'),
            tags: ['Laravel'],
        );

        app(CreateTagPage::class)('Laravel');

        Livewire::test('pages::settings')
            ->set('values.tags.pages_path', 'topics')
            ->call('save')
            ->assertHasNoErrors();

        expect(disk()->get('site/tagged-post/index.html'))
            ->toContain('/topics/laravel/')
            ->not->toContain('/tags/laravel/');
    });

    test('rejects a path that collides with an existing directory', function () {
        config()->set('pluma.tags.create_pages', true);

        app(CreateTagPage::class)('Laravel');

        disk()->makeDirectory('pages/topics');

        Livewire::test('pages::settings')
            ->set('values.title', 'Not saved')
            ->set('values.tags.pages_path', 'topics')
            ->call('save')
            ->assertHasErrors('values.tags.pages_path');

        $saved = json_decode(disk()->get('pluma-settings.json'), true);

        expect($saved['tags']['pages_path'])->toBe('tags')
            ->and($saved['title'])->not->toBe('Not saved')
            ->and('pages/tags/laravel.tag.md')->toExistOnDisk();
    });
});

describe('cover image', function () {
    test('uploading a cover image stores it in the assets directory', function () {
        Livewire::test('pages::settings')
            ->set('newImages.cover_image', UploadedFile::fake()->image('cover.png'))
            ->assertHasNoErrors()
            ->assertSet('images.cover_image', 'cover.png')
            ->assertSet('newImages.cover_image', null);

        expect('assets/cover.png')->toExistOnDisk();
    });

    test('uploading a cover image through the livewire upload flow stores it', function () {
        Livewire::test('pages::settings')
            ->upload('newImages.cover_image', [UploadedFile::fake()->image('cover.png')])
            ->assertHasNoErrors()
            ->assertSet('images.cover_image', 'cover.png');

        expect('assets/cover.png')->toExistOnDisk();
    });

    test('uploading a cover image saves it to the settings file right away', function () {
        Livewire::test('pages::settings')
            ->set('values.title', 'Not saved yet')
            ->set('newImages.cover_image', UploadedFile::fake()->image('cover.png'));

        $saved = json_decode(disk()->get('pluma-settings.json'), true);

        expect($saved['cover_image'])->toBe('cover.png')
            ->and(config('pluma.cover_image'))->toBe('cover.png')
            ->and($saved['title'])->not->toBe('Not saved yet');
    });

    test('removing the cover image saves the empty value right away', function () {
        Livewire::test('pages::settings')
            ->set('newImages.cover_image', UploadedFile::fake()->image('cover.png'))
            ->call('removeImage', 'cover_image');

        $saved = json_decode(disk()->get('pluma-settings.json'), true);

        expect($saved['cover_image'])->toBe('')
            ->and(config('pluma.cover_image'))->toBe('');
    });

    test('uploading a cover image copies it to the generated site', function () {
        Livewire::test('pages::settings')
            ->set('newImages.cover_image', UploadedFile::fake()->image('cover.png'));

        expect('site/cover.png')->toExistOnDisk();
    });

    test('uploading a new cover image deletes the previous one', function () {
        Livewire::test('pages::settings')
            ->set('newImages.cover_image', UploadedFile::fake()->image('old.png'))
            ->set('newImages.cover_image', UploadedFile::fake()->image('new.png'))
            ->assertSet('images.cover_image', 'new.png');

        expect('assets/old.png')->toBeMissingFromDisk()
            ->and('site/old.png')->toBeMissingFromDisk()
            ->and('assets/new.png')->toExistOnDisk();
    });

    test('replacing the cover image with a file of the same name keeps the new file', function () {
        Livewire::test('pages::settings')
            ->set('newImages.cover_image', UploadedFile::fake()->image('cover.png', 10, 10))
            ->set('newImages.cover_image', UploadedFile::fake()->image('cover.png', 200, 200))
            ->assertSet('images.cover_image', 'cover.png');

        expect('assets/cover.png')->toExistOnDisk()
            ->and('site/cover.png')->toExistOnDisk()
            ->and(getimagesize(disk()->path('assets/cover.png'))[0])->toBe(200);
    });

    test('removing the cover image clears the value and deletes the file', function () {
        Livewire::test('pages::settings')
            ->set('newImages.cover_image', UploadedFile::fake()->image('cover.png'))
            ->call('removeImage', 'cover_image')
            ->assertSet('images.cover_image', '');

        expect('assets/cover.png')->toBeMissingFromDisk()
            ->and('site/cover.png')->toBeMissingFromDisk();
    });

    test('setting the upload back to null keeps the current cover image', function () {
        Livewire::test('pages::settings')
            ->set('newImages.cover_image', UploadedFile::fake()->image('cover.png'))
            ->set('newImages.cover_image', null)
            ->assertHasNoErrors()
            ->assertSet('images.cover_image', 'cover.png');

        $saved = json_decode(disk()->get('pluma-settings.json'), true);

        expect($saved['cover_image'])->toBe('cover.png')
            ->and('assets/cover.png')->toExistOnDisk()
            ->and('site/cover.png')->toExistOnDisk();
    });

    test('rejects files that are not images', function () {
        Livewire::test('pages::settings')
            ->set('newImages.cover_image', UploadedFile::fake()->create('notes.txt', 10))
            ->assertHasErrors(['newImages.cover_image']);

        expect('assets/notes.txt')->toBeMissingFromDisk();
    });

    test('rejects images bigger than the upload limit', function () {
        Livewire::test('pages::settings')
            ->set('newImages.cover_image', UploadedFile::fake()->image('huge.png')->size(12289))
            ->assertHasErrors(['newImages.cover_image']);

        expect('assets/huge.png')->toBeMissingFromDisk();
    });

    test('saving stores the cover image filename and regenerates the index', function () {
        Livewire::test('pages::settings')
            ->set('values.title', 'My Site')
            ->set('newImages.cover_image', UploadedFile::fake()->image('cover.png'))
            ->call('save')
            ->assertHasNoErrors();

        $saved = json_decode(disk()->get('pluma-settings.json'), true);

        expect($saved['cover_image'])->toBe('cover.png')
            ->and(config('pluma.cover_image'))->toBe('cover.png')
            ->and(disk()->get('site/index.html'))->toContain('<img src="http://localhost:8001/cover.png" alt="My Site">');
    });
});

describe('image settings', function () {
    test('keys the upload field and its preview by the setting key', function () {
        Livewire::test('pages::settings')
            ->set('newImages.cover_image', UploadedFile::fake()->image('cover.png'))
            ->assertSeeHtml('id="setting-cover-image-input"')
            ->assertSeeHtml('id="setting-cover-image-preview"')
            ->assertSeeHtml('wire:model="newImages.cover_image"')
            ->assertSeeHtml("removeImage('cover_image')");
    });

    test('ignores uploads for a setting that is not an image', function () {
        config(['pluma.title' => 'My Site']);

        Livewire::test('pages::settings')
            ->set('newImages.title', UploadedFile::fake()->image('cover.png'))
            ->assertHasNoErrors();

        expect('assets/cover.png')->toBeMissingFromDisk()
            ->and(config('pluma.title'))->toBe('My Site');
    });

    test('ignores removals for a setting that is not an image', function () {
        Livewire::test('pages::settings')
            ->call('removeImage', 'title')
            ->assertHasNoErrors();

        $saved = json_decode(disk()->get('pluma-settings.json'), true);

        expect($saved['title'])->not->toBe('');
    });
});
