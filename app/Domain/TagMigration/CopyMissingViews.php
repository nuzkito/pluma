<?php

namespace App\Domain\TagMigration;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class CopyMissingViews
{
    private const string VIEWS_DIRECTORY = 'views';

    private Filesystem $site;

    private Filesystem $template;

    public function __construct()
    {
        $this->site = Storage::disk('current');
        $this->template = Storage::disk('template');
    }

    /**
     * @return array<int, string>
     */
    public function __invoke(): array
    {
        $copied = [];

        foreach ($this->template->files(self::VIEWS_DIRECTORY) as $view) {
            if ($this->site->exists($view)) {
                continue;
            }

            $this->site->put($view, $this->template->get($view));
            $copied[] = $view;
        }

        return $copied;
    }
}
