<?php

use App\Domain\Editor\Page\DeleteType;
use App\Domain\Editor\Page\PageRepository;
use Livewire\Component;

new class extends Component {
    public function delete(DeleteType $deleteType, string $type)
    {
        $deleteType($type);
    }

    public function render(PageRepository $pages)
    {
        return $this->view([
            'types' => $pages->typeCounts(),
        ]);
    }
};
?>

<div class="grid gap-6">
    <flux:heading size="lg">Types</flux:heading>

    @if($types->isNotEmpty())
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Pages</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($types as $type => $count)
                    <flux:table.row :key="$type">
                        <flux:table.cell>{{ $type }}</flux:table.cell>
                        <flux:table.cell>{{ $count }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button
                                wire:click="delete({{ \Illuminate\Support\Js::from($type) }})"
                                variant="danger"
                                size="sm"
                                icon="trash"
                            >Delete</flux:button>
                        </flux:table.cell>
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
