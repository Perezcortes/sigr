import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/filament/admin/theme.css',
            ],
            refresh: true,
        }),
        VitePWA({
            registerType: 'autoUpdate',
            injectRegister: null, // lo registramos manualmente en app.js
            manifest: {
                name: 'SIGR',
                short_name: 'SIGR',
                description: 'Sistema de gestión de rentas',
                theme_color: '#161848',
                background_color: '#ffffff',
                display: 'standalone',
                scope: '/',
                start_url: '/',
                icons: [
                    {
                        src: '/images/logo-rentas-b.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any',
                    },
                    {
                        src: '/images/logo-rentas-w.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any',
                    },
                ],
            },
            workbox: {
                navigateFallback: '/offline.html',
                // Evita cachear HTML dinámico de Filament/Livewire/API
                navigateFallbackDenylist: [
                    /^\/admin(\/.*)?$/,
                    /^\/livewire(\/.*)?$/,
                    /^\/api(\/.*)?$/,
                    /^\/sanctum(\/.*)?$/,
                ],
                runtimeCaching: [
                    {
                        urlPattern: ({ request }) =>
                            request.destination === 'style' ||
                            request.destination === 'script' ||
                            request.destination === 'worker',
                        handler: 'StaleWhileRevalidate',
                        options: {
                            cacheName: 'assets',
                            expiration: { maxEntries: 100, maxAgeSeconds: 60 * 60 * 24 * 30 },
                        },
                    },
                    {
                        urlPattern: ({ request }) =>
                            request.destination === 'image' || request.destination === 'font',
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'static-media',
                            expiration: { maxEntries: 200, maxAgeSeconds: 60 * 60 * 24 * 60 },
                        },
                    },
                ],
            },
            includeAssets: [
                'offline.html',
                'images/favicon.ico',
                'images/logo-rentas-b.png',
                'images/logo-rentas-w.png',
            ],
        }),
    ],
});