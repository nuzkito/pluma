<?php

namespace App\Domain\Settings;

class SettingDefinition
{
    /**
     * @param  list<string>  $rules
     * @param  array<string, string>  $options
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $description,
        public SettingType $type,
        public string $group,
        public array $rules = [],
        public array $options = [],
    ) {}
}
