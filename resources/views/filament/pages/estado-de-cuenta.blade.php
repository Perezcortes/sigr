<x-filament-panels::page>
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 mb-6">
        <div class="flex items-start gap-4">
            <div class="p-3 bg-[#161848]/10 text-[#161848] dark:bg-[#26cad3]/20 dark:text-[#26cad3] rounded-lg">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
            </div>
            <div class="flex-1">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Link de Cobro de Apartados</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Comparte este enlace de Stripe con tus clientes para cobrar apartados. La empresa resguardará este dinero hasta que la operación se cierre.
                </p>
                
                <div class="flex items-center gap-3">
                    <input type="text" readonly value="{{ $linkStripeApartados }}" class="w-full max-w-md bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-[#26cad3] focus:border-[#26cad3] block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" id="stripeLink">
                    
                    <button onclick="copyToClipboard()" wire:click="copiarLink" type="button" class="text-white bg-[#26cad3] hover:bg-[#1faeb8] focus:ring-4 focus:ring-[#26cad3]/50 font-medium rounded-lg text-sm px-5 py-2.5 focus:outline-none transition-colors">
                        Copiar Link
                    </button>
                </div>
            </div>
        </div>
    </div>

    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 mt-8">Mi Billetera Virtual</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4">
                <svg class="w-16 h-16 text-gray-100 dark:text-gray-700 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8V7z" />
                </svg>
            </div>
            <div class="relative z-10">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Saldo en Resguardo</p>
                <h3 class="text-3xl font-black text-gray-900 dark:text-white mb-2">
                    ${{ number_format($saldoRetenido, 2) }} <span class="text-base font-medium text-gray-500">MXN</span>
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-4">
                    Este monto corresponde a apartados de operaciones <b>en proceso</b>. Será liberado automáticamente cuando la operación cambie a estatus "Cerrada" o "Activa".
                </p>
            </div>
        </div>

        <div class="bg-gradient-to-br from-[#161848] to-[#1e2161] rounded-xl shadow-lg p-6 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4">
                <svg class="w-16 h-16 text-white/10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="relative z-10">
                <p class="text-sm font-medium text-[#26cad3] uppercase tracking-wider mb-1">Saldo Disponible</p>
                <h3 class="text-3xl font-black text-white mb-2">
                    ${{ number_format($saldoDisponible, 2) }} <span class="text-base font-medium text-gray-300">MXN</span>
                </h3>
                <p class="text-xs text-gray-300 mt-4">
                    Monto liberado de operaciones cerradas. Puedes usar este saldo para <b>pagar regalías</b> en el Centro de Pagos o adquirir material promocional.
                </p>
            </div>
        </div>

    </div>

    <script>
        function copyToClipboard() {
            var copyText = document.getElementById("stripeLink");
            copyText.select();
            copyText.setSelectionRange(0, 99999); // Para móviles
            navigator.clipboard.writeText(copyText.value);
        }
    </script>
</x-filament-panels::page>