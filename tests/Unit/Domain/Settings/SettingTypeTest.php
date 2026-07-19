<?php

use App\Domain\Settings\SettingType;
use Carbon\Carbon;

test('cast coerces string values', function () {
    expect(SettingType::String->cast(123))->toBe('123');
});

test('cast coerces boolean values', function () {
    expect(SettingType::Boolean->cast(1))->toBeTrue()
        ->and(SettingType::Boolean->cast(0))->toBeFalse();
});

test('cast coerces integer values', function () {
    expect(SettingType::Integer->cast('42'))->toBe(42);
});

test('cast parses datetime values into Carbon', function () {
    $value = SettingType::DateTime->cast('2026-01-15T10:30:00+00:00');

    expect($value)->toBeInstanceOf(Carbon::class)
        ->and($value->toIso8601String())->toBe('2026-01-15T10:30:00+00:00');
});

test('cast coerces list items to strings', function () {
    expect(SettingType::List->cast(['youtube.com', 123]))->toBe(['youtube.com', '123']);
});

test('cast returns an empty list for non array values', function (mixed $value) {
    expect(SettingType::List->cast($value))->toBe([]);
})->with([
    'null' => [null],
    'string' => ['youtube.com'],
]);

test('forForm joins list values with newlines', function () {
    expect(SettingType::List->forForm(['youtube.com', 'vimeo.com']))->toBe("youtube.com\nvimeo.com");
});

test('forForm formats Carbon datetimes for the input', function () {
    expect(SettingType::DateTime->forForm(Carbon::parse('2026-01-15 10:30:00')))->toBe('2026-01-15T10:30');
});

test('forForm casts non Carbon datetimes to string', function () {
    expect(SettingType::DateTime->forForm('2026-01-15T10:30'))->toBe('2026-01-15T10:30');
});

test('forForm passes other types through unchanged', function () {
    expect(SettingType::String->forForm('My site'))->toBe('My site')
        ->and(SettingType::Boolean->forForm(true))->toBeTrue()
        ->and(SettingType::Integer->forForm(42))->toBe(42);
});

test('fromForm coerces scalar values', function () {
    expect(SettingType::String->fromForm(42))->toBe('42')
        ->and(SettingType::Boolean->fromForm('1'))->toBeTrue()
        ->and(SettingType::Boolean->fromForm(''))->toBeFalse()
        ->and(SettingType::Integer->fromForm('42'))->toBe(42);
});

test('fromForm stores datetimes as iso8601 strings', function () {
    expect(SettingType::DateTime->fromForm('2026-01-15T10:30'))
        ->toBe(Carbon::parse('2026-01-15T10:30')->toIso8601String());
});

test('fromForm splits list values on any newline style trimming and dropping blank lines', function () {
    expect(SettingType::List->fromForm("youtube.com\r\nvimeo.com\r  dailymotion.com  \n\ntwitch.tv"))
        ->toBe(['youtube.com', 'vimeo.com', 'dailymotion.com', 'twitch.tv']);
});

test('fromForm returns an empty list for an empty textarea', function () {
    expect(SettingType::List->fromForm(''))->toBe([]);
});
