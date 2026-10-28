<?php

use App\Domain\Editor\Page\PageRepository;
use App\Domain\Editor\Type\TypeRepository;
use Livewire\Component;

new class extends Component {
    public function render(TypeRepository $types, PageRepository $pages)
    {
        return $this->view([
            'types' => $types->all(),
            'pageCounts' => $pages->countByType(),
        ]);
    }
};
?>

<div class="grid gap-6">
    <flux:heading size="lg">Types</flux:heading>

    @if($types->isNotEmpty())
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Pages</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($types as $type)
                    <flux:table.row :key="$type->id">
                        <flux:table.cell align="start">
                            <a href="{{ route('types.edit', $type->id) }}" wire:navigate.hover class="flex items-center gap-2 text-blue-600 hover:underline">
                                <flux:icon name="squares-2x2" variant="micro" />
                                {{ $type->name }}
                            </a>
                        </flux:table.cell>
                        <flux:table.cell>{{ $pageCounts->get($type->id, 0) }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @else
        <div class="grid justify-items-center gap-4 py-12">
            <flux:text>There are no types yet. Types are created when you set them on a page.</flux:text>
        </div>
    @endif
</div>
