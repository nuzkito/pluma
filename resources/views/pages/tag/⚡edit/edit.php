<?php

use App\Domain\Editor\Asset\AssetRepository;
use App\Domain\Editor\Tag\ChangeTagCoverImage;
use App\Domain\Editor\Tag\DeleteTagAsset;
use App\Domain\Editor\Tag\RenameTag;
use App\Domain\Editor\Tag\Tag;
use App\Domain\Editor\Tag\TagRepository;
use App\Domain\Editor\Tag\UpdateTagContent;
use App\Domain\Editor\Tag\UpdateTagSlug;
use App\Domain\Editor\Tag\UploadTagAsset;
use Illuminate\Support\Arr;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $tagId;

    #[Validate('required|string|max:255')]
    public string $name;

    #[Validate('required|string|max:255')]
    public string $slug;

    public string $content;

    public ?string $cover_image = null;

    public array $assets;

    #[Validate(['newAssets.*' => 'file|max:12288'])]
    public array $newAssets = [];

    public function mount(TagRepository $repository, AssetRepository $assetRepo, string $id)
    {
        $tag = $repository->find($id);

        if (! $tag) {
            abort(404);
        }

        $this->tagId = $tag->id;
        $this->name = $tag->name;
        $this->slug = $tag->slug;
        $this->content = (string) $tag->content;
        $this->cover_image = $tag->cover_image;
        $this->assets = array_map(fn (string $filename) => [
            'filename' => $filename,
            'url' => route('tags.assets.show', [$tag->id, $filename]),
        ], $assetRepo->filenames($tag->assetsPath()));
    }

    public function render(TagRepository $repository)
    {
        return $this->view([
            'tag' => $repository->find($this->tagId),
        ]);
    }

    public function updatedName(RenameTag $action, string $value)
    {
        $this->validateOnly('name');

        $action->__invoke($this->tagId, $value)->match(
            ok: function (Tag $tag) {
                $this->name = $tag->name;
                $this->slug = $tag->slug;
            },
            error: fn (string $error) => $this->addError('name', $error),
        );
    }

    public function updatedSlug(UpdateTagSlug $action, string $value)
    {
        $this->validateOnly('slug');

        $action->__invoke($this->tagId, $value)->match(
            ok: fn (Tag $tag) => $this->slug = $tag->slug,
            error: fn (string $error) => $this->addError('slug', $error),
        );
    }

    public function updatedContent(UpdateTagContent $action, string $value)
    {
        $action->__invoke($this->tagId, $value);
    }

    public function updatedNewAssets(UploadTagAsset $uploadTagAsset)
    {
        $newAssets = $uploadTagAsset->__invoke($this->tagId, $this->newAssets);

        $this->assets = Arr::sort(
            array_values([...$this->assets, ...$newAssets]),
            fn ($asset) => $asset['filename'],
        );

        $this->newAssets = [];
    }

    public function deleteAsset(DeleteTagAsset $deleteTagAsset, string $filename)
    {
        $deleteTagAsset->__invoke($this->tagId, $filename);

        $this->assets = array_filter($this->assets, fn ($a) => $a['filename'] !== $filename)
            |> array_values(...);

        if ($this->cover_image === $filename) {
            $this->cover_image = null;
        }
    }

    public function setCoverImage(ChangeTagCoverImage $changeTagCoverImage, string $filename)
    {
        $tag = $changeTagCoverImage->__invoke($this->tagId, $filename);

        $this->cover_image = $tag->cover_image;
    }
};
