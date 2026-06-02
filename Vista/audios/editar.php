<div class="max-w-2xl mx-auto">
    <div class="mb-8">
        <a href="<?php echo url('audios'); ?>" class="text-sm text-gray-500 hover:text-spotify transition-colors flex items-center gap-2 mb-4">
            <i class="fas fa-arrow-left"></i>
            Volver a mis audios
        </a>
        <h1 class="text-2xl font-bold">Editar Audio</h1>
    </div>

    <div class="bg-white dark:bg-darkCard rounded-xl border border-gray-200 dark:border-darkBorder p-8">
        <form action="<?php echo url('audio_actualizar'); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id" value="<?php echo $audio['id']; ?>">

            <div class="flex items-center gap-4 mb-6 p-4 bg-gray-50 dark:bg-darkBg rounded-lg">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-spotify/20 to-blue-500/20 flex items-center justify-center">
                    <i class="fas fa-headphones text-2xl text-spotify"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-400">Archivo actual</p>
                    <p class="font-medium"><?php echo e($audio['archivo']); ?></p>
                    <p class="text-xs text-gray-400"><?php echo tiempoFormateado($audio['duracion']); ?> · <?php echo formatoFecha($audio['created_at']); ?></p>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Nombre del audio</label>
                <input type="text" name="nombre" value="<?php echo e($audio['nombre']); ?>" required
                       class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-darkBorder bg-white dark:bg-darkBg focus:ring-2 focus:ring-spotify focus:border-transparent outline-none transition-all">
            </div>

            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-spotify hover:bg-spotifyDark text-white font-semibold py-2.5 px-6 rounded-xl transition-all duration-300">
                    <i class="fas fa-save mr-2"></i>
                    Guardar cambios
                </button>
                <a href="<?php echo url('audios'); ?>" class="px-6 py-2.5 rounded-xl border border-gray-300 dark:border-darkBorder hover:bg-gray-50 dark:hover:bg-darkBorder transition-colors">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
