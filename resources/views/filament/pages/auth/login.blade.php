{{-- fixed inset-0: ignora el padding/max-width que el layout de Filament le pone al <main> --}}
<div class="fixed inset-0 z-0 overflow-y-auto">
    {{-- Fondo: cubre toda la pantalla siempre (móvil y escritorio), la tarjeta flota encima --}}
    <div
        class="absolute inset-0 hidden bg-cover bg-center md:block"
        style="background-image: url('{{ asset('images/login-bg-desktop.jpg') }}')"
    ></div>
    <div
        class="absolute inset-0 bg-cover bg-center md:hidden"
        style="background-image: url('{{ asset('images/login-bg-mobile.jpg') }}')"
    ></div>

    {{-- Tarjeta --}}
    {{-- md:w-full (no md:w-auto): w-auto + hijo con % es undefined behavior (CSS 2.2 §10.2) --}}
    <div class="relative px-4 pt-20 md:absolute md:left-48 md:top-1/2 md:w-full md:max-w-lg md:px-0 md:pt-0 md:-translate-y-1/2">
        <div class="rounded-2xl bg-white p-10 shadow-xl">
            <div class="text-center">
                <img src="{{ asset('images/logo-rentas-w.png') }}" alt="Rentas.com" class="mb-4 h-8 w-auto mx-auto">
                <h2 class="mb-6 text-lg font-bold text-[#161848]">Accede a tu cuenta</h2>
            </div>
            <div>

                @if (filament()->hasRegistration())
                    <p class="mb-4 text-sm text-gray-600">
                        {{ __('filament-panels::pages/auth/login.actions.register.before') }}
                        {{ $this->registerAction }}
                    </p>
                @endif

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

                <x-filament-panels::form id="form" wire:submit="authenticate">
                    {{ $this->form }}

                    <x-filament-panels::form.actions
                        :actions="$this->getCachedFormActions()"
                        :full-width="$this->hasFullWidthFormActions()"
                    />
                </x-filament-panels::form>

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}
            </div>
        </div>
    </div>

    <x-filament-actions::modals />
</div>
