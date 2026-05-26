<?php

use App\Domain\Page\DraftNameGenerator;
use App\Domain\Page\Page;
use App\Domain\Page\PageRepository;
use Livewire\Component;

new class extends Component {
    public string $variant = 'subtle';

    public string $size = 'base';

    public function create(PageRepository $repository, DraftNameGenerator $generator)
    {
        $title = $generator();
        $page = Page::draft($title);
        $repository->save($page);

        return redirect()->route('pages.edit', $page->path);
    }
};
?>

<flux:button wire:click="create" variant="{{ $this->variant }}" size="{{ $this->size }}" icon="plus" class="{{ $attributes->get('class') }}">New page</flux:button>
