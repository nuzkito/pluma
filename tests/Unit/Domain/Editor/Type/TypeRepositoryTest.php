<?php

use App\Domain\Editor\Type\Type;
use App\Domain\Editor\Type\TypeRepository;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('current');
});

test('saves a type to the types directory and reads it back', function () {
    $repository = new TypeRepository;

    $repository->save(new Type('post', 'Post'));

    expect('types/post.md')->toExistOnDisk()
        ->and($repository->find('post')->name)->toBe('Post');
});

test('returns null for a type without a file', function () {
    expect((new TypeRepository)->find('missing'))->toBeNull();
});

test('never reads files outside the types directory', function (string $id) {
    disk()->put('secret.md', "---\nname: Secret\n---\n");
    disk()->put('types/nested/secret.md', "---\nname: Secret\n---\n");

    expect((new TypeRepository)->find($id))->toBeNull();
})->with(['../secret', 'nested/secret', '', '.', '..']);

test('lists every type sorted by name', function () {
    $repository = new TypeRepository;

    $repository->save(new Type('recipe', 'recipe'));
    $repository->save(new Type('post', 'Post'));
    $repository->save(new Type('article', 'Article'));

    expect($repository->all()->pluck('name')->all())->toBe(['Article', 'Post', 'recipe']);
});

test('finds a type by its name ignoring the case', function () {
    $repository = new TypeRepository;

    $repository->save(new Type('post', 'Post'));

    expect($repository->findByName('POST')?->id)->toBe('post')
        ->and($repository->findByName('post', excludeId: 'post'))->toBeNull();
});
