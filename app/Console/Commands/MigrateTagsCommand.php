<?php

namespace App\Console\Commands;

use App\Domain\Generator\SiteGenerator;
use App\Domain\TagMigration\CopyMissingViews;
use App\Domain\TagMigration\MigrateTags;
use Illuminate\Console\Command;

class MigrateTagsCommand extends Command
{
    protected $signature = 'pluma:migrate-tags';

    protected $description = 'Bring the tags of a site created by an older version of Pluma to the current format';

    public function handle(MigrateTags $migrateTags, CopyMissingViews $copyMissingViews, SiteGenerator $generator): int
    {
        $result = $migrateTags->__invoke();
        $copiedViews = $copyMissingViews->__invoke();

        if ($result['tag_pages'] === 0 && $result['pages'] === 0 && $copiedViews === []) {
            $this->info('The tags are already in the current format. Nothing to migrate.');

            return self::SUCCESS;
        }

        $this->info("Converted {$result['tag_pages']} tag page(s), migrated {$result['pages']} page(s) and created {$result['tags']} tag(s).");

        foreach ($copiedViews as $view) {
            $this->info("Copied the missing view {$view} from the template.");
        }

        $generator->generateAll();

        $this->info('Site regenerated.');

        return self::SUCCESS;
    }
}
