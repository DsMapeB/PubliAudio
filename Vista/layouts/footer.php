<?php if (isset($_SESSION['usuario_id'])): ?>
</div>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="public/js/sweetalert.js"></script>
<script src="public/js/app.js"></script>
<?php if (isset($_GET['error']) || isset($_GET['success'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_GET['error'])): ?>
    mostrarError('Error', '<?php echo mensajeError(sanitize($_GET['error'])); ?>');
    <?php endif; ?>
    <?php if (isset($_GET['success'])): ?>
    mostrarExito('Excelente', '<?php echo mensajeExito(sanitize($_GET['success'])); ?>');
    <?php endif; ?>
    if (window.history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.delete('error');
        url.searchParams.delete('success');
        window.history.replaceState({}, '', url);
    }
});
</script>
<?php endif; ?>
</body>
</html>
