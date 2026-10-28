<?php

namespace App\Domain\Editor\Type;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\CommonMark\Extension\FrontMatter\Data\SymfonyYamlFrontMatterParser;
use League\CommonMark\Extension\FrontMatter\FrontMatterParser;
use Symfony\Component\Yaml\Yaml;

class TypeRepository
{
    private const string DIRECTORY_WHERE_TYPES_ARE_STORED = 'types';

    private Filesystem $disk;

    public function __construct()
    {
        $this->disk = Storage::disk('current');
    }

    /**
     * @return Collection<int, Type>
     */
    public function all(): Collection
    {
        if (! $this->disk->exists(self::DIRECTORY_WHERE_TYPES_ARE_STORED)) {
            return collect();
        }

        return collect($this->disk->files(self::DIRECTORY_WHERE_TYPES_ARE_STORED))
            ->filter(fn (string $file) => Str::of($file)->endsWith('.md'))
            ->map($this->fromFile(...))
            ->sortBy(fn (Type $type) => mb_strtolower($type->name))
            ->values();
    }

    public function find(string $id): ?Type
    {
        if (! Type::isValidIdentifier($id)) {
            return null;
        }

        $filePath = self::DIRECTORY_WHERE_TYPES_ARE_STORED."/$id.md";

        if (! $this->disk->exists($filePath)) {
            return null;
        }

        return $this->fromFile($filePath);
    }

    public function findByName(string $name, ?string $excludeId = null): ?Type
    {
        return $this->all()->first(
            fn (Type $type) => $type->id !== $excludeId && $type->hasName($name),
        );
    }

    public function nextId(string $name): string
    {
        $types = $this->all();
        $base = Str::slug($name) ?: 'type';
        $id = $base;
        $suffix = 2;

        while ($types->contains(fn (Type $type) => $type->id === $id)) {
            $id = "$base-$suffix";
            $suffix++;
        }

        return $id;
    }

    public function save(Type $type): void
    {
        $this->disk->makeDirectory(self::DIRECTORY_WHERE_TYPES_ARE_STORED);

        $yaml = Yaml::dump(['name' => $type->name]);

        $this->disk->put(self::DIRECTORY_WHERE_TYPES_ARE_STORED."/{$type->filename()}", "---\n$yaml---\n");
    }

    public function delete(Type $type): void
    {
        $this->disk->delete(self::DIRECTORY_WHERE_TYPES_ARE_STORED."/{$type->filename()}");
    }

    private function fromFile(string $filePath): Type
    {
        $parser = new FrontMatterParser(new SymfonyYamlFrontMatterParser);
        $metadata = $parser->parse($this->disk->get($filePath))->getFrontMatter();
        $id = basename($filePath, '.md');

        return new Type(
            id: $id,
            name: $metadata['name'] ?? $id,
        );
    }
}
