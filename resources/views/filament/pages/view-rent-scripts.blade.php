<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmDelete(id, type) {
    // Detectamos si el sistema está en modo oscuro
    const isDark = document.documentElement.classList.contains('dark');

    const colors = {
        marino: '#161848',
        cian:   '#26cad3',
        naranja: '#fe5f3b',
        bgDark:  '#111827',
        bgLight: '#ffffff'
    };

    Swal.fire({
        title: '¿Eliminar documento?',
        text: 'Esta acción es permanente y no se puede deshacer.',
        icon: 'warning',
        iconColor: colors.naranja,
        
        // Estética adaptativa
        background: isDark ? colors.bgDark : colors.bgLight,
        color: isDark ? '#ffffff' : colors.marino,
        
        showCancelButton: true,
        confirmButtonColor: colors.naranja, // Naranja para peligro
        cancelButtonColor: colors.marino,  // Azul marino para cancelar
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        
        reverseButtons: true, 
        
        // bordes y sombras 
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

            // Notifica a Livewire mediante un evento global (si tienes listeners configurados)
            Livewire.dispatch('callMethod', { method: targetMethod, params: [id] });

            // Ejecuta la llamada directa al componente de Filament/Livewire actual
            if (typeof @this !== 'undefined') {
                @this.call(targetMethod, id);
            }
        }
    });
}
</script>

<style>
    /* Estilo para suavizar la transición de los botones del modal */
    .swal2-confirm, .swal2-cancel {
        transition: all 0.2s ease-in-out !important;
    }
    .swal2-confirm:hover {
        filter: brightness(1.1);
        transform: translateY(-1px);
    }
    .swal2-cancel:hover {
        filter: contrast(1.2);
        transform: translateY(-1px);
    }
</style>