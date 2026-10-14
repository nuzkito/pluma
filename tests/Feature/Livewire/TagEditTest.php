<?php

use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

test('a tag opens in the editor with its name, slug and content', function () {
    aTag('Laravel', slug: 'framework', content: 'All about Laravel');

    $this->get(route('tags.edit', 'laravel'))->assertSuccessful();

    Livewire::test('pages::tag.edit', ['id' => 'laravel'])
        ->assertSet('name', 'Laravel')
        ->assertSet('slug', 'framework')
        ->assertSet('content', 'All about Laravel');
});

test('a missing tag returns a 404', function () {
    $this->get(route('tags.edit', 'missing'))->assertNotFound();
});

test('shows where the page of the tag is generated', function () {
    aTag('Laravel', slug: 'framework');

    config()->set('pluma.tags.create_pages', true);

    Livewire::test('pages::tag.edit', ['id' => 'laravel'])
        ->assertSee('Page at /tags/framework/');

    config()->set('pluma.tags.create_pages', false);

    Livewire::test('pages::tag.edit', ['id' => 'laravel'])
        ->assertSee('No page');
});

test('renaming the tag saves its name and the slug that follows it', function () {
    aTag('Larvel');

    Livewire::test('pages::tag.edit', ['id' => 'larvel'])
        ->set('name', 'Laravel')
        ->assertHasNoErrors()
        ->assertSet('slug', 'laravel');

    expect(tags()->find('larvel')->name)->toBe('Laravel');
});

test('a name that is already taken shows an error', function () {
    aTag('Laravel');
    aTag('PHP');

    Livewire::test('pages::tag.edit', ['id' => 'php'])
        ->set('name', 'Laravel')
        ->assertHasErrors('name');

    expect(tags()->find('php')->name)->toBe('PHP');
});

test('changing the slug saves it', function () {
    aTag('Laravel');

    Livewire::test('pages::tag.edit', ['id' => 'laravel'])
        ->set('slug', 'framework')
        ->assertHasNoErrors();

    expect(tags()->find('laravel')->slug)->toBe('framework');
});

test('an invalid slug shows an error', function () {
    aTag('Laravel');

    Livewire::test('pages::tag.edit', ['id' => 'laravel'])
        ->set('slug', 'php/laravel')
        ->assertHasErrors('slug');

    expect(tags()->find('laravel')->slug)->toBe('laravel');
});

test('editing the content saves it and regenerates its page', function () {
    config()->set('pluma.tags.create_pages', true);

    aTag('Laravel');

    Livewire::test('pages::tag.edit', ['id' => 'laravel'])
        ->set('content', 'Everything about Laravel');

    expect((string) tags()->find('laravel')->content)->toBe('Everything about Laravel')
        ->and(disk()->get('site/tags/laravel/index.html'))->toContain('Everything about Laravel');
});

test('uploading an asset stores it under the tag pages path and the id of the tag', function () {
    config()->set('pluma.tags.create_pages', true);
    config()->set('pluma.tags.pages_path', 'etiquetas');

    aTag('Laravel', slug: 'framework');

    $file = UploadedFile::fake()->createWithContent('notes.txt', 'hello');

    Livewire::test('pages::tag.edit', ['id' => 'laravel'])
        ->set('newAssets', [$file])
        ->assertSet('newAssets', [])
        ->assertSet('assets', [['filename' => 'notes.txt', 'url' => route('tags.assets.show', ['laravel', 'notes.txt'])]]);

    expect('assets/tags/laravel/notes.txt')->toExistOnDisk()
        ->and('site/etiquetas/framework/notes.txt')->toExistOnDisk();
});

test('setting a cover image saves it and copies it to the generated site', function () {
    config()->set('pluma.tags.create_pages', true);

    aTag('Laravel');

    disk()->put('assets/tags/laravel/header.png', 'binary');

    Livewire::test('pages::tag.edit', ['id' => 'laravel'])
        ->call('setCoverImage', 'header.png')
        ->assertSet('cover_image', 'header.png');

    expect(tags()->find('laravel')->cover_image)->toBe('header.png')
        ->and('site/tags/laravel/header.png')->toExistOnDisk()
        ->and(disk()->get('site/tags/laravel/index.html'))->toContain('src="header.png"');
});

test('deleting the cover image asset clears it from the tag and the generated site', function () {
    config()->set('pluma.tags.create_pages', true);

    aTag('Laravel', cover_image: 'header.png');

    disk()->put('assets/tags/laravel/header.png', 'binary');
    disk()->put('site/tags/laravel/header.png', 'binary');

    Livewire::test('pages::tag.edit', ['id' => 'laravel'])
        ->call('deleteAsset', 'header.png')
        ->assertSet('cover_image', null);

    expect(tags()->find('laravel')->cover_image)->toBeNull()
        ->and('assets/tags/laravel/header.png')->toBeMissingFromDisk()
        ->and('site/tags/laravel/header.png')->toBeMissingFromDisk();
});

test('serves the assets of a tag', function () {
    aTag('Laravel');

    disk()->put('assets/tags/laravel/notes.txt', 'hello');

    $this->get(route('tags.assets.show', ['laravel', 'notes.txt']))->assertSuccessful();
    $this->get(route('tags.assets.show', ['laravel', 'missing.txt']))->assertNotFound();
    $this->get(route('tags.assets.show', ['missing', 'notes.txt']))->assertNotFound();
});
