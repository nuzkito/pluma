<?php

namespace App\Domain\TagMigration;

use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Tag\Tag;
use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\CommonMark\Extension\FrontMatter\Data\SymfonyYamlFrontMatterParser;
use League\CommonMark\Extension\FrontMatter\FrontMatterParser;

class LegacyTagPageRepository
{
    private const string BASE_DIRECTORY = 'pages';

    private Filesystem $disk;

    public function __construct()
    {
        $this->disk = Storage::disk('current');
    }

    /**
     * @return Collection<string, Tag>
     */
    public function all(): Collection
    {
        if (! $this->disk->exists(self::BASE_DIRECTORY)) {
            return collect();
        }

        return collect($this->disk->allFiles(self::BASE_DIRECTORY))
            ->filter(fn (string $file) => Str::of($file)->endsWith('.tag.md'))
            ->mapWithKeys(function (string $file) {
                $path = Str::of($file)->after(self::BASE_DIRECTORY.'/')->beforeLast('.tag.md')->toString();

                return [$path => $this->fromFile($file, $path)];
            });
    }

    public function delete(string $path): void
    {
        $this->disk->delete(self::BASE_DIRECTORY."/$path.tag.md");
    }

    private function fromFile(string $filePath, string $path): Tag
    {
        $parser = new FrontMatterParser(new SymfonyYamlFrontMatterParser);
        $parsed = $parser->parse($this->disk->get($filePath));
        $metadata = $parsed->getFrontMatter();
        $id = Str::afterLast($path, '/');

        return new Tag(
            id: $id,
            name: $metadata['title'] ?? $id,
            slug: $id,
            content: new Markdown($parsed->getContent()),
            created_at: Carbon::parse($metadata['created_at'] ?? null),
            cover_image: $metadata['cover_image'] ?? null,
        );
    }
}
