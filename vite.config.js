import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
server: {
    host: '0.0.0.0',
    hmr: {
        host: 'localhost',
    },
    allowedHosts: [
        'all',
        '25e9c6837789.ngrok-free.app'
    ]
},

    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});

