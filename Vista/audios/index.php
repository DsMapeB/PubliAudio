<div class="mb-8 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold">Mis Audios</h1>
        <p class="text-gray-500 dark:text-darkText mt-1">Gestiona tus archivos de audio personalizados</p>
    </div>
    <a href="<?php echo url('audio_crear'); ?>" class="flex items-center gap-2 bg-spotify hover:bg-spotifyDark text-white px-5 py-2.5 rounded-xl font-medium transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98]">
        <i class="fas fa-plus"></i>
        Subir Audio
    </a>
</div>

<div class="bg-white dark:bg-darkCard rounded-xl border border-gray-200 dark:border-darkBorder overflow-hidden">
    <?php if (empty($audios)): ?>
    <div class="text-center py-16 px-6">
        <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-darkBorder flex items-center justify-center">
            <i class="fas fa-microphone text-3xl text-gray-300"></i>
        </div>
        <h3 class="text-lg font-semibold mb-2">No hay audios aún</h3>
        <p class="text-gray-500 dark:text-darkText mb-6 max-w-md mx-auto">Sube archivos MP3 para intercalarlos en tus playlists de Spotify.</p>
        <a href="<?php echo url('audio_crear'); ?>" class="inline-flex items-center gap-2 bg-spotify hover:bg-spotifyDark text-white px-6 py-2.5 rounded-xl font-medium transition-colors">
            <i class="fas fa-plus"></i>
            Subir primer audio
        </a>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200 dark:border-darkBorder bg-gray-50 dark:bg-darkBg">
                    <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                    <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Duración</th>
                    <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Subido</th>
                    <th class="text-right px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-darkBorder">
                <?php foreach ($audios as $audio): ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-darkBorder/50 transition-colors" data-audio-id="<?php echo $audio['id']; ?>">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-spotify/20 to-blue-500/20 flex items-center justify-center">
                                <i class="fas fa-headphones text-spotify"></i>
                            </div>
                            <div>
                                <p class="font-medium"><?php echo e($audio['nombre']); ?></p>
                                <p class="text-xs text-gray-400"><?php echo e(basename($audio['archivo'])); ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo tiempoFormateado($audio['duracion']); ?></td>
                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo formatoFecha($audio['created_at']); ?></td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button onclick="previewAudio(<?php echo $audio['id']; ?>)" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-darkBorder transition-colors text-gray-400 hover:text-spotify" title="Vista previa">
                                <i class="fas fa-play"></i>
                            </button>
                            <a href="<?php echo url('audio_editar', ['id' => $audio['id']]); ?>" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-darkBorder transition-colors text-gray-400 hover:text-blue-500" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button onclick="eliminarAudio(<?php echo $audio['id']; ?>, '<?php echo e($audio['nombre']); ?>')" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-darkBorder transition-colors text-gray-400 hover:text-red-500" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<audio id="previewAudio" class="hidden"></audio>

<script>
const previewAudioEl = document.getElementById('previewAudio');

function previewAudio(id) {
    fetch('<?php echo url('audio_obtener_info'); ?>&id=' + id)
        .then(r => r.json())
        .then(data => {
            if (data.url) {
                if (!previewAudioEl.paused) previewAudioEl.pause();
                previewAudioEl.src = data.url;
                previewAudioEl.play();
                mostrarExito('Reproduciendo', data.nombre);
            }
        });
}

function eliminarAudio(id, nombre) {
    confirmarEliminacion(
        '¿Eliminar audio?',
        'Se eliminará "' + nombre + '" permanentemente.',
        function() {
            fetch('<?php echo url('audio_eliminar'); ?>&id=' + id)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector(`tr[data-audio-id="${id}"]`)?.remove();
                        mostrarExito('Eliminado', 'Audio eliminado correctamente');
                        setTimeout(() => location.reload(), 1000);
                    }
                });
        }
    );
}
</script>
