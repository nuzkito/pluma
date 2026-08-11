<?php

use App\Domain\Editor\Page\MoveTagPages;
use App\Domain\Generator\SiteGenerator;
use App\Domain\Settings\RemoveSettingImage;
use App\Domain\Settings\SettingDefinition;
use App\Domain\Settings\SettingsRepository;
use App\Domain\Settings\SettingsSchema;
use App\Domain\Settings\SettingType;
use App\Domain\Settings\UploadSettingImage;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    /**
     * @var array<string, mixed>
     */
    public array $values = [];

    /**
     * The filename stored for each image setting, keyed by setting key.
     *
     * @var array<string, mixed>
     */
    #[Locked]
    public array $images = [];

    /**
     * The pending upload of each image setting, keyed by setting key.
     *
     * @var array<string, mixed>
     */
    public array $newImages = [];

    public function mount(): void
    {
        foreach (SettingsSchema::definitions() as $definition) {
            if ($definition->type === SettingType::Image) {
                data_set($this->images, $definition->key, (string) config("pluma.{$definition->key}"));
                data_set($this->newImages, $definition->key, null);

                continue;
            }

            data_set($this->values, $definition->key, $definition->type->forForm(config("pluma.{$definition->key}")));
        }
    }

    public function updatedNewImages(mixed $value, string $key, UploadSettingImage $uploadSettingImage): void
    {
        $upload = data_get($this->newImages, $key);

        if (! $upload instanceof TemporaryUploadedFile) {
            return;
        }

        $this->validateOnly("newImages.$key");

        $image = $uploadSettingImage->__invoke($key, $upload);

        if ($image === null) {
            return;
        }

        data_set($this->images, $key, $image);
        data_set($this->newImages, $key, null);
    }

    public function removeImage(string $key, RemoveSettingImage $removeSettingImage): void
    {
        if (! $removeSettingImage->__invoke($key)) {
            return;
        }

        data_set($this->images, $key, '');
    }

    public function save(SettingsRepository $settings, SiteGenerator $siteGenerator, MoveTagPages $moveTagPages): void
    {
        $this->validate();

        $values = [];

        foreach (SettingsSchema::definitions() as $definition) {
            data_set($values, $definition->key, $definition->type->fromForm($this->value($definition)));
        }

        $currentTagsPath = (string) config('pluma.tags.pages_path');
        $newTagsPath = (string) data_get($values, 'tags.pages_path');

        $result = $moveTagPages->__invoke($currentTagsPath, $newTagsPath);

        if ($result->isError()) {
            $this->addError('values.tags.pages_path', $result->unwrapError());

            return;
        }

        $settings->save($values);

        if (trim($currentTagsPath, '/') !== trim($newTagsPath, '/')) {
            $siteGenerator->generateAll();
        } else {
            $siteGenerator->copySiteImages();
            $siteGenerator->regenerateIndex();
        }

        Flux::toast('Settings saved.', variant: 'success');
    }

    private function value(SettingDefinition $definition): mixed
    {
        return $definition->type === SettingType::Image
            ? (string) data_get($this->images, $definition->key)
            : data_get($this->values, $definition->key);
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        $rules = [];

        foreach (SettingsSchema::definitions() as $definition) {
            if ($definition->type === SettingType::Image) {
                $rules["newImages.{$definition->key}"] = ['nullable', 'image', 'max:12288'];

                continue;
            }

            if ($definition->rules !== []) {
                $rules["values.{$definition->key}"] = $definition->rules;
            }
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        $attributes = [];

        foreach (SettingsSchema::definitions() as $definition) {
            $property = $definition->type === SettingType::Image ? 'newImages' : 'values';

            $attributes["$property.{$definition->key}"] = strtolower($definition->label);
        }

        return $attributes;
    }

    /**
     * @return array<string, list<SettingDefinition>>
     */
    #[Computed]
    public function groups(): array
    {
        return SettingsSchema::grouped();
    }
};
