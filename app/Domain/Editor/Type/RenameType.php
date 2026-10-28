<?php

namespace App\Domain\Editor\Type;

use App\Domain\Error;
use App\Domain\Ok;
use App\Domain\Result;

class RenameType
{
    public function __construct(
        private TypeRepository $repository,
    ) {}

    /**
     * @return Result<Type>
     */
    public function __invoke(string $id, string $newName): Result
    {
        $newName = trim($newName);
        $type = $this->repository->find($id);

        if ($newName === '') {
            return new Error('The name of a type cannot be empty.');
        }

        if ($this->repository->findByName($newName, $id) !== null) {
            return new Error('Another type already has this name.');
        }

        $type->rename($newName);

        $this->repository->save($type);

        return new Ok($type);
    }
}
