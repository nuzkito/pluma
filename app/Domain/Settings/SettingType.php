<?php

namespace App\Domain\Settings;

use Carbon\Carbon;
use Illuminate\Support\Collection;

use function is_array;

enum SettingType: string
{
    case String = 'string';
    case Boolean = 'boolean';
    case Integer = 'integer';
    case DateTime = 'datetime';
    case List = 'list';

    /**
     * Coerce a value decoded from the settings JSON into the type used in config.
     */
    public function cast(mixed $value): mixed
    {
        return match ($this) {
            self::String => (string) $value,
            self::Boolean => (bool) $value,
            self::Integer => (int) $value,
            self::DateTime => Carbon::parse($value),
            self::List => $this->toList($value),
        };
    }

    /**
     * Prepare a config value for binding to the form input.
     */
    public function forForm(mixed $value): mixed
    {
        return match ($this) {
            self::List => implode("\n", $this->toList($value)),
            self::DateTime => $value instanceof Carbon ? $value->format('Y-m-d\TH:i') : (string) $value,
            default => $value,
        };
    }

    /**
     * Convert a submitted form value back to the type stored in the settings JSON.
     */
    public function fromForm(mixed $value): mixed
    {
        return match ($this) {
            self::String => (string) $value,
            self::Boolean => (bool) $value,
            self::Integer => (int) $value,
            self::DateTime => Carbon::parse($value)->toIso8601String(),
            self::List => Collection::make(preg_split('/\r\n|\r|\n/', (string) $value))
                ->map(trim(...))
                ->filter()
                ->values()
                ->all(),
        };
    }

    /**
     * @return list<string>
     */
    private function toList(mixed $value): array
    {
        return Collection::make(is_array($value) ? $value : [])
            ->map(fn (mixed $item): string => (string) $item)
            ->values()
            ->all();
    }
}
