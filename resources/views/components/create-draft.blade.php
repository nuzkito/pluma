<?php

use App\Domain\Editor\Page\Page;
use App\Domain\Editor\Page\DraftNameGenerator;
use App\Domain\Editor\Page\PageRepository;
use App\Domain\Editor\Type\TypeRepository;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component {
    public string $variant = 'subtle';

    public ?string $size = null;

    public string $directory = '';

    public function create(PageRepository $repository, DraftNameGenerator $generator, TypeRepository $types)
    {
        $title = $generator->__invoke($this->directory);
        $path = trim("{$this->directory}/".Str::slug($title), '/');
        $defaultType = $types->find((string) config('pluma.types.default'));
        $page = Page::draft($title, $path, $defaultType?->id);

        $repository->save($page);

        return redirect()->route('pages.edit', $page->path);
    }
};
?>

<flux:button wire:click="create" :variant="$this->variant" :size="$this->size" icon="plus" class="{{ $attributes->get('class') }} cursor-pointer">New page</flux:button>
