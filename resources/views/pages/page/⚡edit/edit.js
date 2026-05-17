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
        const baseUrl = `/pages/${pagePath}/attachments/`

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

window.insertAttachment = (filename) => {
    const extension = filename.split('.').pop().toLowerCase()
    const encoded = encodeURIComponent(filename)

    let markdown

    if (['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'].includes(extension)) {
        markdown = `![${filename}](${encoded})`
    } else if (['mp4', 'webm', 'ogg'].includes(extension)) {
        markdown = `<video src="${encoded}" controls></video>`
    } else if (['mp3', 'wav', 'flac'].includes(extension)) {
        markdown = `<audio src="${encoded}" controls></audio>`
    } else {
        markdown = `[${filename}](${encoded})`
    }

    easymde.codemirror.replaceRange(markdown, easymde.codemirror.getCursor())
};
