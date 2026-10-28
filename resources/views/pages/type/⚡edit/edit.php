<?php

use App\Domain\Editor\Type\DeleteType;
use App\Domain\Editor\Type\RenameType;
use App\Domain\Editor\Type\Type;
use App\Domain\Editor\Type\TypeRepository;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public string $typeId;

    #[Validate('required|string|max:255')]
    public string $name;

    public function mount(TypeRepository $repository, string $id)
    {
        $type = $repository->find($id);

        if (! $type) {
            abort(404);
        }

        $this->typeId = $type->id;
        $this->name = $type->name;
    }

    public function render(TypeRepository $repository)
    {
        return $this->view([
            'type' => $repository->find($this->typeId),
        ]);
    }

    public function updatedName(RenameType $action, string $value)
    {
        $this->validateOnly('name');

        $action->__invoke($this->typeId, $value)->match(
            ok: fn (Type $type) => $this->name = $type->name,
            error: fn (string $error) => $this->addError('name', $error),
        );
    }

    public function delete(DeleteType $deleteType)
    {
        $deleteType($this->typeId);

        return $this->redirectRoute('types.index', navigate: true);
    }
};
