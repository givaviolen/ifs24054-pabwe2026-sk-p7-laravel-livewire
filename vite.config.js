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
    build: {
        // Browser modern saja: tanpa transpile ke sintaks lama.
        target: 'esnext',
        // Hapus polyfill modulepreload (kode "legacy" kecil di bundel).
        modulePreload: {
            polyfill: false,
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});