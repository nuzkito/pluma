<?php

use App\Domain\Editor\Page\DirectoryRepository;
use App\Domain\Editor\Page\PageRepository;
use Livewire\Component;

new class extends Component {
    public string $directory = '';

    public function mount(string $directory = ''): void
    {
        $this->directory = trim($directory, '/');
    }

    public function render(PageRepository $pages, DirectoryRepository $directories)
    {
        return $this->view([
            'pages' => $pages->searchByDirectory($this->directory),
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
