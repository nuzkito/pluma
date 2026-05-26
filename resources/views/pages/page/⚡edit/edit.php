<?php

use App\Domain\Attachment\AttachmentRepository;
use App\Domain\Attachment\DeleteAttachment;
use App\Domain\Attachment\UploadAttachment;
use App\Domain\Page\AddPageTag;
use App\Domain\Page\DeletePage;
use App\Domain\Page\Page;
use App\Domain\Page\PageRepository;
use App\Domain\Page\PublishPage;
use App\Domain\Page\RemovePageTag;
use App\Domain\Page\UnpublishPage;
use App\Domain\Page\UpdatePageContent;
use App\Domain\Page\UpdatePagePath;
use App\Domain\Page\UpdatePagePublishedAt;
use App\Domain\Page\UpdatePageRss;
use App\Domain\Page\UpdatePageTitle;
use Illuminate\Support\Arr;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
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

    public array $attachments;

    public string $oldPath;

    public array $availableTags = [];

    #[Validate(['newAttachments.*' => 'file|max:20480'])]
    public array $newAttachments = [];

    public function mount(PageRepository $repository, AttachmentRepository $attachmentRepo, string $path)
    {
        $page = $repository->findByPath($path);

        if (!$page) {
            abort(404);
        }

        $this->title = $page->title;
        $this->path = (string) $page->path;
        $this->oldPath = (string) $page->path;
        $this->content = (string) $page->content;
        $this->rss = $page->rss;
        $this->published_at = $page->published_at?->format('Y-m-d\TH:i');
        $this->tags = $page->tags;
        $this->attachments = $attachmentRepo->all($page->path);
        $this->availableTags = $repository->all()
            ->flatMap(fn (Page $p) => $p->tags)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function render(PageRepository $repository, AttachmentRepository $attachmentRepo)
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
            ok: function (Page $page) {
                $this->oldPath = $page->path->__toString();
                $this->path = $page->path->__toString();
                $this->dispatch('url-changed', $page->path->__toString());
            },
            error: fn(string $error) => $this->addError('title', $error),
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
            error: fn(string $error) => $this->addError('path', $error),
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

    public function addTag(AddPageTag $addTagAction, string $tag)
    {
        if (trim($tag) === '') {
            return;
        }

        $page = $addTagAction->__invoke($this->path, $tag);

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

    public function updatedNewAttachments(UploadAttachment $uploadAttachment)
    {
        $newAttachments = $uploadAttachment->__invoke($this->path, $this->newAttachments);

        $this->attachments = Arr::sort(
            array_values([...$this->attachments, ...$newAttachments]),
            fn($attachment) => $attachment['filename'],
        );

        $this->newAttachments = [];
    }

    public function deleteAttachment(DeleteAttachment $deleteAttachment, string $filename)
    {
        $deleteAttachment->__invoke($this->path, $filename);

        $this->attachments = array_values(array_filter($this->attachments, fn($a) => $a['filename'] !== $filename));
    }

    private function isImage(string $filename): bool
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        return in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'], true);
    }
};
