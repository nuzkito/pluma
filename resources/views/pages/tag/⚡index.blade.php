<?php

use App\Domain\Editor\Tag\TagRepository;
use Livewire\Component;

new class extends Component {
    public function render(TagRepository $tags)
    {
        return $this->view([
            'tags' => $tags->all(),
        ]);
    }
};
?>

<div class="grid gap-6">
    <flux:heading size="lg">Tags</flux:heading>

    @if(! config('pluma.tags.create_pages'))
        <flux:callout icon="information-circle">
            <flux:callout.text>Tag pages are disabled in the <flux:link :href="route('settings.index')" wire:navigate>settings</flux:link>, so the site does not have a page for each tag.</flux:callout.text>
        </flux:callout>
    @endif

    @if($tags->isNotEmpty())
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Slug</flux:table.column>
                <flux:table.column>Created</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($tags as $tag)
                    <flux:table.row :key="$tag->id">
                        <flux:table.cell align="start">
                            <a href="{{ route('tags.edit', $tag->id) }}" wire:navigate.hover class="flex items-center gap-2 text-blue-600 hover:underline">
                                <flux:icon name="tag" variant="micro" />
                                {{ $tag->name }}
                            </a>
                        </flux:table.cell>
                        <flux:table.cell>{{ $tag->slug }}</flux:table.cell>
                        <flux:table.cell>{{ $tag->created_at->format('Y-m-d H:i') }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @else
        <div class="grid justify-items-center gap-4 py-12">
            <flux:text>There are no tags yet. Tags are created when you add them to a page.</flux:text>
        </div>
    @endif
</div>
