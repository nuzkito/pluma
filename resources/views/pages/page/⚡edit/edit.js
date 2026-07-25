const easymde = new EasyMDE({
    element: $wire.$el.querySelector('#content'),
    spellChecker: false,
    status: false,
    sideBySideFullscreen: false,
    toolbar: [
        'bold', 'italic', 'strikethrough', '|',
        'heading-1', 'heading-2', 'heading-3', '|',
        'code', 'quote', 'unordered-list', 'ordered-list', 'check-list', '|',
        'link', 'image', 'table', 'horizontal-rule', '|',
        'side-by-side', 'guide',
    ],
    previewRender: (plainText) => {
        const pagePath = $wire.$el.querySelector('#path').value
        const baseUrl = `/pages/${pagePath}/assets/`

        const container = document.createElement('div')
        container.innerHTML = easymde.markdown(plainText)

        container.querySelectorAll('img, video, audio, source').forEach((el) => {
            const src = el.getAttribute('src')
            if (src && !src.startsWith('/') && !src.startsWith('http://') && !src.startsWith('https://')) {
                el.setAttribute('src', baseUrl + encodeURIComponent(src))
            }
        });

        container.querySelectorAll('a').forEach((el) => {
            const href = el.getAttribute('href')
            if (href && !href.startsWith('/') && !href.startsWith('http://') && !href.startsWith('https://') && !href.startsWith('#') && !href.startsWith('mailto:')) {
                el.setAttribute('href', baseUrl + encodeURIComponent(href))
            }
        });

        return container.innerHTML
    },
});

easymde.codemirror.on('change', () => {
    $wire.set('content', easymde.value())
});

$wire.on('url-changed', (path) => {
    history.pushState({}, '', `/pages/${path}/edit`)
});

const assetMarkdown = (filename) => {
    const extension = filename.split('.').pop().toLowerCase()
    const encoded = encodeURIComponent(filename)

    if (['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'].includes(extension)) {
        return `![${filename}](${encoded})`
    }

    if (['mp4', 'webm', 'ogg'].includes(extension)) {
        return `<video src="${encoded}" controls></video>`
    }

    if (['mp3', 'wav', 'flac'].includes(extension)) {
        return `<audio src="${encoded}" controls></audio>`
    }

    return `[${filename}](${encoded})`
}

window.insertAsset = (filename) => {
    easymde.codemirror.replaceRange(assetMarkdown(filename), easymde.codemirror.getCursor())
};

const dragListeners = new AbortController()

let dragDepth = 0

/**
 * The two areas a file can be dropped on: the assets section, which only
 * uploads the file, and the editor, which also inserts it. Returns null once
 * the component is gone (wire:navigate detaches it from the document), which
 * unregisters the drag listeners.
 */
const findDropZones = () => {
    const zones = {
        assets: $wire.$el.querySelector('#asset-drop-zone'),
        editor: $wire.$el.querySelector('#content-drop-zone'),
    }

    if (! Object.values(zones).every((zone) => zone?.isConnected)) {
        dragListeners.abort()

        return null
    }

    return zones
}

const resetDragState = (zones) => {
    dragDepth = 0

    Object.values(zones).forEach((zone) => {
        zone.toggleAttribute('data-dragging', false)
        zone.toggleAttribute('data-over', false)
    })
}

/**
 * Hand the dropped files over to the file input so that the regular
 * wire:model upload flow takes care of them.
 */
const uploadDroppedAssets = (files) => {
    const dataTransfer = new DataTransfer()

    for (const file of files) {
        dataTransfer.items.add(file)
    }

    if (dataTransfer.files.length === 0) {
        return
    }

    const input = $wire.$el.querySelector('#asset-input')

    input.files = dataTransfer.files
    input.dispatchEvent(new Event('change', { bubbles: true }))
}

/**
 * Insert the dropped files into the editor, at the position they were dropped
 * on. The name of an uploaded asset is always its original one, so the markdown
 * can be written right away, without waiting for the upload to finish.
 */
const insertDroppedAssets = (files, event) => {
    const position = easymde.codemirror.coordsChar({ left: event.clientX, top: event.clientY }, 'window')
    const markdown = [...files].map((file) => assetMarkdown(file.name)).join('\n')

    easymde.codemirror.setCursor(position)
    easymde.codemirror.replaceSelection(markdown)
    easymde.codemirror.focus()
}

/**
 * Listen for a drag event on the whole window, ignoring drags that carry no
 * files. The capture phase is required because CodeMirror stops the propagation
 * of drag events over the Markdown editor, which would otherwise unbalance the
 * dragenter/dragleave counter below, and it lets us handle a drop on the editor
 * before CodeMirror does.
 */
const onFileDrag = (event, handler) => window.addEventListener(event, (e) => {
    if (! e.dataTransfer?.types.includes('Files')) {
        return
    }

    const zones = findDropZones()

    if (zones) {
        handler(zones, e)
    }
}, { capture: true, signal: dragListeners.signal })

/**
 * The state is set on both dragenter and dragover so that it doesn't depend on
 * the order in which the browser fires them: the spec leaves the previous
 * element (dropping the state) before entering the new one.
 */
const markDragState = (zones, event) => {
    Object.values(zones).forEach((zone) => {
        zone.toggleAttribute('data-dragging', true)
        zone.toggleAttribute('data-over', zone.contains(event.target))
    })
}

onFileDrag('dragenter', (zones, event) => {
    dragDepth++

    markDragState(zones, event)
})

onFileDrag('dragleave', (zones) => {
    if (--dragDepth <= 0) {
        resetDragState(zones)
    }
})

onFileDrag('dragover', (zones, event) => {
    markDragState(zones, event)

    // Preventing the default turns the page into a valid drop target, so that
    // dropping outside the zones doesn't make the browser navigate away to the
    // file. Over the editor text it's CodeMirror that has to do it: it ignores
    // drag events whose default is already prevented, and we need it to run so
    // that it draws the caret showing where the file would be inserted...
    if (! easymde.codemirror.getScrollerElement().contains(event.target)) {
        event.preventDefault()
    }
})

onFileDrag('drop', (zones, event) => {
    // CodeMirror checks this before handling the drop itself, so preventing the
    // default keeps it from pasting the raw contents of the file into the
    // editor, while still letting it clear the caret it drew while dragging...
    event.preventDefault()

    const droppedOnAssets = zones.assets.contains(event.target)
    const droppedOnEditor = zones.editor.contains(event.target)

    resetDragState(zones)

    if (! droppedOnAssets && ! droppedOnEditor) {
        return
    }

    if (droppedOnEditor) {
        insertDroppedAssets(event.dataTransfer.files, event)
    }

    uploadDroppedAssets(event.dataTransfer.files)
});
