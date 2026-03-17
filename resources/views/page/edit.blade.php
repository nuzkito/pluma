@extends('layout')

@section('title', $page->title)

@section('content')
<div>
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
            @if($page->isPublished())
                <span class="badge badge-published">Published</span>
            @else
                <span class="badge badge-draft">Draft</span>
            @endif
            <span class="text-xs text-gray-400" id="save-status"></span>
        </div>
        <div class="flex gap-2">
            <button type="button" class="btn btn-success" id="publish-btn" style="{{ $page->isPublished() ? 'display:none' : '' }}">Publish</button>
            <button type="button" class="btn btn-danger" id="unpublish-btn" style="{{ $page->isDraft() ? 'display:none' : '' }}">Unpublish</button>
            <form id="delete-form" method="POST" action="{{ route('pages.destroy', $page->path) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
        </div>
    </div>

    <div class="mb-4">
        <label for="title" class="block mb-1 font-semibold text-sm">Title</label>
        <input type="text" id="title" value="{{ $page->title }}" placeholder="Page title" class="w-full px-2 py-1.5 border border-gray-300 rounded text-base">
    </div>

    <div class="mb-4">
        <label for="path" class="block mb-1 font-semibold text-sm">Path</label>
        <input type="text" id="path" value="{{ $page->path }}" placeholder="page-url-path" class="w-full px-2 py-1.5 border border-gray-300 rounded text-base">
        <div id="path-error" class="text-red-600 text-xs mt-1"></div>
    </div>

    <div class="mb-4">
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" id="rss" {{ $page->rss ? 'checked' : '' }}>
            Include in RSS feed
        </label>
    </div>

    <div class="mb-4">
        <label for="published_at" class="block mb-1 font-semibold text-sm">Published at</label>
        <input type="datetime-local" id="published_at" value="{{ $page->published_at?->format('Y-m-d\TH:i') }}" class="px-2 py-1.5 border border-gray-300 rounded text-base">
    </div>

    <div class="mb-4">
        <label for="content" class="block mb-1 font-semibold text-sm">Content</label>
        <textarea id="content">{{ $page->content }}</textarea>
    </div>

    <div class="mb-4">
        <label class="block mb-1 font-semibold text-sm">Attachments</label>
        <input type="file" id="attachment-input" multiple>
        <div id="attachments-list" class="mt-2"></div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let pagePath = @json($page->path);
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const saveStatus = document.getElementById('save-status');
    const pathError = document.getElementById('path-error');
    const deleteForm = document.getElementById('delete-form');
    let pathManuallyEdited = @json($page->path !== \Illuminate\Support\Str::slug($page->title));
    let saveTimeout = null;
    var publishBtn = document.getElementById('publish-btn');
    var unpublishBtn = document.getElementById('unpublish-btn');
    var statusBadge = document.querySelector('.badge');

    const easymde = new EasyMDE({
        element: document.getElementById('content'),
        spellChecker: false,
        status: false,
        previewRender: function (plainText, preview) {
            var html = easymde.markdown(plainText);
            var baseUrl = '/pages/' + pagePath + '/attachments/';

            var container = document.createElement('div');
            container.innerHTML = html;

            container.querySelectorAll('img, video, audio, source').forEach(function (el) {
                var src = el.getAttribute('src');
                if (src && !src.startsWith('/') && !src.startsWith('http://') && !src.startsWith('https://')) {
                    el.setAttribute('src', baseUrl + encodeURIComponent(src));
                }
            });

            container.querySelectorAll('a').forEach(function (el) {
                var href = el.getAttribute('href');
                if (href && !href.startsWith('/') && !href.startsWith('http://') && !href.startsWith('https://') && !href.startsWith('#') && !href.startsWith('mailto:')) {
                    el.setAttribute('href', baseUrl + encodeURIComponent(href));
                }
            });

            return container.innerHTML;
        },
    });

    function autoSave(data) {
        clearTimeout(saveTimeout);
        saveStatus.textContent = 'Saving...';

        saveTimeout = setTimeout(function () {
            fetch('/pages/' + pagePath, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(data),
            })
            .then(function (r) { return r.json(); })
            .then(function (response) {
                if (response.success) {
                    saveStatus.textContent = 'Saved';
                    pathError.textContent = '';
                    if (response.page && response.page.path !== pagePath) {
                        pagePath = response.page.path;
                        history.replaceState(null, '', '/pages/' + pagePath + '/edit');
                        deleteForm.action = '/pages/' + pagePath;
                    }
                    if (response.page) {
                        if (response.page.published_at) {
                            publishBtn.style.display = 'none';
                            unpublishBtn.style.display = '';
                            statusBadge.textContent = 'Published';
                            statusBadge.className = 'badge badge-published';
                        } else {
                            unpublishBtn.style.display = 'none';
                            publishBtn.style.display = '';
                            statusBadge.textContent = 'Draft';
                            statusBadge.className = 'badge badge-draft';
                        }
                    }
                } else if (response.errors && response.errors.path) {
                    pathError.textContent = response.errors.path[0];
                    saveStatus.textContent = 'Error';
                }
            })
            .catch(function () {
                saveStatus.textContent = 'Error saving';
            });
        }, 500);
    }

    document.getElementById('title').addEventListener('input', function () {
        var data = { title: this.value };

        if (!pathManuallyEdited) {
            var generatedPath = this.value.toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-|-$/g, '');
            document.getElementById('path').value = generatedPath;
            data.path = generatedPath;
        }

        autoSave(data);
    });

    document.getElementById('path').addEventListener('input', function () {
        pathManuallyEdited = true;
        autoSave({ path: this.value });
    });

    document.getElementById('rss').addEventListener('change', function () {
        autoSave({ rss: this.checked });
    });

    var publishedAtInput = document.getElementById('published_at');
    if (publishedAtInput) {
        publishedAtInput.addEventListener('change', function () {
            autoSave({ published_at: this.value || null });
        });
    }

    easymde.codemirror.on('change', function () {
        autoSave({ content: easymde.value() });
    });

    publishBtn.addEventListener('click', function () {
        fetch('/pages/' + pagePath + '/publish', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
        })
        .then(function (r) { return r.json(); })
        .then(function (response) {
            if (response.success) {
                publishBtn.style.display = 'none';
                unpublishBtn.style.display = '';
                statusBadge.textContent = 'Published';
                statusBadge.className = 'badge badge-published';
                if (response.page && response.page.published_at) {
                    var date = new Date(response.page.published_at);
                    var pad = function (n) { return n < 10 ? '0' + n : n; };
                    publishedAtInput.value = date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate()) + 'T' + pad(date.getHours()) + ':' + pad(date.getMinutes());
                }
            }
        });
    });

    unpublishBtn.addEventListener('click', function () {
        fetch('/pages/' + pagePath + '/unpublish', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
        })
        .then(function (r) { return r.json(); })
        .then(function (response) {
            if (response.success) {
                unpublishBtn.style.display = 'none';
                publishBtn.style.display = '';
                statusBadge.textContent = 'Draft';
                statusBadge.className = 'badge badge-draft';
                publishedAtInput.value = '';
            }
        });
    });

    var existingAttachments = @json($attachments);
    existingAttachments.forEach(function (attachment) {
        addAttachmentToList(attachment.filename, attachment.url);
    });

    document.getElementById('attachment-input').addEventListener('change', function () {
        var files = this.files;

        for (var i = 0; i < files.length; i++) {
            var formData = new FormData();
            formData.append('file', files[i]);

            fetch('/pages/' + pagePath + '/attachments', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: formData,
            })
            .then(function (r) { return r.json(); })
            .then(function (response) {
                if (response.success) {
                    addAttachmentToList(response.filename, response.url);
                }
            });
        }

        this.value = '';
    });

    function addAttachmentToList(filename, url) {
        var list = document.getElementById('attachments-list');
        var item = document.createElement('div');
        item.className = 'flex items-center gap-2 mb-1';

        var span = document.createElement('span');
        span.textContent = filename;
        item.appendChild(span);

        var insertBtn = document.createElement('button');
        insertBtn.type = 'button';
        insertBtn.className = 'btn';
        insertBtn.style.cssText = 'padding: 0.2rem 0.5rem; font-size: 0.75rem;';
        insertBtn.textContent = 'Insert';
        insertBtn.addEventListener('click', function () {
            insertAttachment(filename);
        });
        item.appendChild(insertBtn);

        var deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.className = 'btn btn-danger';
        deleteBtn.style.cssText = 'padding: 0.2rem 0.5rem; font-size: 0.75rem;';
        deleteBtn.textContent = 'Delete';
        deleteBtn.addEventListener('click', function () {
            if (!confirm('Delete "' + filename + '"?')) {
                return;
            }

            fetch('/pages/' + pagePath + '/attachments/' + encodeURIComponent(filename), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            })
            .then(function (r) { return r.json(); })
            .then(function (response) {
                if (response.success) {
                    item.remove();
                }
            });
        });
        item.appendChild(deleteBtn);

        list.appendChild(item);
    }

    function insertAttachment(filename) {
        var extension = filename.split('.').pop().toLowerCase();
        var markdown;

        if (['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'].indexOf(extension) !== -1) {
            markdown = '![' + filename + '](' + filename + ')';
        } else if (['mp4', 'webm', 'ogg'].indexOf(extension) !== -1) {
            markdown = '<video src="' + filename + '" controls></video>';
        } else if (['mp3', 'wav', 'flac'].indexOf(extension) !== -1) {
            markdown = '<audio src="' + filename + '" controls></audio>';
        } else {
            markdown = '[' + filename + '](' + filename + ')';
        }

        var cm = easymde.codemirror;
        var cursor = cm.getCursor();
        cm.replaceRange(markdown, cursor);
    }
});
</script>
@endpush
@endsection
