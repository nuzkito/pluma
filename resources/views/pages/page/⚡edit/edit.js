mountMarkdownEditor($wire, {
    assetsUrl: () => `/pages/${$wire.$el.querySelector('#path').value}/assets/`,
})

$wire.on('url-changed', (path) => {
    history.pushState({}, '', `/pages/${path}/edit`)
});
