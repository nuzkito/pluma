<?php

namespace App\Domain\Editor\Page;

use App\Domain\Editor\Type\CreateType;

class UpdatePageType
{
    public function __construct(
        private PageRepository $repository,
        private CreateType $createType,
        private SiteSynchronizer $site,
    ) {}

    public function __invoke(string $path, ?string $typeName): Page
    {
        $page = $this->repository->findByPath($path);

        $page->changeType(trim((string) $typeName) === '' ? null : $this->createType->__invoke($typeName)->id);

        $this->repository->save($page);

        $this->site->refresh($page);

        return $page;
    }
}
