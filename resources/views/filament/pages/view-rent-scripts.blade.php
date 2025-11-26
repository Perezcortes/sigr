<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmDelete(id, type) {
    Swal.fire({
        title: '¿Eliminar documento?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            const methodMap = {
                'tenant': 'deleteTenantDocument',
                'guarantor': 'deleteGuarantorDocument',
                'owner': 'deleteOwnerDocument',
                'property': 'deletePropertyDocument'
            };
            Livewire.dispatch('callMethod', { method: methodMap[type], params: [id] });
            @this.call(methodMap[type], id);
        }
    });
}
</script>

