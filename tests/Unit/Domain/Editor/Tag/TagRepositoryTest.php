<?php

use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Tag\Tag;
use App\Domain\Editor\Tag\TagRepository;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('current');
});

test('saves a tag to the tags directory and reads it back', function () {
    $repository = new TagRepository;

    $tag = Tag::create('laravel', 'Laravel');
    $tag->changeSlug('framework');
    $tag->setContent(new Markdown('All about Laravel'));
    $repository->save($tag);

    $found = $repository->find('laravel');

    expect('tags/laravel.md')->toExistOnDisk()
        ->and($found->name)->toBe('Laravel')
        ->and($found->slug)->toBe('framework')
        ->and((string) $found->content)->toBe('All about Laravel');
});

test('returns null for a tag without a file', function () {
    expect((new TagRepository)->find('missing'))->toBeNull();
});

test('never reads files outside the tags directory', function (string $id) {
    disk()->put('secret.md', "---\nname: Secret\ncreated_at: '2025-01-01T00:00:00+00:00'\n---\n");
    disk()->put('tags/nested/secret.md', "---\nname: Secret\ncreated_at: '2025-01-01T00:00:00+00:00'\n---\n");

    expect((new TagRepository)->find($id))->toBeNull();
})->with(['../secret', 'nested/secret', '', '.', '..']);

test('lists every tag sorted by name', function () {
    $repository = new TagRepository;

    $repository->save(Tag::create('php', 'php'));
    $repository->save(Tag::create('laravel', 'Laravel'));
    $repository->save(Tag::create('alpine', 'Alpine'));

    expect($repository->all()->pluck('name')->all())->toBe(['Alpine', 'Laravel', 'php']);
});

test('finds a tag by its name ignoring the case', function () {
    $repository = new TagRepository;

    $repository->save(Tag::create('laravel', 'Laravel'));

    expect($repository->findByName('LARAVEL')?->id)->toBe('laravel')
        ->and($repository->findByName('laravel', excludeId: 'laravel'))->toBeNull();
});

test('builds a new id from the name that no other tag uses', function () {
    $repository = new TagRepository;

    expect($repository->nextId('Cosas varias'))->toBe('cosas-varias');

    $repository->save(Tag::create('cosas-varias', 'Cosas varias'));

    expect($repository->nextId('Cosas Varias!'))->toBe('cosas-varias-2')
        ->and($repository->nextId('!!!'))->toBe('tag');
});
