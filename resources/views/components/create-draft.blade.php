<?php

use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\DraftNameGenerator;
use App\Domain\Editor\Page\PageRepository;
use Livewire\Component;

new class extends Component {
    public string $variant = 'subtle';

    public ?string $size = null;

    public function create(PageRepository $repository, DraftNameGenerator $generator)
    {
        $title = $generator();
        $page = ContentPage::draft($title);
        $repository->save($page);

        return redirect()->route('pages.edit', $page->path);
    }
};
?>

<flux:button wire:click="create" :variant="$this->variant" :size="$this->size" icon="plus" class="{{ $attributes->get('class') }}">New page</flux:button>