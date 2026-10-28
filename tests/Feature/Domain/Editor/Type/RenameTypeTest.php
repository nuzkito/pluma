<?php

use App\Domain\Editor\Type\RenameType;

test('renames the type without changing its id', function () {
    aPage('A Post', 'a-post', type: 'post');

    $result = app(RenameType::class)('post', 'Article');

    expect($result)->toBeOk()
        ->and(types()->find('post')->name)->toBe('Article')
        ->and(repository()->findByPath('a-post')->type)->toBe('post');
});

test('rejects an empty name', function () {
    expect(app(RenameType::class)('post', '  '))->toBeError('The name of a type cannot be empty.')
        ->and(types()->find('post')->name)->toBe('Post');
});

test('rejects the name of another type', function () {
    expect(app(RenameType::class)('post', 'page'))->toBeError('Another type already has this name.')
        ->and(types()->find('post')->name)->toBe('Post');
});
