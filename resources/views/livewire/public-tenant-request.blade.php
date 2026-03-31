<div class="min-h-screen py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        
        <div class="bg-white dark:bg-[#161848] shadow-xl rounded-2xl overflow-hidden ring-1 ring-gray-900/5 dark:ring-white/10">
            
            <div class="bg-[#161848] dark:bg-[#0f1133] py-6 px-8 text-center border-b border-gray-900/10 dark:border-white/10">
                <h1 class="text-2xl font-bold text-white">Solicitud de Arrendamiento</h1>
                <p class="text-gray-300 mt-2 text-sm">Por favor, complete cuidadosamente la siguiente información para procesar su perfil.</p>
            </div>

            <div class="p-8">
                @if($isSubmitted)
                    <div class="rounded-xl bg-green-50 dark:bg-green-900/20 p-6 text-center border border-green-200 dark:border-green-800">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-800/50 mb-4">
                            <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-green-800 dark:text-green-400 mb-2">¡Solicitud recibida exitosamente!</h3>
                        <p class="text-sm text-green-700 dark:text-green-300">Sus datos han sido guardados y serán analizados por uno de nuestros asesores. Puede cerrar esta ventana.</p>
                    </div>
                @else
                    <form wire:submit="save">
                        
                        {{ $this->form }}

                        <div class="mt-8 flex flex-col-reverse sm:flex-row justify-end items-center gap-4 border-t border-gray-200 dark:border-gray-800/50 pt-6">
                            
                            <button type="button" onclick="window.location.reload()" class="w-full sm:w-auto px-6 py-2.5 bg-white dark:bg-[#0f1133] border border-gray-300 dark:border-white/10 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-white/5 transition-colors text-sm shadow-sm">
                                Cancelar
                            </button>
                            
                            <button type="submit" class="fi-btn-primary w-full sm:w-auto px-8 py-2.5 rounded-lg text-sm px-4 py-2 flex items-center justify-center font-bold shadow-sm">
                                Guardar
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

    </div>
</div>