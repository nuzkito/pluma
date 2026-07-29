<?php

use App\Domain\Editor\Asset\AssetRepository;
use App\Domain\Editor\Asset\DeleteAsset;
use App\Domain\Editor\Asset\UploadAsset;
use App\Domain\Editor\Page\AddPageTag;
use App\Domain\Editor\Page\ChangePageCoverImage;
use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\CreateTagPage;
use App\Domain\Editor\Page\DeletePage;
use App\Domain\Editor\Page\PageRepository;
use App\Domain\Editor\Page\PublishPage;
use App\Domain\Editor\Page\RemovePageTag;
use App\Domain\Editor\Page\UnpublishPage;
use App\Domain\Editor\Page\UpdatePageContent;
use App\Domain\Editor\Page\UpdatePagePath;
use App\Domain\Editor\Page\UpdatePagePublishedAt;
use App\Domain\Editor\Page\UpdatePageRss;
use App\Domain\Editor\Page\UpdatePageTitle;
use Illuminate\Support\Arr;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    #[Validate('required|string|max:255')]
    public string $title;

    #[Validate('required|string|max:255')]
    public string $path;

    public string $content;

    public bool $rss = false;

    #[Validate('nullable|date')]
    public ?string $published_at;

    public array $tags;

    public ?string $cover_image = null;

    public array $assets;

    public string $oldPath;

    public array $availableTags = [];

    #[Validate(['newAssets.*' => 'file|max:12288'])]
    public array $newAssets = [];

    public function mount(PageRepository $repository, AssetRepository $assetRepo, string $path)
    {
        $page = $repository->findByPath($path);

        if (! $page) {
            abort(404);
        }

        $this->title = $page->title;
        $this->path = (string) $page->path;
        $this->oldPath = (string) $page->path;
        $this->content = (string) $page->content;
        $this->rss = $page->rss;
        $this->published_at = $page->published_at?->format('Y-m-d\TH:i');
        $this->tags = $page->tags;
        $this->cover_image = $page->cover_image;
        $this->assets = $assetRepo->all($page->path);
        $this->availableTags = $repository->all()
            ->flatMap(fn (ContentPage $p) => $p->tags)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function render(PageRepository $repository, AssetRepository $assetRepo)
    {
        return $this->view([
            'page' => $repository->findByPath($this->path),
        ]);
    }

    public function updatedTitle(UpdatePageTitle $action, string $value)
    {
        $this->validate();

        $result = $action->__invoke($this->oldPath, $value);

        $result->match(
            ok: function (ContentPage $page) {
                $this->oldPath = $page->path->__toString();
                $this->path = $page->path->__toString();
                $this->dispatch('url-changed', $page->path->__toString());
            },
            error: fn (string $error) => $this->addError('title', $error),
        );
    }

    public function updatedPath(UpdatePagePath $action, string $newPath)
    {
        $this->validate();

        $result = $action->__invoke($this->oldPath, $newPath);

        $result->match(
            ok: function () use ($newPath) {
                $this->dispatch('url-changed', $newPath);
                $this->oldPath = $newPath;
            },
            error: fn (string $error) => $this->addError('path', $error),
        );
    }

    public function updatedContent(UpdatePageContent $action, string $value)
    {
        $action->__invoke($this->path, $value);
    }

    public function updatedRss(UpdatePageRss $action, bool $value)
    {
        $action->__invoke($this->path, $value);
    }

    public function updatedPublishedAt(UpdatePagePublishedAt $action, ?string $value)
    {
        $action->__invoke($this->path, $value);
    }

    public function addTag(AddPageTag $addTagAction, CreateTagPage $createTagPage, string $tag)
    {
        if (trim($tag) === '') {
            return;
        }

        $page = $addTagAction->__invoke($this->path, $tag);
        $createTagPage->__invoke($tag);

        $this->tags = $page->tags;
    }

    public function removeTag(RemovePageTag $removeTagAction, int $index)
    {
        $page = $removeTagAction->__invoke($this->path, $index);

        $this->tags = $page->tags;
    }

    public function publish(PublishPage $publishPage)
    {
        $page = $publishPage->__invoke($this->path);

        $this->published_at = $page->published_at->format('Y-m-d\TH:i');
    }

    public function unpublish(UnpublishPage $unpublishPage)
    {
        $unpublishPage->__invoke($this->path);

        $this->published_at = null;
    }

    public function delete(DeletePage $deletePage)
    {
        $deletePage->__invoke($this->path);

        return redirect()->route('pages.index');
    }

    public function updatedNewAssets(UploadAsset $uploadAsset)
    {
        $newAssets = $uploadAsset->__invoke($this->path, $this->newAssets);

        $this->assets = Arr::sort(
            array_values([...$this->assets, ...$newAssets]),
            fn ($asset) => $asset['filename'],
        );

        $this->newAssets = [];
    }

    public function deleteAsset(DeleteAsset $deleteAsset, string $filename)
    {
        $deleteAsset->__invoke($this->path, $filename);

        $this->assets = array_values(array_filter($this->assets, fn ($a) => $a['filename'] !== $filename));

        if ($this->cover_image === $filename) {
            $this->cover_image = null;
        }
    }

    public function setCoverImage(ChangePageCoverImage $changePageCoverImage, string $filename)
    {
        $page = $changePageCoverImage->__invoke($this->path, $filename);

        $this->cover_image = $page->cover_image;
    }

    private function isImage(string $filename): bool
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        return in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'], true);
    }
};
