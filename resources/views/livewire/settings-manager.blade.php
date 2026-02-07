<div class="space-y-6">
    
    {{ $this->form }}

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 p-6">
        
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-6">Configuración de pagos</h3>

        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach($paymentSettings as $payment)
                <div x-data="{ open: false }" class="py-4 first:pt-0" wire:key="payment-row-{{ $payment->id }}">
                    
                    <div class="flex items-center justify-between mb-2">
                        <button @click="open = !open" class="flex items-center gap-2 group text-left focus:outline-none">
                            <span class="text-base font-bold text-gray-900 dark:text-white group-hover:text-[#FF5A1F] transition-colors">
                                {{ $payment->tipo }}
                            </span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <div x-data="{ active: @js((bool)$payment->activo) }">
                            <button type="button" 
                                    x-on:click="active = !active; $wire.togglePayment({{ $payment->id }})"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none bg-gray-200 dark:bg-gray-700"
                                    x-bind:style="active ? 'background-color: #FF5A1F;' : ''"> <span aria-hidden="true" 
                                      class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                      :class="active ? 'translate-x-5' : 'translate-x-0'">
                                </span>
                            </button>
                        </div>
                    </div>

                    <div x-show="open" x-collapse class="mt-4 px-1 space-y-3">
                        <div class="flex items-center">
                            <span class="w-32 text-sm text-gray-400 font-medium">Tipo de pago:</span>
                            <span class="text-sm text-gray-600 dark:text-gray-300">{{ $payment->tipo }}</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-32 text-sm text-gray-400 font-medium">Frecuencia:</span>
                            <span class="text-sm text-gray-600 dark:text-gray-300">{{ $payment->frecuencia }}</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-32 text-sm text-gray-400 font-medium">Monto:</span>
                            <span class="text-sm text-gray-600 dark:text-gray-300">
                                {{ $payment->es_variable ? 'Variable' : '$' . number_format($payment->monto, 2) . ' ' . $payment->moneda }}
                            </span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-32 text-sm text-gray-400 font-medium">Recordatorio:</span>
                            <div class="relative">
                                <div class="border border-gray-300 dark:border-gray-600 rounded-md px-3 py-1 flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-900 shadow-sm">
                                    {{ str_replace('_', ' ', $payment->recordatorio) }}
                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>
                        </div>
                        <div class="pt-2">
                            <button class="w-full py-2 border border-[#FF5A1F] text-[#FF5A1F] bg-white dark:bg-transparent rounded-lg text-sm font-bold hover:bg-orange-50 transition-colors">
                                + Agregar recordatorio
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8 flex justify-center w-full">
            <div class="w-full md:w-auto">
                {{ $this->createPaymentAction }}
            </div>
        </div>
        
    </div>

    <x-filament-actions::modals />
</div>