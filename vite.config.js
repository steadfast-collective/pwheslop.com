import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/cp.css',
                'resources/css/tailwind.css',
                'resources/js/cp.js',
                'resources/js/site.js',
            ],
            refresh: true,
        }),
    ],
});
