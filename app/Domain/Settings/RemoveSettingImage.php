<?php

namespace App\Domain\Settings;

use App\Domain\Generator\SiteGenerator;

class RemoveSettingImage
{
    public function __construct(
        private SiteAssetRepository $assets,
        private SettingsRepository $settings,
        private SiteGenerator $siteGenerator,
    ) {}

    /**
     * Clear the image of the given setting and delete its file.
     *
     * @return bool Whether the key is an image setting and its image was cleared.
     */
    public function __invoke(string $key): bool
    {
        if (! $this->isImageSetting($key)) {
            return false;
        }

        $image = $this->currentImage($key);

        if ($image !== '') {
            $this->assets->delete($image);
            $this->siteGenerator->removeSiteFile($image);
        }

        $this->settings->put($key, '');

        $this->siteGenerator->regenerateIndex();

        return true;
    }

    private function isImageSetting(string $key): bool
    {
        return SettingsSchema::find($key)?->type === SettingType::Image;
    }

    private function currentImage(string $key): string
    {
        return (string) config('pluma.'.$key);
    }
}
