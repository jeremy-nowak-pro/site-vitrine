import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    build: {
        // Le seul fichier au-delà de 500 Ko est la visionneuse (Three.js), chargée à la demande sur la fiche pièce.
        chunkSizeWarningLimit: 1100,
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**', '**/.claude/**'],
        },
    },
});
