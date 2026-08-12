<?php

use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Page\TagPage;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

test('a tag page opens in the editor with its title, path and content', function () {
    $tagPage = TagPage::create('Laravel');
    $tagPage->setContent(new Markdown('All about Laravel'));
    repository()->save($tagPage);

    Livewire::test('pages::page.edit', ['path' => 'tags/laravel'])
        ->assertSet('isTagPage', true)
        ->assertSet('title', 'Laravel')
        ->assertSet('path', 'tags/laravel')
        ->assertSet('content', 'All about Laravel');
});

test('the title and path fields are read only', function () {
    repository()->save(TagPage::create('Laravel'));
    aPage('A Regular Page', 'a-regular-page');

    Livewire::test('pages::page.edit', ['path' => 'tags/laravel'])
        ->assertSeeHtml('readonly');

    Livewire::test('pages::page.edit', ['path' => 'a-regular-page'])
        ->assertDontSeeHtml('readonly');
});

test('the fields that do not apply to a tag page are hidden', function () {
    config(['pluma.rss.enabled' => true]);

    repository()->save(TagPage::create('Laravel'));

    Livewire::test('pages::page.edit', ['path' => 'tags/laravel'])
        ->assertDontSee('Include in RSS feed')
        ->assertDontSeeHtml('id="tags-list"')
        ->assertDontSee('Published at')
        ->assertDontSeeHtml('wire:click="publish"')
        ->assertDontSeeHtml('wire:click="unpublish"')
        ->assertDontSeeHtml('wire:click="delete"');
});

test('editing the content saves it and regenerates the tag page', function () {
    repository()->save(TagPage::create('Laravel'));

    Livewire::test('pages::page.edit', ['path' => 'tags/laravel'])
        ->set('content', 'Everything about Laravel')
        ->assertSet('content', 'Everything about Laravel');

    expect((string) repository()->findByPath('tags/laravel')->content)->toBe('Everything about Laravel')
        ->and(disk()->get('site/tags/laravel/index.html'))->toContain('Everything about Laravel');
});

test('setting a cover image saves it and copies it to the generated site', function () {
    repository()->save(TagPage::create('Laravel'));

    disk()->put('assets/tags/laravel/header.png', 'binary');

    Livewire::test('pages::page.edit', ['path' => 'tags/laravel'])
        ->call('setCoverImage', 'header.png')
        ->assertSet('cover_image', 'header.png');

    expect(repository()->findByPath('tags/laravel')->cover_image)->toBe('header.png')
        ->and('site/tags/laravel/header.png')->toExistOnDisk();
});

test('deleting the cover image asset clears it from the tag page', function () {
    $tagPage = TagPage::create('Laravel');
    $tagPage->changeCoverImage('header.png');
    repository()->save($tagPage);

    disk()->put('assets/tags/laravel/header.png', 'binary');

    Livewire::test('pages::page.edit', ['path' => 'tags/laravel'])
        ->call('deleteAsset', 'header.png')
        ->assertSet('cover_image', null);

    expect(repository()->findByPath('tags/laravel')->cover_image)->toBeNull()
        ->and('assets/tags/laravel/header.png')->toBeMissingFromDisk();
});

test('uploading an asset stores it under the tag page path', function () {
    repository()->save(TagPage::create('Laravel'));

    $file = UploadedFile::fake()->createWithContent('notes.txt', 'hello');

    Livewire::test('pages::page.edit', ['path' => 'tags/laravel'])
        ->set('newAssets', [$file])
        ->assertSet('newAssets', []);

    expect('assets/tags/laravel/notes.txt')->toExistOnDisk();
});

test('the actions that do not apply to a tag page leave it untouched', function () {
    repository()->save(TagPage::create('Laravel'));

    Livewire::test('pages::page.edit', ['path' => 'tags/laravel'])
        ->set('title', 'PHP')
        ->set('path', 'tags/php')
        ->call('addTag', 'php')
        ->call('publish')
        ->call('unpublish')
        ->call('delete')
        ->assertOk()
        ->assertSet('path', 'tags/laravel');

    $tagPage = repository()->findByPath('tags/laravel');

    expect($tagPage)->not->toBeNull()
        ->and($tagPage->title)->toBe('Laravel')
        ->and($tagPage->tags)->toBe([])
        ->and('pages/tags/php.tag.md')->toBeMissingFromDisk();
});
