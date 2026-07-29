<?php

namespace App\Domain\Settings;

use App\Domain\Generator\SiteGenerator;
use Illuminate\Http\UploadedFile;

class UploadSettingImage
{
    public function __construct(
        private SiteAssetRepository $assets,
        private SettingsRepository $settings,
        private SiteGenerator $siteGenerator,
    ) {}

    /**
     * Store the uploaded file as the image of the given setting, replacing the previous one.
     *
     * @return string|null The stored filename, or null when the key is not an image setting.
     */
    public function __invoke(string $key, UploadedFile $file): ?string
    {
        if (! $this->isImageSetting($key)) {
            return null;
        }

        $previousImage = $this->currentImage($key);
        $image = $this->assets->save($file);

        if ($previousImage !== '' && $previousImage !== $image) {
            $this->assets->delete($previousImage);
            $this->siteGenerator->removeSiteFile($previousImage);
        }

        $this->settings->put($key, $image);

        $this->siteGenerator->copySiteFile($image);
        $this->siteGenerator->regenerateIndex();

        return $image;
    }

    private function isImageSetting(string $key): bool
    {
        return SettingsSchema::find($key)?->type === SettingType::Image;
    }

    private function currentImage(string $key): string
    {
        return (string) config("pluma.$key");
    }
}
