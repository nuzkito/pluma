<?php

use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\DirectoryRepository;
use App\Domain\Editor\Page\PageRepository;
use App\Domain\Editor\Page\TagPage;
use Livewire\Component;

new class extends Component {
    public string $directory = '';

    public function mount(string $directory = ''): void
    {
        $this->directory = trim($directory, '/');
    }

    public function render(PageRepository $pages, DirectoryRepository $directories)
    {
        $entries = $pages->searchByDirectory($this->directory);

        return $this->view([
            'pages' => $entries->whereInstanceOf(ContentPage::class)->values(),
            'tags' => $entries->whereInstanceOf(TagPage::class)->sortBy('title')->values(),
            'directories' => $directories->searchByDirectory($this->directory),
        ]);
    }
};
?>

<div class="grid gap-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <x-path-breadcrumbs :path="$directory" />
        </div>
        <div class="flex gap-2">
            <flux:button :href="route('directories.create', ['directory' => $directory])" icon="folder-plus" wire:navigate>New directory</flux:button>
            <livewire:create-draft :directory="$directory" variant="primary" />
        </div>
    </div>

    @if($directories->isNotEmpty())
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Directory</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($directories as $folder)
                    <flux:table.row>
                        <flux:table.cell align="start">
                            <a href="{{ route('pages.index', ['directory' => $folder->path]) }}" wire:navigate class="flex items-center gap-2 text-blue-600 hover:underline">
                                <flux:icon name="folder" variant="micro" />
                                {{ $folder->name() }}
                            </a>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif

    @if($tags->isNotEmpty())
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Tag</flux:table.column>
                <flux:table.column>Created</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($tags as $tagPage)
                    <flux:table.row>
                        <flux:table.cell align="start">
                            <a href="{{ route('pages.edit', $tagPage->path) }}" wire:navigate.hover class="flex items-center gap-2 text-blue-600 hover:underline">
                                <flux:icon name="tag" variant="micro" />
                                {{ $tagPage->title }}
                            </a>
                        </flux:table.cell>
                        <flux:table.cell>{{ $tagPage->created_at->format('Y-m-d H:i') }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif

    @if($pages->isNotEmpty())
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Title</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Tags</flux:table.column>
                <flux:table.column>Created</flux:table.column>
                <flux:table.column>Published</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($pages as $page)
                    <flux:table.row>
                        <flux:table.cell align="start">
                            <a href="{{ route('pages.edit', $page->path) }}" wire:navigate.hover class="text-blue-600 hover:underline">{{ $page->title }}</a>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($page->isPublished())
                                <flux:badge color="emerald">Published</flux:badge>
                            @else
                                <flux:badge color="amber">Draft</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @foreach($page->tags as $tag)
                                <flux:badge>{{ $tag }}</flux:badge>
                            @endforeach
                        </flux:table.cell>
                        <flux:table.cell>{{ $page->created_at->format('Y-m-d H:i') }}</flux:table.cell>
                        <flux:table.cell>{{ $page->published_at?->format('Y-m-d H:i') ?? '-' }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
