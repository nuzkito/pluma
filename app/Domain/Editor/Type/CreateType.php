<?php

namespace App\Domain\Editor\Type;

class CreateType
{
    public function __construct(
        private TypeRepository $repository,
    ) {}

    public function __invoke(string $name): Type
    {
        $name = trim($name);

        $existingType = $this->repository->findByName($name);

        if ($existingType !== null) {
            return $existingType;
        }

        $type = new Type($this->repository->nextId($name), $name);

        $this->repository->save($type);

        return $type;
    }
}
