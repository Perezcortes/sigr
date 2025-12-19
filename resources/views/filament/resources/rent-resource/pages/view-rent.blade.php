<div> <x-filament-panels::page>
        <x-filament-panels::form wire:submit="save">
            {{ $this->form }}
        </x-filament-panels::form>
    </x-filament-panels::page>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    function confirmDelete(id, type) {
        const isDark = document.documentElement.classList.contains('dark');

        const colors = {
            marino: '#161848',
            orange: '#fe5f3b',
            bgDark: '#111827',
            bgLight: '#ffffff'
        };

        Swal.fire({
            title: '¿Eliminar documento?',
            text: 'Esta acción es permanente y no se puede deshacer.',
            icon: 'warning',
            iconColor: colors.orange,
            background: isDark ? colors.bgDark : colors.bgLight,
            color: isDark ? '#ffffff' : colors.marino,
            showCancelButton: true,
            confirmButtonColor: colors.orange,
            cancelButtonColor: colors.marino,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            customClass: {
                popup: 'rounded-3xl shadow-2xl border border-gray-100 dark:border-gray-800',
                confirmButton: 'rounded-xl px-6 py-3 font-bold text-xs uppercase tracking-widest',
                cancelButton: 'rounded-xl px-6 py-3 font-bold text-xs uppercase tracking-widest'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const methodMap = {
                    'tenant': 'deleteTenantDocument',
                    'guarantor': 'deleteGuarantorDocument',
                    'owner': 'deleteOwnerDocument',
                    'property': 'deletePropertyDocument'
                };

                const targetMethod = methodMap[type];

                // Llamada a Livewire
                if (typeof @this !== 'undefined') {
                    @this.call(targetMethod, id);
                }
            }
        });
    }
    </script>

    <style>
        .swal2-confirm, .swal2-cancel {
            transition: all 0.2s ease-in-out !important;
        }
        .swal2-confirm:hover {
            filter: brightness(1.1);
            transform: translateY(-1px);
        }
    </style>
</div> 