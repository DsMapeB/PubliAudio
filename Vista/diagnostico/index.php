<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold">Diagnóstico Spotify</h1>
            <p class="text-gray-500 dark:text-darkText mt-1">Estado de la conexión con Spotify</p>
        </div>
        <a href="<?php echo url('dashboard'); ?>" class="text-sm text-spotify hover:underline">&larr; Volver al dashboard</a>
    </div>

    <?php if (empty($diagnostico)): ?>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-6 text-center">
            <p class="text-yellow-700 dark:text-yellow-300">No hay datos de diagnóstico disponibles</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <div class="bg-white dark:bg-darkCard rounded-xl border border-gray-200 dark:border-darkBorder overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-darkBorder bg-gray-50 dark:bg-darkBg">
                    <h2 class="font-semibold text-sm uppercase tracking-wider text-gray-500 dark:text-darkText">Estado General</h2>
                </div>
                <div class="p-6 grid grid-cols-2 gap-4">
                    <div class="flex items-center gap-3">
                        <span class="w-2 h-2 rounded-full <?php echo $diagnostico['hay_sesion'] ? 'bg-green-500' : 'bg-red-500'; ?>"></span>
                        <span class="text-sm">Sesión activa</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="w-2 h-2 rounded-full <?php echo $diagnostico['token_en_db'] ? 'bg-green-500' : 'bg-red-500'; ?>"></span>
                        <span class="text-sm">Token en DB</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="w-2 h-2 rounded-full <?php echo $diagnostico['token_en_sesion'] ? 'bg-green-500' : 'bg-red-500'; ?>"></span>
                        <span class="text-sm">Token en sesión</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="w-2 h-2 rounded-full <?php echo !$diagnostico['token_expirado'] ? 'bg-green-500' : 'bg-red-500'; ?>"></span>
                        <span class="text-sm">Token vigente</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="w-2 h-2 rounded-full <?php echo $diagnostico['hay_refresh_token'] ? 'bg-green-500' : 'bg-red-500'; ?>"></span>
                        <span class="text-sm">Refresh token disponible</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="w-2 h-2 rounded-full <?php echo $diagnostico['me'] ? 'bg-green-500' : 'bg-red-500'; ?>"></span>
                        <span class="text-sm">API /me responde</span>
                    </div>
                </div>
            </div>

            <?php if (!empty($diagnostico['errores'])): ?>
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-6">
                <h3 class="font-semibold text-red-700 dark:text-red-300 mb-3">Errores detectados</h3>
                <ul class="space-y-2">
                    <?php foreach ($diagnostico['errores'] as $error): ?>
                    <li class="text-sm text-red-600 dark:text-red-400 flex items-start gap-2">
                        <i class="fas fa-exclamation-circle mt-0.5"></i>
                        <span><?php echo e($error); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if ($diagnostico['me']): ?>
            <div class="bg-white dark:bg-darkCard rounded-xl border border-gray-200 dark:border-darkBorder overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-darkBorder bg-gray-50 dark:bg-darkBg">
                    <h2 class="font-semibold text-sm uppercase tracking-wider text-gray-500 dark:text-darkText">Perfil de Spotify</h2>
                </div>
                <div class="p-6">
                    <div class="flex items-center gap-4">
                        <img src="<?php echo e($diagnostico['me']['images'][0]['url'] ?? ''); ?>" alt="" class="w-16 h-16 rounded-full object-cover bg-gray-100">
                        <div>
                            <p class="font-semibold"><?php echo e($diagnostico['me']['display_name'] ?? 'N/A'); ?></p>
                            <p class="text-sm text-gray-500 dark:text-darkText"><?php echo e($diagnostico['me']['email'] ?? 'Sin email'); ?></p>
                            <p class="text-xs text-gray-400">ID: <?php echo e($diagnostico['me']['id'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($diagnostico['total_playlists'])): ?>
            <div class="bg-white dark:bg-darkCard rounded-xl border border-gray-200 dark:border-darkBorder overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-darkBorder bg-gray-50 dark:bg-darkBg">
                    <h2 class="font-semibold text-sm uppercase tracking-wider text-gray-500 dark:text-darkText">Playlists</h2>
                </div>
                <div class="p-6">
                    <p class="text-lg font-bold"><?php echo $diagnostico['total_playlists']; ?> playlists encontradas</p>
                    <?php if (!empty($diagnostico['playlists']['items'])): ?>
                    <div class="mt-4 space-y-2">
                        <?php foreach (array_slice($diagnostico['playlists']['items'], 0, 10) as $pl): ?>
                        <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-darkBorder">
                            <img src="<?php echo e($pl['images'][0]['url'] ?? ''); ?>" alt="" class="w-10 h-10 rounded object-cover bg-gray-100">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate"><?php echo e($pl['name']); ?></p>
                                <p class="text-xs text-gray-400"><?php echo e($pl['tracks']['total']); ?> canciones</p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>