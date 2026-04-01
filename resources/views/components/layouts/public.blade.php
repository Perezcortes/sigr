<!DOCTYPE html>
<html lang="es" 
    x-data="{ theme: localStorage.getItem('theme') || 'light' }" 
    x-bind:class="theme === 'dark' ? 'dark' : ''" 
    x-init="$watch('theme', val => localStorage.setItem('theme', val))">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Solicitud de Arrendamiento</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    
    <style>
        body, html, .fi-body { 
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important; 
        }
    </style>
    @filamentStyles
    @vite(['resources/css/app.css', 'resources/css/filament/admin/theme.css'])
</head>
<body class="fi-body font-sans antialiased bg-gray-100 dark:bg-gray-950 text-gray-900 dark:text-gray-100 transition-colors duration-300 relative">
    
    <button 
        @click="theme = theme === 'dark' ? 'light' : 'dark'" 
        class="fixed top-4 right-4 z-50 p-2 rounded-full bg-white dark:bg-gray-800 shadow-md text-gray-500 dark:text-gray-400 hover:text-[#26cad3] focus:outline-none transition-colors"
        title="Alternar tema">
        <svg x-show="theme === 'light'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" /></svg>
        <svg x-show="theme === 'dark'" x-cloak class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" /></svg>
    </button>

    {{ $slot }}

    @filamentScripts
    @vite('resources/js/app.js')
</body>
</html>