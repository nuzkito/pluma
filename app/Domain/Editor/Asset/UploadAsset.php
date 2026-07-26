<?php

namespace App\Domain\Editor\Asset;

use App\Domain\Editor\Page\PagePath;
use App\Domain\Editor\Page\PageRepository;
use App\Domain\Generator\SiteGenerator;
use Illuminate\Http\UploadedFile;

class UploadAsset
{
    public function __construct(
        private AssetRepository $assetRepo,
        private PageRepository $pageRepository,
        private SiteGenerator $siteGenerator,
    ) {}

    /**
     * @param  UploadedFile[]  $files
     * @return array<int, array{filename: string, url: string}>
     */
    public function __invoke(string $pagePath, array $files): array
    {
        $page = $this->pageRepository->findByPath($pagePath);

        return array_values(array_map(function (UploadedFile $file) use ($pagePath, $page) {
            $filename = $file->getClientOriginalName();

            $this->assetRepo->save(new NewAsset(
                pagePath: new PagePath($pagePath),
                name: $filename,
                file: $file,
            ));

            if ($page?->isPublished()) {
                $this->siteGenerator->copyPageFile($pagePath, $filename);
            }

            return [
                'filename' => $filename,
                'url' => route('assets.show', [$pagePath, $filename]),
            ];
        }, $files));
    }
}
