<?php

use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Page\PagePath;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

describe('title and path', function () {
    test('edit page shows current title in title field', function () {
        $repository = initializeSite();

        $page = ContentPage::draft('My Original Title', 'my-original-title');
        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->assertSet('title', 'My Original Title');
    });

    test('editing title updates page title in repository', function () {
        $repository = initializeSite();

        $page = ContentPage::draft('Original Title', 'original-title');
        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->set('title', 'Updated Title From Edit')
            ->assertSet('title', 'Updated Title From Edit');

        $updated = $repository->findByPath('updated-title-from-edit');

        expect($updated)->not->toBeNull()
            ->and($updated->title)->toBe('Updated Title From Edit')
            ->and((string) $updated->path)->toBe('updated-title-from-edit');
    });

    test('changing title auto-updates path when slug-based', function () {
        Carbon::setTestNow(Carbon::parse('2025-06-01 12:00:00'));

        $repository = initializeSite();

        $page = ContentPage::draft('My Original Title', 'my-original-title');
        expect((string) $page->path)->toBe('my-original-title');

        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->set('title', 'New Auto Slug Title')
            ->assertSet('title', 'New Auto Slug Title');

        $updated = $repository->findByPath('new-auto-slug-title');

        expect($updated)->not->toBeNull()
            ->and((string) $updated->path)->toBe('new-auto-slug-title')
            ->and($updated->title)->toBe('New Auto Slug Title');

        Carbon::setTestNow(null);
    });

    test('changing title does not update path when custom path exists', function () {
        Carbon::setTestNow(Carbon::parse('2025-06-01 12:00:00'));

        $repository = initializeSite();

        $page = new ContentPage(
            title: 'Original Title',
            path: new PagePath('custom-path'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
        );

        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->set('title', 'New Title With Custom Path')
            ->assertSet('title', 'New Title With Custom Path');

        $updated = $repository->findByPath('custom-path');

        expect($updated)->not->toBeNull()
            ->and((string) $updated->path)->toBe('custom-path')
            ->and($updated->title)->toBe('New Title With Custom Path');

        Carbon::setTestNow(null);
    });

    test('changing title to same slug does not affect path', function () {
        $repository = initializeSite();

        $page = ContentPage::draft('Same Slug Title', 'same-slug-title');
        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->set('title', 'Same Slug Title')
            ->assertSet('title', 'Same Slug Title');

        $updated = $repository->findByPath('same-slug-title');

        expect($updated)->not->toBeNull()
            ->and((string) $updated->path)->toBe('same-slug-title')
            ->and($updated->title)->toBe('Same Slug Title');
    });

    test('editing title preserves other page properties', function () {
        Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

        $repository = initializeSite();

        $page = new ContentPage(
            title: 'Original Title',
            path: new PagePath('preserve-test'),
            content: new Markdown('# Original Content'),
            created_at: Carbon::now(),
            published_at: Carbon::parse('2025-06-15 14:30:00'),
            rss: true,
            tags: ['php', 'testing'],
        );

        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->set('title', 'New Title Preserves Other Fields')
            ->assertSet('title', 'New Title Preserves Other Fields');

        $updated = $repository->findByPath('preserve-test');

        expect($updated->title)->toBe('New Title Preserves Other Fields')
            ->and((string) $updated->path)->toBe('preserve-test')
            ->and((string) $updated->content)->toBe('# Original Content')
            ->and($updated->rss)->toBeTrue()
            ->and($updated->tags)->toBe(['php', 'testing'])
            ->and($updated->published_at?->format('Y-m-d H:i'))->toBe('2025-06-15 14:30');

        Carbon::setTestNow(null);
    });

    test('title change dispatches url-changed event when path changes', function () {
        $repository = initializeSite();

        $page = ContentPage::draft('Title That Changes Path', 'title-that-changes-path');
        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->set('title', 'Completely Different Title Now')
            ->assertSet('title', 'Completely Different Title Now')
            ->assertDispatched('url-changed');

        expect($repository->findByPath('completely-different-title-now'))->not->toBeNull();
    });

    test('slug-based page title change that would conflict with another slug is rejected', function () {
        Carbon::setTestNow(Carbon::parse('2025-06-01 12:00:00'));

        $repository = initializeSite();

        $pageA = ContentPage::draft('Existing Title', 'existing-title');
        $repository->save($pageA);

        $pageB = new ContentPage(
            title: 'Different Title',
            path: new PagePath('different-title'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
        );
        $repository->save($pageB);

        Livewire::test('pages::page.edit', ['path' => 'different-title'])
            ->set('title', 'Existing Title')
            ->assertHasErrors('title');

        expect($repository->findByPath('existing-title'))->not->toBeNull();
        $updated = $repository->findByPath('different-title');
        expect((string) $updated->path)->toBe('different-title');

        Carbon::setTestNow(null);
    });
});

describe('tags', function () {
    test('adding first tag to draft page saves it correctly', function () {
        $repository = initializeSite();

        $page = ContentPage::draft('Draft With Tags', 'draft-with-tags');
        $repository->save($page);

        expect($page->tags)->toBe([]);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->call('addTag', 'first-tag')
            ->assertOk();

        $updated = $repository->findByPath('draft-with-tags');

        expect($updated->tags)->toBe(['first-tag']);
    });

    test('adding a tag creates its tag page file', function () {
        $repository = initializeSite();
        config()->set('pluma.create_tag_pages', true);

        $page = ContentPage::draft('Page Creating Tag', 'page-creating-tag');
        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->call('addTag', 'Cosas varias')
            ->assertOk();

        expect(Storage::disk('current')->exists('pages/tags/cosas-varias.tag.md'))->toBeTrue();
    });

    test('adding a tag does not create its tag page file when the option is disabled', function () {
        $repository = initializeSite();
        config()->set('pluma.create_tag_pages', false);

        $page = ContentPage::draft('Page Without Tag Page', 'page-without-tag-page');
        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->call('addTag', 'Cosas varias')
            ->assertOk();

        expect($repository->findByPath('page-without-tag-page')->tags)->toBe(['Cosas varias'])
            ->and(Storage::disk('current')->exists('pages/tags/cosas-varias.tag.md'))->toBeFalse();
    });

    test('adding a tag whose page already exists does not fail', function () {
        $repository = initializeSite();
        config()->set('pluma.create_tag_pages', true);

        $page = ContentPage::draft('Page Reusing Tag', 'page-reusing-tag');
        $repository->save($page);

        Storage::disk('current')->put('pages/tags/laravel.tag.md', "---\ntitle: Laravel\npath: laravel\ncreated_at: '2025-01-01T10:00:00+00:00'\n---\n\nExisting description");

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->call('addTag', 'Laravel')
            ->assertOk();

        expect(Storage::disk('current')->get('pages/tags/laravel.tag.md'))->toContain('Existing description');
    });

    test('adding second tag to page with existing tag results in two tags', function () {
        $repository = initializeSite();

        $page = new ContentPage(
            title: 'Page With One Tag',
            path: new PagePath('one-tag-page'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
            tags: ['existing-tag'],
        );
        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->call('addTag', 'new-tag')
            ->assertOk();

        $updated = $repository->findByPath('one-tag-page');

        expect($updated->tags)->toBe(['existing-tag', 'new-tag']);
    });

    test('adding two tags sequentially saves both correctly', function () {
        $repository = initializeSite();

        $page = ContentPage::draft('Multi Tag Page', 'multi-tag-page');
        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->call('addTag', 'tag-one')
            ->assertOk()
            ->call('addTag', 'tag-two')
            ->assertOk();

        $updated = $repository->findByPath('multi-tag-page');

        expect($updated->tags)->toBe(['tag-one', 'tag-two']);
    });

    test('removing a tag removes it from the page', function () {
        $repository = initializeSite();

        $page = new ContentPage(
            title: 'Page To Remove Tag From',
            path: new PagePath('remove-tag-page'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
            tags: ['keep-this', 'remove-this'],
        );
        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->call('removeTag', 1)
            ->assertOk();

        $updated = $repository->findByPath('remove-tag-page');

        expect($updated->tags)->toBe(['keep-this'])
            ->and($updated->tags)->not->toContain('remove-this');
    });

    test('removing all tags results in empty array', function () {
        $repository = initializeSite();

        $page = new ContentPage(
            title: 'Clear All Tags',
            path: new PagePath('clear-tags'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
            tags: ['tag1', 'tag2'],
        );
        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->call('removeTag', 0)
            ->assertOk()
            ->call('removeTag', 0)
            ->assertOk();

        $updated = $repository->findByPath('clear-tags');

        expect($updated->tags)->toBe([]);
    });

    test('duplicate tags are prevented by addTag', function () {
        $repository = initializeSite();

        $page = ContentPage::draft('Duplicate Tags', 'duplicate-tags');
        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->call('addTag', 'php')
            ->assertOk()
            ->call('addTag', 'laravel')
            ->assertOk();

        expect($repository->findByPath('duplicate-tags')->tags)->toBe(['php', 'laravel']);
    });
});

describe('tag datalist autocomplete', function () {
    test('all tags from all pages are available in the component for autocompletion', function () {
        $repository = initializeSite();

        $page1 = new ContentPage(
            title: 'Page One',
            path: new PagePath('page-one'),
            content: new Markdown('# Content 1'),
            created_at: Carbon::now(),
        );
        $repository->save($page1);

        $page2 = new ContentPage(
            title: 'Page Two With Tags',
            path: new PagePath('page-two-with-tags'),
            content: new Markdown('# Content 2'),
            created_at: Carbon::now(),
            tags: ['livewire', 'php'],
        );
        $repository->save($page2);

        Livewire::test('pages::page.edit', ['path' => (string) $page1->path])
            ->assertSeeHtml('<option value="livewire">livewire</option>')
            ->assertSeeHtml('<option value="php">php</option>');
    });

    test('tags from the edited page are excluded from the datalist options', function () {
        Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

        $repository = initializeSite();

        $page1 = new ContentPage(
            title: 'Page One With Tags',
            path: new PagePath('page-one-with-tags'),
            content: new Markdown('# Content 1'),
            created_at: Carbon::now(),
            tags: ['php', 'laravel'],
        );
        $repository->save($page1);

        $page2 = new ContentPage(
            title: 'Page Two With Tags',
            path: new PagePath('page-two-with-tags'),
            content: new Markdown('# Content 2'),
            created_at: Carbon::now(),
            tags: ['livewire', 'php'],
        );
        $repository->save($page2);

        Livewire::test('pages::page.edit', ['path' => (string) $page1->path])
            ->assertSeeHtml('<option value="livewire">livewire</option>')
            ->assertDontSeeHtml('<option value="php"')
            ->assertDontSeeHtml('<option value="laravel"');

        Carbon::setTestNow(null);
    });

    test('editing a page with no tags shows all other pages tags in datalist', function () {
        Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

        $repository = initializeSite();

        $page1 = new ContentPage(
            title: 'Page Without Tags',
            path: new PagePath('no-tags-page'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
            tags: [],
        );
        $repository->save($page1);

        $page2 = new ContentPage(
            title: 'Page With Tags',
            path: new PagePath('with-tags-page'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
            tags: ['php', 'laravel'],
        );
        $repository->save($page2);

        Livewire::test('pages::page.edit', ['path' => (string) $page1->path])
            ->assertSeeHtml('<option value="php">php</option>')
            ->assertSeeHtml('<option value="laravel">laravel</option>');

        Carbon::setTestNow(null);
    });

    test('tag datalist options are sorted alphabetically by name', function () {
        $repository = initializeSite();

        $page1 = new ContentPage(
            title: 'Page One',
            path: new PagePath('page-one'),
            content: new Markdown('# Content 1'),
            created_at: Carbon::now(),
            tags: ['zebra', 'alpha'],
        );
        $repository->save($page1);

        $page2 = new ContentPage(
            title: 'Page Two',
            path: new PagePath('page-two'),
            content: new Markdown('# Content 2'),
            created_at: Carbon::now(),
            tags: ['beta', 'gamma'],
        );
        $repository->save($page2);

        Livewire::test('pages::page.edit', ['path' => (string) $page1->path])
            ->assertSeeHtmlInOrder(['alpha', 'beta', 'gamma', 'zebra']);
    });
});

describe('rss checkbox', function () {
    test('checking unchecked rss checkbox sets page.rss to true', function () {
        $repository = initializeSite();

        $page = new ContentPage(
            title: 'RSS Disabled Page',
            path: new PagePath('rss-disabled'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
            rss: false,
        );
        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->set('rss', true)
            ->assertSet('rss', true);

        $updated = $repository->findByPath('rss-disabled');

        expect($updated->rss)->toBeTrue();
    });

    test('unchecking checked rss checkbox sets page.rss to false', function () {
        Carbon::setTestNow(Carbon::parse('2025-06-01 12:00:00'));

        $repository = initializeSite();

        $page = new ContentPage(
            title: 'RSS Enabled Page',
            path: new PagePath('rss-enabled'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
            published_at: Carbon::now(),
            rss: true,
        );
        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->set('rss', false)
            ->assertSet('rss', false);

        $updated = $repository->findByPath('rss-enabled');

        expect($updated->rss)->toBeFalse();

        Carbon::setTestNow(null);
    });

    test('shows the rss checkbox when rss is enabled', function () {
        $repository = initializeSite();
        config(['pluma.enable_rss' => true]);

        $page = new ContentPage(
            title: 'Page',
            path: new PagePath('a-page'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
        );
        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->assertSee('Include in RSS feed');
    });

    test('hides the rss checkbox when rss is disabled', function () {
        $repository = initializeSite();
        config(['pluma.enable_rss' => false]);

        $page = new ContentPage(
            title: 'Page',
            path: new PagePath('a-page'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
        );
        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->assertDontSee('Include in RSS feed');
    });
});

describe('published at', function () {
    test('setting published_at date publishes an unpublished page', function () {
        Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

        $repository = initializeSite();

        $page = ContentPage::draft('Draft To Publish', 'draft-to-publish');
        expect($page->isDraft())->toBeTrue();
        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->set('published_at', '2025-06-15T14:30')
            ->assertSet('published_at', '2025-06-15T14:30');

        Carbon::setTestNow(null);

        $updated = $repository->findByPath('draft-to-publish');

        expect($updated)->not->toBeNull()
            ->and($updated->isPublished())->toBeTrue()
            ->and($updated->published_at?->format('Y-m-d H:i'))->toBe('2025-06-15 14:30');
    });

    test('clearing published_at unpublishes a page', function () {
        Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

        $repository = initializeSite();

        $page = new ContentPage(
            title: 'Published To Unpublish',
            path: new PagePath('published-to-unpublish'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
            published_at: Carbon::parse('2025-06-15 14:30:00'),
        );
        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->set('published_at', null)
            ->assertSet('published_at', null);

        Carbon::setTestNow(null);

        $updated = $repository->findByPath('published-to-unpublish');

        expect($updated)->not->toBeNull()
            ->and($updated->isDraft())->toBeTrue()
            ->and($updated->published_at)->toBeNull();
    });

    test('changing published_at on a published page updates to the new date', function () {
        Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

        $repository = initializeSite();

        $page = new ContentPage(
            title: 'Update Published Date',
            path: new PagePath('update-published-date'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
            published_at: Carbon::parse('2025-01-01 10:00:00'),
        );
        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->set('published_at', '2025-08-20T16:45')
            ->assertSet('published_at', '2025-08-20T16:45');

        Carbon::setTestNow(null);

        $updated = $repository->findByPath('update-published-date');

        expect($updated)->not->toBeNull()
            ->and($updated->isPublished())->toBeTrue()
            ->and($updated->published_at?->format('Y-m-d H:i'))->toBe('2025-08-20 16:45');
    });
});

describe('content', function () {
    test('edit page shows current content in textarea', function () {
        $repository = initializeSite();

        $page = new ContentPage(
            title: 'Page With Content',
            path: new PagePath('with-content'),
            content: new Markdown('# Hello World'),
            created_at: Carbon::now(),
        );
        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->assertSet('content', '# Hello World');
    });

    test('updating content saves it to the page', function () {
        Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

        $repository = initializeSite();

        $page = new ContentPage(
            title: 'Content Update Test',
            path: new PagePath('content-update'),
            content: new Markdown('# Old Content'),
            created_at: Carbon::now(),
        );
        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->set('content', '# New Content')
            ->assertSet('content', '# New Content');

        Carbon::setTestNow(null);

        $updated = $repository->findByPath('content-update');

        expect($updated)->not->toBeNull()
            ->and((string) $updated->content)->toBe('# New Content');
    });

    test('setting empty content clears the page', function () {
        Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

        $repository = initializeSite();

        $page = new ContentPage(
            title: 'Clear Content',
            path: new PagePath('clear-content'),
            content: new Markdown('# Has Content'),
            created_at: Carbon::now(),
        );
        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->set('content', '')
            ->assertSet('content', '');

        Carbon::setTestNow(null);

        $updated = $repository->findByPath('clear-content');

        expect($updated)->not->toBeNull()
            ->and((string) $updated->content)->toBe('');
    });
});

describe('attachments', function () {
    test('edit page shows existing attachments in list', function () {
        Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

        $repository = initializeSite();

        $page = new ContentPage(
            title: 'Page With Attachments',
            path: new PagePath('with-attachments'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
        );
        $repository->save($page);

        Storage::disk('current')->put("assets/{$page->path}/file1.txt", 'hello');
        Storage::disk('current')->put("assets/{$page->path}/image.png", 'binary');

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->tap(function ($component) {
                expect($component->attachments)->toHaveCount(2);
                expect(collect($component->attachments)->pluck('filename')->all())->toEqualCanonicalizing(['file1.txt', 'image.png']);

                foreach ($component->attachments as $attachment) {
                    expect($attachment)->toHaveKeys(['filename', 'url']);
                }
            });

        Carbon::setTestNow(null);
    });

    test('uploading a file adds it to attachments list', function () {
        Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

        $repository = initializeSite();

        $page = new ContentPage(
            title: 'Upload Test Page',
            path: new PagePath('upload-test'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
        );
        $repository->save($page);

        $file = UploadedFile::fake()->createWithContent('test.txt', 'hello world');

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->set('newAttachments', [$file])
            ->assertSet('newAttachments', []);

        expect(Storage::disk('current')->exists("assets/{$page->path}/test.txt"))->toBeTrue();

        Carbon::setTestNow(null);
    });

    test('uploading multiple files adds all to attachments list', function () {
        Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

        $repository = initializeSite();

        $page = new ContentPage(
            title: 'Multi Upload Test',
            path: new PagePath('multi-upload'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
        );
        $repository->save($page);

        $file1 = UploadedFile::fake()->createWithContent('doc1.pdf', 'content1');
        $file2 = UploadedFile::fake()->createWithContent('doc2.txt', 'content2');

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->set('newAttachments', [$file1, $file2])
            ->assertSet('newAttachments', []);

        expect(Storage::disk('current')->exists("assets/{$page->path}/doc1.pdf"))->toBeTrue();
        expect(Storage::disk('current')->exists("assets/{$page->path}/doc2.txt"))->toBeTrue();

        Carbon::setTestNow(null);
    });

    test('deleting an attachment removes it from storage and list', function () {
        Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

        $repository = initializeSite();

        $page = new ContentPage(
            title: 'Delete Attachment Test',
            path: new PagePath('delete-attachment'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
        );
        $repository->save($page);

        Storage::disk('current')->put("assets/{$page->path}/to-delete.txt", 'hello');
        Storage::disk('current')->put("assets/{$page->path}/keep.txt", 'world');

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->call('deleteAttachment', 'to-delete.txt')
            ->tap(function ($component) {
                expect(collect($component->attachments)->pluck('filename')->all())->toEqualCanonicalizing(['keep.txt']);
            });

        expect(Storage::disk('current')->exists("assets/{$page->path}/to-delete.txt"))->toBeFalse();
        expect(Storage::disk('current')->exists("assets/{$page->path}/keep.txt"))->toBeTrue();

        Carbon::setTestNow(null);
    });

    test('deleting all attachments results in empty list', function () {
        Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

        $repository = initializeSite();

        $page = new ContentPage(
            title: 'Clear Attachments Test',
            path: new PagePath('clear-attachments'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
        );
        $repository->save($page);

        Storage::disk('current')->put("assets/{$page->path}/file1.txt", 'hello');
        Storage::disk('current')->put("assets/{$page->path}/file2.png", 'binary');

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->call('deleteAttachment', 'file1.txt')
            ->call('deleteAttachment', 'file2.png');

        expect(Storage::disk('current')->files("assets/{$page->path}"))->toBe([]);

        Carbon::setTestNow(null);
    });

    test('deleting all attachments removes empty asset directory', function () {
        Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

        $repository = initializeSite();

        $page = new ContentPage(
            title: 'Empty Dir Cleanup Test',
            path: new PagePath('empty-dir-cleanup'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
        );
        $repository->save($page);

        Storage::disk('current')->put("assets/{$page->path}/file1.txt", 'hello');
        Storage::disk('current')->put("assets/{$page->path}/file2.png", 'binary');

        expect(Storage::disk('current')->exists("assets/{$page->path}"))->toBeTrue();

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->call('deleteAttachment', 'file1.txt')
            ->call('deleteAttachment', 'file2.png');

        expect(Storage::disk('current')->exists("assets/{$page->path}"))->toBeFalse();

        Carbon::setTestNow(null);
    });

    test('deleting attachment from published page removes it from site disk', function () {
        Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

        $repository = initializeSite();

        $page = new ContentPage(
            title: 'Published Delete Site Disk Test',
            path: new PagePath('published-delete-site-disk'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
            published_at: Carbon::parse('2025-06-15 14:30:00'),
        );
        $repository->save($page);

        Storage::disk('current')->put("assets/{$page->path}/to-delete.txt", 'hello');
        Storage::disk('current')->put("site/{$page->path}/to-delete.txt", 'hello');

        expect(Storage::disk('current')->exists("site/{$page->path}/to-delete.txt"))->toBeTrue();

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->call('deleteAttachment', 'to-delete.txt');

        expect(Storage::disk('current')->exists("assets/{$page->path}/to-delete.txt"))->toBeFalse();
        expect(Storage::disk('current')->exists("site/{$page->path}/to-delete.txt"))->toBeFalse();

        Carbon::setTestNow(null);
    });

    test('deleting attachment from draft page does not touch site disk', function () {
        Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

        $repository = initializeSite();

        $page = new ContentPage(
            title: 'Draft Delete No Site Disk Test',
            path: new PagePath('draft-delete-no-site-disk'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
        );
        $repository->save($page);

        Storage::disk('current')->put("assets/{$page->path}/to-delete.txt", 'hello');
        Storage::disk('current')->put("site/{$page->path}/to-delete.txt", 'should remain');

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->call('deleteAttachment', 'to-delete.txt');

        expect(Storage::disk('current')->exists("assets/{$page->path}/to-delete.txt"))->toBeFalse();
        expect(Storage::disk('current')->exists("site/{$page->path}/to-delete.txt"))->toBeTrue();

        Carbon::setTestNow(null);
    });
});

describe('cover image', function () {
    test('cover image is null by default', function () {
        $repository = initializeSite();

        $page = ContentPage::draft('No Cover Page', 'no-cover-page');
        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->assertSet('cover_image', null);
    });

    test('edit page loads the existing cover image', function () {
        $repository = initializeSite();

        $page = new ContentPage(
            title: 'Page With Cover',
            path: new PagePath('page-with-cover'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
            cover_image: 'header.png',
        );
        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->assertSet('cover_image', 'header.png');
    });

    test('setting an image as cover saves it on the page', function () {
        $repository = initializeSite();

        $page = ContentPage::draft('Set Cover Page', 'set-cover-page');
        $repository->save($page);

        Storage::disk('current')->put("assets/{$page->path}/header.png", 'binary');

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->call('setCoverImage', 'header.png')
            ->assertSet('cover_image', 'header.png');

        expect($repository->findByPath('set-cover-page')->cover_image)->toBe('header.png');
    });

    test('shows the add as cover image button for images that are not the cover', function () {
        $repository = initializeSite();

        $page = ContentPage::draft('Cover Button Page', 'cover-button-page');
        $repository->save($page);

        Storage::disk('current')->put("assets/{$page->path}/photo.png", 'binary');

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->assertSee('Add as cover image');
    });

    test('does not show the add as cover image button for non-image attachments', function () {
        $repository = initializeSite();

        $page = ContentPage::draft('No Image Cover Page', 'no-image-cover-page');
        $repository->save($page);

        Storage::disk('current')->put("assets/{$page->path}/document.txt", 'content');

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->assertDontSee('Add as cover image');
    });

    test('hides the add as cover image button for the image already set as cover', function () {
        $repository = initializeSite();

        $page = new ContentPage(
            title: 'Already Cover Page',
            path: new PagePath('already-cover-page'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
            cover_image: 'header.png',
        );
        $repository->save($page);

        Storage::disk('current')->put("assets/{$page->path}/header.png", 'binary');

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->assertDontSee('Add as cover image');
    });

    test('deleting the cover image attachment clears the cover image', function () {
        $repository = initializeSite();

        $page = new ContentPage(
            title: 'Delete Cover Page',
            path: new PagePath('delete-cover-page'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
            cover_image: 'header.png',
        );
        $repository->save($page);

        Storage::disk('current')->put("assets/{$page->path}/header.png", 'binary');

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->call('deleteAttachment', 'header.png')
            ->assertSet('cover_image', null);

        expect($repository->findByPath('delete-cover-page')->cover_image)->toBeNull();
    });

    test('deleting a non-cover attachment keeps the cover image', function () {
        $repository = initializeSite();

        $page = new ContentPage(
            title: 'Keep Cover Page',
            path: new PagePath('keep-cover-page'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
            cover_image: 'header.png',
        );
        $repository->save($page);

        Storage::disk('current')->put("assets/{$page->path}/header.png", 'binary');
        Storage::disk('current')->put("assets/{$page->path}/other.txt", 'content');

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->call('deleteAttachment', 'other.txt')
            ->assertSet('cover_image', 'header.png');

        expect($repository->findByPath('keep-cover-page')->cover_image)->toBe('header.png');
    });
});

describe('publish / unpublish', function () {
    test('publishing a draft page sets published_at and marks as published', function () {
        Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

        $repository = initializeSite();

        $page = ContentPage::draft('Draft To Publish Via Button', 'draft-to-publish-via-button');
        expect($page->isDraft())->toBeTrue();
        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->call('publish')
            ->tap(function ($component) {
                expect($component->published_at)->not->toBeNull();
            });

        Carbon::setTestNow(null);

        $updated = $repository->findByPath('draft-to-publish-via-button');

        expect($updated)->not->toBeNull()
            ->and($updated->isPublished())->toBeTrue();
    });

    test('unpublishing a published page clears published_at and marks as draft', function () {
        Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

        $repository = initializeSite();

        $page = new ContentPage(
            title: 'Published To Unpublish Via Button',
            path: new PagePath('published-to-unpublish-via-button'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
            published_at: Carbon::parse('2025-06-15 14:30:00'),
        );
        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->call('unpublish')
            ->assertSet('published_at', null);

        Carbon::setTestNow(null);

        $updated = $repository->findByPath('published-to-unpublish-via-button');

        expect($updated)->not->toBeNull()
            ->and($updated->isDraft())->toBeTrue()
            ->and($updated->published_at)->toBeNull();
    });
});

describe('delete', function () {
    test('deleting a page removes it from the repository', function () {
        Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

        $repository = initializeSite();

        $page = new ContentPage(
            title: 'Page To Delete Via Button',
            path: new PagePath('delete-via-button'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
        );
        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->call('delete');

        Carbon::setTestNow(null);

        expect($repository->findByPath('delete-via-button'))->toBeNull();
    });

    test('deleting a published page removes it and regenerates index', function () {
        Carbon::setTestNow(Carbon::parse('2025-01-01 10:00:00'));

        $repository = initializeSite();

        $page = new ContentPage(
            title: 'Published Delete Test',
            path: new PagePath('published-delete'),
            content: new Markdown('# Content'),
            created_at: Carbon::now(),
            published_at: Carbon::parse('2025-06-15 14:30:00'),
        );
        $repository->save($page);

        Livewire::test('pages::page.edit', ['path' => (string) $page->path])
            ->call('delete');

        Carbon::setTestNow(null);

        expect($repository->findByPath('published-delete'))->toBeNull();
    });
});
