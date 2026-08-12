<?php

use App\Domain\Editor\Page\DirectoryRepository;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component {
    #[Url]
    public string $directory = '';

    public string $name = '';

    public function mount(): void
    {
        $this->directory = trim($this->directory, '/');
    }

    public function create(DirectoryRepository $directories)
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
        ]);

        $path = trim("{$this->directory}/{$validated['name']}", '/');

        if ($directories->exists($path)) {
            $this->addError('name', 'This directory already exists.');

            return;
        }

        $directories->create($path);

        return redirect()->route('pages.index', ['directory' => $this->directory]);
    }
};
?>

<div class="grid gap-6">
    <flux:heading size="xl">New directory</flux:heading>

    @if($directory !== '')
        <flux:text>Creating inside <flux:link :href="route('pages.index', ['directory' => $directory])" wire:navigate>{{ $directory }}</flux:link></flux:text>
    @endif

    <form wire:submit="create" class="grid gap-6">
        <flux:field>
            <flux:label>Name</flux:label>
            <flux:input wire:model="name" placeholder="blog" autofocus />
            <flux:error name="name" />
        </flux:field>

        <div class="flex justify-end gap-3">
            <flux:button :href="route('pages.index', ['directory' => $directory])" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">Create</flux:button>
        </div>
    </form>
</div>
