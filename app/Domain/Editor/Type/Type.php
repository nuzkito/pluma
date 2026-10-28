<?php

namespace App\Domain\Editor\Type;

class Type
{
    private const string IDENTIFIER_PATTERN = '/^[A-Za-z0-9-]+$/';

    public function __construct(
        public readonly string $id,
        public string $name,
    ) {}

    public static function isValidIdentifier(string $value): bool
    {
        return preg_match(self::IDENTIFIER_PATTERN, $value) === 1;
    }

    public function rename(string $newName): void
    {
        $this->name = $newName;
    }

    public function hasName(string $name): bool
    {
        return mb_strtolower($this->name) === mb_strtolower($name);
    }

    public function filename(): string
    {
        return "{$this->id}.md";
    }
}
