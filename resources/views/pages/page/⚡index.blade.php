<?php

use App\Domain\Editor\Page\PageRepository;
use Livewire\Component;

new class extends Component {
    public function render(PageRepository $repository)
    {
        return $this->view([
            'pages' => $repository->all(),
        ]);
    }
};
?>

<div class="grid gap-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item separator="slash" icon="home"></flux:breadcrumbs.item>
        <flux:breadcrumbs.item></flux:breadcrumbs.item>
    </flux:breadcrumbs>

    @if($pages->isEmpty())
        <flux:text class="text-center">No pages yet. Create your first page.</flux:text>
        <livewire:create-draft variant="primary" class="mx-auto" />
    @else
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
