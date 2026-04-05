import preset from './vendor/filament/support/tailwind.config.preset'

/** @type {import('tailwindcss').Config} */
export default {
    presets: [preset],
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './app/Livewire/**/*.php',
        './vendor/filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                // Aquí definimos tus colores de marca para usarlos libremente en HTML
                primary: {
                    50: '#eefbfa',
                    100: '#d5f5f4',
                    200: '#aeeee9',
                    300: '#7ae2da',
                    400: '#4cd1c9',
                    500: '#26cad3', // Tu Cyan
                    600: '#21a1aa',
                    700: '#1e818a',
                    800: '#1b676f',
                    900: '#19555c',
                    950: '#0d3238',
                },
            }
        },
    },
    plugins: [],
}