<div class="flex flex-col items-center justify-center p-4">
    @if ($qrCode)
        <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700 max-w-[280px] mx-auto">
            <img src="{{ $qrCode }}" alt="Scan QR Code" class="w-64 h-64 border border-gray-200 rounded dark:border-gray-700 mx-auto" />
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 text-center">Escanea este código con tu aplicación de WhatsApp</p>
        </div>
    @else
        <div class="text-center py-6">
            <svg class="animate-spin h-8 w-8 text-primary-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Iniciando sesión en OpenWA...</p>
            <p class="mt-1 text-xs text-gray-400">Si no aparece el código QR, cierra este modal e intenta de nuevo en unos segundos.</p>
        </div>
    @endif
</div>
