<div class="mb-8">
    <h1 class="text-2xl font-bold">Tus Playlists</h1>
    <p class="text-gray-500 dark:text-darkText mt-1">Selecciona una playlist para configurar la inserción de audios</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    <?php if (empty($playlists)): ?>
    <div class="col-span-full text-center py-16">
        <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-darkBorder flex items-center justify-center">
            <i class="fab fa-spotify text-3xl text-gray-300"></i>
        </div>
        <h3 class="text-lg font-semibold mb-2">No se encontraron playlists</h3>
        <p class="text-gray-500 dark:text-darkText">Asegúrate de tener playlists en tu cuenta de Spotify.</p>
    </div>
    <?php else: ?>
    <?php foreach ($playlists as $pl): ?>
    <a href="<?php echo url('playlist_ver', ['id' => $pl['id'] ?? '']); ?>"
       class="group bg-white dark:bg-darkCard rounded-xl border border-gray-200 dark:border-darkBorder overflow-hidden hover:shadow-lg hover:border-spotify/30 transition-all duration-300 transform hover:-translate-y-1">
        <div class="aspect-square overflow-hidden bg-gray-100 dark:bg-darkBorder">
            <?php if (!empty($pl['images'][0]['url'])): ?>
            <img src="<?php echo e($pl['images'][0]['url']); ?>" alt="<?php echo e($pl['name'] ?? ''); ?>"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
            <?php else: ?>
            <div class="w-full h-full flex items-center justify-center">
                <i class="fas fa-music text-6xl text-gray-300"></i>
            </div>
            <?php endif; ?>
        </div>
        <div class="p-4">
            <h3 class="font-semibold truncate group-hover:text-spotify transition-colors"><?php echo e($pl['name'] ?? ''); ?></h3>
            <div class="flex items-center justify-between mt-1">
                <span class="text-sm text-gray-400"><?php echo e(($pl['tracks']['total'] ?? 0)); ?> canciones</span>
                <span class="text-xs text-gray-400"><?php echo e($pl['owner']['display_name'] ?? ''); ?></span>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
