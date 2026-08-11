import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        // When the dev server listens on every interface (as it does inside
        // Docker) the URL written to public/hot would be http://[::]:5173,
        // which the browser cannot load. The container sets this variable to
        // the host name the dev server is reachable at.
        hmr: process.env.VITE_DEV_SERVER_HOST
            ? { host: process.env.VITE_DEV_SERVER_HOST }
            : undefined,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
