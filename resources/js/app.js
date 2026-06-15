import './bootstrap';

// Registro del Service Worker (PWA)
if (import.meta.env.PROD && 'serviceWorker' in navigator) {
    window.addEventListener('load', async () => {
        try {
            await navigator.serviceWorker.register('/sw.js', { scope: '/' });
        } catch (e) {
            // No bloqueamos la app si falla el SW
            console.warn('No se pudo registrar el Service Worker', e);
        }
    });
}
