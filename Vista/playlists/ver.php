<div class="mb-8">
    <a href="<?php echo url('playlists'); ?>" class="text-sm text-gray-500 hover:text-spotify transition-colors flex items-center gap-2 mb-4">
        <i class="fas fa-arrow-left"></i>
        Volver a playlists
    </a>

    <div class="flex items-center gap-6">
        <?php if ($playlist && !empty($playlist['images'][0]['url'])): ?>
        <img src="<?php echo e($playlist['images'][0]['url']); ?>" alt="" class="w-32 h-32 rounded-xl object-cover shadow-lg">
        <?php endif; ?>
        <div>
            <h1 class="text-2xl font-bold"><?php echo e($playlist['name'] ?? 'Playlist'); ?></h1>
            <p class="text-gray-500 dark:text-darkText mt-1">
                <?php echo e($playlist['owner']['display_name'] ?? 'Desconocido'); ?> ·
                <?php echo count($canciones); ?> canciones
            </p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-darkCard rounded-xl border border-gray-200 dark:border-darkBorder overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-darkBorder">
                <h2 class="font-semibold">Canciones</h2>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-darkBorder">
                <?php foreach ($canciones as $i => $item): ?>
                <?php $track = $item['item'] ?? []; ?>
                <div class="flex items-center gap-4 px-6 py-3 hover:bg-gray-50 dark:hover:bg-darkBorder/50 transition-colors">
                    <span class="text-sm text-gray-400 w-6"><?php echo $i + 1; ?></span>
                    <?php if (!empty($track['album']['images'][0]['url'])): ?>
                    <img src="<?php echo e($track['album']['images'][0]['url']); ?>" alt="" class="w-10 h-10 rounded object-cover">
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-sm truncate"><?php echo e($track['name'] ?? 'Desconocida'); ?></p>
                        <p class="text-xs text-gray-400 truncate">
                            <?php echo e(implode(', ', array_map(fn($a) => $a['name'], $track['artists'] ?? []))); ?>
                        </p>
                    </div>
                    <?php if (isset($track['duration_ms'])): ?>
                    <span class="text-xs text-gray-400"><?php echo gmdate('i:s', floor($track['duration_ms'] / 1000)); ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div>
        <div class="bg-white dark:bg-darkCard rounded-xl border border-gray-200 dark:border-darkBorder p-6 sticky top-6">
            <h2 class="font-semibold mb-4">Configurar reproducción</h2>

            <form action="<?php echo url('playlist_guardar_config'); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="playlist_id" value="<?php echo e($playlist['id'] ?? ''); ?>">
                <input type="hidden" name="playlist_nombre" value="<?php echo e($playlist['name'] ?? ''); ?>">
                <input type="hidden" name="playlist_imagen" value="<?php echo e($playlist['images'][0]['url'] ?? ''); ?>">

                <div class="mb-5">
                    <label class="block text-sm font-medium mb-2">Audio personalizado</label>
                    <select name="audio_id" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-darkBorder bg-white dark:bg-darkBg focus:ring-2 focus:ring-spotify focus:border-transparent outline-none transition-all">
                        <option value="">Seleccionar audio...</option>
                        <?php foreach ($audios as $a): ?>
                        <option value="<?php echo $a['id']; ?>" <?php echo ($configActual['audio_id'] ?? 0) == $a['id'] ? 'selected' : ''; ?>>
                            <?php echo e($a['nombre']); ?> (<?php echo tiempoFormateado($a['duracion']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-medium mb-2">Reproducir cada</label>
                    <select name="canciones_intervalo" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-darkBorder bg-white dark:bg-darkBg focus:ring-2 focus:ring-spotify focus:border-transparent outline-none transition-all">
                        <?php $selectedIntervalo = $configActual['canciones_intervalo'] ?? 3; ?>
                        <?php foreach ([1, 2, 3, 5, 10] as $num): ?>
                        <option value="<?php echo $num; ?>" <?php echo $selectedIntervalo == $num ? 'selected' : ''; ?>>
                            <?php echo $num; ?> canción<?php echo $num > 1 ? 'es' : ''; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="w-full bg-spotify hover:bg-spotifyDark text-white font-semibold py-2.5 px-6 rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2">
                    <i class="fas fa-save"></i>
                    Guardar configuración
                </button>
            </form>

            <?php if (isset($configActual) && $configActual): ?>
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-darkBorder">
                <a href="<?php echo url('player', ['config' => $configActual['id']]); ?>"
                   class="w-full bg-spotify hover:bg-spotifyDark text-white font-semibold py-3 px-6 rounded-xl transition-all duration-300 flex items-center justify-center gap-2">
                    <i class="fas fa-play"></i>
                    Ir al reproductor
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
