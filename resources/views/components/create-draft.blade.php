<?php

use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\DraftNameGenerator;
use App\Domain\Editor\Page\PageRepository;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component {
    public string $variant = 'subtle';

    public ?string $size = null;

    public string $directory = '';

    public function create(PageRepository $repository, DraftNameGenerator $generator)
    {
        $title = $generator->__invoke($this->directory);
        $path = trim("{$this->directory}/".Str::slug($title), '/');
        $page = ContentPage::draft($title, $path);

        $repository->save($page);

        return redirect()->route('pages.edit', $page->path);
    }
};
?>

<flux:button wire:click="create" :variant="$this->variant" :size="$this->size" icon="plus" class="{{ $attributes->get('class') }} cursor-pointer">New page</flux:button>
