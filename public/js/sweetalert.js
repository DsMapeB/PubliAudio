const SwalToast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
    }
});

function mostrarExito(titulo, mensaje) {
    SwalToast.fire({
        icon: 'success',
        title: titulo,
        text: mensaje
    });
}

function mostrarError(titulo, mensaje) {
    SwalToast.fire({
        icon: 'error',
        title: titulo,
        text: mensaje
    });
}

function mostrarAdvertencia(titulo, mensaje) {
    SwalToast.fire({
        icon: 'warning',
        title: titulo,
        text: mensaje
    });
}

function mostrarInfo(titulo, mensaje) {
    SwalToast.fire({
        icon: 'info',
        title: titulo,
        text: mensaje
    });
}

function mostrarConfirmacion(titulo, mensaje, callbackConfirm, callbackCancel) {
    Swal.fire({
        title: titulo,
        text: mensaje,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1DB954',
        cancelButtonColor: '#EF4444',
        confirmButtonText: 'Sí',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed && callbackConfirm) callbackConfirm();
        else if (callbackCancel) callbackCancel();
    });
}

function mostrarLoading(titulo = 'Cargando...') {
    Swal.fire({
        title: titulo,
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });
}

function cerrarLoading() {
    Swal.close();
}

function confirmarEliminacion(titulo, mensaje, callback) {
    mostrarConfirmacion(titulo, mensaje, callback);
}
