<div class="space-y-4" @if($showQr || ($whatsappInstance && $whatsappInstance->isConnecting())) wire:poll.5s="checkStatus" @endif>
    <div class="text-sm text-gray-600 dark:text-gray-400">
        @if ($whatsappInstance)
            <p>
                <span class="font-medium text-gray-950 dark:text-white">{{ __('filament-evolution::resource.fields.name') }}:</span>
                {{ $whatsappInstance->name }}
            </p>
            <p class="mt-1">
                <span class="font-medium text-gray-950 dark:text-white">{{ __('filament-evolution::resource.fields.status') }}:</span>
                @if ($whatsappInstance->isConnected())
                    <span class="text-success-600 dark:text-success-400">Conectado</span>
                @else
                    <span>{{ $whatsappInstance->status?->value ?? (string) $whatsappInstance->status }}</span>
                @endif
            </p>
        @else
            <p>
                Crea una instancia con el mismo número que usarás en WhatsApp. Después podrás escanear el código QR para conectarla.
            </p>
        @endif
    </div>

    @if (! $whatsappInstance)
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="advisor-wa-number" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">
                    Número (WhatsApp)
                </label>
                <input
                    id="advisor-wa-number"
                    type="text"
                    wire:model="createNumber"
                    maxlength="20"
                    class="fi-input block w-full rounded-lg border-gray-300 bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 focus:border-primary-600 focus:ring-2 focus:ring-inset focus:ring-primary-600 disabled:bg-gray-50 disabled:text-gray-500 disabled:opacity-70 dark:border-white/10 dark:bg-white/5 dark:text-white dark:ring-white/20 dark:focus:border-primary-400 dark:focus:ring-primary-400 sm:text-sm"
                    placeholder="Ej. 5215512345678"
                />
                @error('createNumber')
                    <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                @enderror
            </div>
            <x-filament::button type="button" wire:click="createInstance" wire:loading.attr="disabled" color="primary">
                Crear instancia y conectar
            </x-filament::button>
        </div>
    @else
        @if (! $whatsappInstance->isConnected())
            <div class="flex flex-wrap gap-2">
                @if (! $showQr)
                    <x-filament::button type="button" wire:click="openQr" wire:loading.attr="disabled" color="success" icon="heroicon-o-qr-code">
                        Mostrar código QR
                    </x-filament::button>
                @else
                    <x-filament::button type="button" wire:click="closeQr" wire:loading.attr="disabled" color="gray">
                        Ocultar QR
                    </x-filament::button>
                @endif
            </div>
        @endif
    @endif

    @if ($showQr && $whatsappInstance)
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
            @if ($qrCode)
                <div class="flex flex-col items-center justify-center p-4 bg-white rounded-lg shadow-sm border dark:bg-gray-800 dark:border-gray-700 max-w-[320px] mx-auto">
                    <img src="{{ $qrCode }}" alt="Scan QR Code" class="w-64 h-64 border border-gray-200 rounded dark:border-gray-700" />
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 text-center">Escanea este código con tu aplicación de WhatsApp</p>
                </div>
            @else
                <div class="flex flex-col items-center justify-center p-4">
                    <x-filament::loading-indicator class="w-8 h-8 text-primary-600" />
                    <p class="mt-2 text-xs text-gray-500">Generando código QR...</p>
                </div>
            @endif
        </div>
    @endif
</div>
