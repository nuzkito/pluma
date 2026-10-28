<?php

use App\Domain\Editor\Type\CreateType;

test('stores only the name of the type in its file', function () {
    $type = app(CreateType::class)('  Recipe  ');

    expect($type->id)->toBe('recipe')
        ->and(disk()->get('types/recipe.md'))->toBe("---\nname: Recipe\n---\n");
});

test('returns the existing type with the same name instead of creating another one', function () {
    $type = app(CreateType::class)('POST');

    expect($type->id)->toBe('post')
        ->and($type->name)->toBe('Post')
        ->and(types()->all())->toHaveCount(2);
});

test('gives a new id to a type whose name generates the id of another one', function () {
    $type = app(CreateType::class)('Post!');

    expect($type->id)->toBe('post-2')
        ->and($type->name)->toBe('Post!');
});
