<div class="mb-8">
    <h1 class="text-2xl font-bold">Dashboard</h1>
    <p class="text-gray-500 dark:text-darkText mt-1">Bienvenido, <?php echo e($_SESSION['nombre']); ?></p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white dark:bg-darkCard rounded-xl p-6 border border-gray-200 dark:border-darkBorder hover:shadow-lg transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                <i class="fas fa-music text-blue-600 dark:text-blue-400 text-xl"></i>
            </div>
            <span class="text-3xl font-bold"><?php echo $totalPlaylists; ?></span>
        </div>
        <h3 class="text-sm font-medium text-gray-500 dark:text-darkText">Playlists configuradas</h3>
    </div>

    <div class="bg-white dark:bg-darkCard rounded-xl p-6 border border-gray-200 dark:border-darkBorder hover:shadow-lg transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                <i class="fas fa-microphone text-green-600 dark:text-green-400 text-xl"></i>
            </div>
            <span class="text-3xl font-bold"><?php echo $totalAudios; ?></span>
        </div>
        <h3 class="text-sm font-medium text-gray-500 dark:text-darkText">Audios subidos</h3>
    </div>

    <div class="bg-white dark:bg-darkCard rounded-xl p-6 border border-gray-200 dark:border-darkBorder hover:shadow-lg transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                <i class="fas fa-play-circle text-purple-600 dark:text-purple-400 text-xl"></i>
            </div>
            <span class="text-3xl font-bold"><?php echo $configuracionesActivas; ?></span>
        </div>
        <h3 class="text-sm font-medium text-gray-500 dark:text-darkText">Configuraciones activas</h3>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white dark:bg-darkCard rounded-xl border border-gray-200 dark:border-darkBorder">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-darkBorder flex items-center justify-between">
            <h2 class="font-semibold">Playlists</h2>
            <a href="<?php echo url('playlists'); ?>" class="text-sm text-spotify hover:underline">Ver todas</a>
        </div>
        <div class="p-6">
            <?php if (empty($playlists)): ?>
            <div class="text-center py-8 text-gray-400">
                <i class="fas fa-music text-4xl mb-3"></i>
                <p>No se encontraron playlists</p>
                <a href="<?php echo url('playlists'); ?>" class="text-spotify hover:underline text-sm mt-2 inline-block">Explorar playlists</a>
            </div>
            <?php else: ?>
            <div class="space-y-3">
                    <?php foreach (array_slice($playlists, 0, 5) as $pl): ?>
                    <a href="<?php echo url('playlist_ver', ['id' => $pl['id'] ?? '']); ?>" class="flex items-center gap-4 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-darkBorder transition-colors group">
                    <img src="<?php echo e($pl['images'][0]['url'] ?? ''); ?>" alt="" class="w-12 h-12 rounded-lg object-cover bg-gray-100" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect fill=%22%23333%22 width=%22100%22 height=%22100%22/><text fill=%22%23999%22 font-size=%2250%22 x=%2250%22 y=%2265%22 text-anchor=%22middle%22>♪</text></svg>'">
                    <div class="flex-1 min-w-0">
                        <p class="font-medium truncate group-hover:text-spotify transition-colors"><?php echo e($pl['name'] ?? ''); ?></p>
                        <p class="text-sm text-gray-400"><?php echo e($pl['tracks']['total'] ?? 0); ?> canciones</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-300 group-hover:text-spotify transition-colors text-sm"></i>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white dark:bg-darkCard rounded-xl border border-gray-200 dark:border-darkBorder">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-darkBorder flex items-center justify-between">
                <h2 class="font-semibold">Mis Audios</h2>
                <a href="<?php echo url('audios'); ?>" class="text-sm text-spotify hover:underline">Ver todos</a>
            </div>
            <div class="p-6">
                <?php if (empty($audios)): ?>
                <div class="text-center py-8 text-gray-400">
                    <i class="fas fa-microphone text-4xl mb-3"></i>
                    <p>No has subido audios</p>
                    <a href="<?php echo url('audio_crear'); ?>" class="text-spotify hover:underline text-sm mt-2 inline-block">Subir audio</a>
                </div>
                <?php else: ?>
                <div class="space-y-2">
                    <?php foreach (array_slice($audios, 0, 5) as $a): ?>
                    <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-darkBorder transition-colors">
                        <i class="fas fa-headphones text-gray-400"></i>
                        <span class="flex-1 truncate text-sm"><?php echo e($a['nombre']); ?></span>
                        <span class="text-xs text-gray-400"><?php echo tiempoFormateado($a['duracion']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white dark:bg-darkCard rounded-xl border border-gray-200 dark:border-darkBorder">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-darkBorder flex items-center justify-between">
                <h2 class="font-semibold">Configuraciones</h2>
            </div>
            <div class="p-6">
                <?php if (empty($configuraciones)): ?>
                <div class="text-center py-8 text-gray-400">
                    <i class="fas fa-cog text-4xl mb-3"></i>
                    <p>Sin configuraciones aún</p>
                </div>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($configuraciones as $cfg): ?>
                    <div class="flex items-center justify-between p-3 rounded-lg border border-gray-100 dark:border-darkBorder">
                        <div class="flex items-center gap-3">
                            <img src="<?php echo e($cfg['playlist_imagen'] ?? ''); ?>" alt="" class="w-10 h-10 rounded object-cover" onerror="this.style.display='none'">
                            <div>
                                <p class="text-sm font-medium truncate max-w-[180px]"><?php echo e($cfg['playlist_nombre'] ?? ''); ?></p>
                                <p class="text-xs text-gray-400">Cada <?php echo $cfg['canciones_intervalo'] ?? 3; ?> canciones</p>
                            </div>
                        </div>
                        <a href="<?php echo url('player', ['config' => $cfg['id'] ?? 0]); ?>" class="text-spotify hover:text-spotifyDark transition-colors">
                            <i class="fas fa-play-circle text-xl"></i>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
