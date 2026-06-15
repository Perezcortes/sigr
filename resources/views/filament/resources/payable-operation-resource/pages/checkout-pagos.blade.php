<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-white/5">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                        Resumen de Operaciones a Pagar
                    </h2>
                </div>
                
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($operaciones as $op)
                        <div class="p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-white/5 transition">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="px-2 py-0.5 rounded text-xs font-bold bg-{{ str_contains($op->payable_type, 'Rent') ? 'blue' : 'orange' }}-100 text-{{ str_contains($op->payable_type, 'Rent') ? 'blue' : 'orange' }}-800 dark:bg-{{ str_contains($op->payable_type, 'Rent') ? 'blue' : 'orange' }}-900/30 dark:text-{{ str_contains($op->payable_type, 'Rent') ? 'blue' : 'orange' }}-400">
                                        {{ str_contains($op->payable_type, 'Rent') ? 'Renta' : 'Venta' }}
                                    </span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Firma: {{ $op->fecha_firma->format('d/m/Y') }}</span>
                                </div>
                                <h3 class="font-bold text-gray-900 dark:text-white">{{ $op->nombre_cliente }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Comisión total: ${{ number_format($op->monto_comision, 2) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500 mb-1">Regalía (12%)</p>
                                <p class="text-lg font-bold text-[#fe5f3b]">${{ number_format($op->regalia, 2) }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 sticky top-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Total a pagar</h3>
                <div class="text-4xl font-black text-[#26cad3] mb-6">
                    ${{ number_format($totalPagar, 2) }} <span class="text-lg text-gray-500">MXN</span>
                </div>

                <div class="space-y-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-bold mb-2 uppercase tracking-wider">Selecciona tu método de pago</p>
                    
                    <div class="w-full flex flex-col gap-3">
                        {{ $this->pagarConTarjetaAction }}
                        {{ $this->pagarConTransferenciaAction }}
                        {{ $this->pagarConSaldoAction }}
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8V7z"/></svg>
                        Tus pagos están protegidos y encriptados.
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-filament-panels::page>