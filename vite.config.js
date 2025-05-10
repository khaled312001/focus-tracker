import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import tailwindcss from 'tailwindcss';
import autoprefixer from 'autoprefixer';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/dashboard.css',
                'resources/css/welcome.css',
                'resources/js/app.js',
                'resources/js/welcome.js',
                'resources/js/meeting-room.js',
                'resources/js/teacher-dashboard.js',
                'resources/js/teacher-meeting.js',
                'resources/js/websocket.js',
                'resources/js/bootstrap.js',
                'resources/js/join.js'
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
            '~': path.resolve(__dirname, './resources'),
            'bootstrap': path.resolve(__dirname, 'node_modules/bootstrap'),
        },
    },
    build: {
        outDir: 'public/build',
        assetsDir: 'assets',
        manifest: true,
        rollupOptions: {
            output: {
                manualChunks: undefined,
                entryFileNames: 'assets/[name].js',
                chunkFileNames: 'assets/[name].js',
                assetFileNames: 'assets/[name].[ext]'
            },
        },
    },
    css: {
        postcss: {
            plugins: [
                tailwindcss,
                autoprefixer,
            ],
        },
    },
});
