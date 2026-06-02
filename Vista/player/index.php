<div class="mb-8">
    <a href="<?php echo url('dashboard'); ?>" class="text-sm text-gray-500 hover:text-spotify transition-colors flex items-center gap-2 mb-4">
        <i class="fas fa-arrow-left"></i>
        Volver al dashboard
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

    <!-- Panel Izquierdo: Info de playlist -->
    <div class="lg:col-span-3">
        <div class="bg-white dark:bg-darkCard rounded-xl border border-gray-200 dark:border-darkBorder p-6">
            <h3 class="font-semibold text-sm text-gray-500 uppercase tracking-wider mb-4">Playlist</h3>
            <?php if ($playlist): ?>
            <div class="text-center">
                <img src="<?php echo e($playlist['images'][0]['url'] ?? ''); ?>"
                     alt="" class="w-full aspect-square rounded-xl object-cover mb-4 shadow-lg"
                     onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 200 200%22><rect fill=%22%23333%22 width=%22200%22 height=%22200%22/><text fill=%22%23999%22 font-size=%2280%22 x=%2250%25%22 y=%2255%25%22 text-anchor=%22middle%22>♪</text></svg>'">
                <h4 class="font-semibold truncate"><?php echo e($playlist['name'] ?? ''); ?></h4>
                <p class="text-sm text-gray-400 mt-1"><?php echo e($playlist['owner']['display_name'] ?? ''); ?></p>
                <p class="text-xs text-gray-400 mt-2"><?php echo count($canciones); ?> canciones</p>
            </div>
            <?php endif; ?>

            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-darkBorder">
                <h3 class="font-semibold text-sm text-gray-500 uppercase tracking-wider mb-3">Configuración</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                    <span class="text-gray-400">Audio:</span>
                    <span class="font-medium"><?php echo e($config['audio_nombre'] ?? ''); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Intervalo:</span>
                    <span class="font-medium">Cada <?php echo $config['canciones_intervalo'] ?? 3; ?> canciones</span>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-darkBorder">
                <h3 class="font-semibold text-sm text-gray-500 uppercase tracking-wider mb-3">Dispositivo</h3>
                <div id="deviceInfo" class="flex items-center gap-2 text-sm text-gray-400">
                    <div class="w-4 h-4 rounded-full bg-gray-300 animate-pulse" id="deviceIndicator"></div>
                    <span id="deviceName">Inicializando...</span>
                </div>
                <button onclick="mostrarSelectorDispositivos()" id="btnCambiarDispositivo"
                        class="hidden mt-2 text-xs text-spotify hover:underline">
                    <i class="fas fa-exchange-alt mr-1"></i>Cambiar dispositivo
                </button>
                <div id="deviceList" class="hidden mt-2 space-y-1"></div>
            </div>

            <!-- Debug info -->
            <div id="debugInfo" class="mt-4 pt-4 border-t border-gray-200 dark:border-darkBorder">
                <div class="text-xs font-mono text-gray-400">
                    <span class="text-spotify font-semibold">DEBUG</span>
                    <br>SDK: <span id="debugSdkStatus" class="text-yellow-400">inicializando...</span>
                </div>
            </div>

            <!-- Event Log -->
            <div id="eventLogPanel" class="mt-4 pt-4 border-t border-gray-200 dark:border-darkBorder">
                <div class="text-xs font-mono">
                    <span class="text-purple-400 font-semibold">EVENT LOG</span>
                    <div id="eventLog" class="mt-2 space-y-0.5 max-h-48 overflow-y-auto"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel Central: Reproductor -->
    <div class="lg:col-span-6">
        <div class="bg-white dark:bg-darkCard rounded-xl border border-gray-200 dark:border-darkBorder p-8 text-center">
            <div id="playerEmptyState" class="py-12">
                <div class="w-32 h-32 mx-auto mb-6 rounded-full bg-gray-100 dark:bg-darkBorder flex items-center justify-center">
                    <i class="fas fa-play-circle text-5xl text-gray-300"></i>
                </div>
                <h2 class="text-xl font-bold mb-2">Listo para reproducir</h2>
                <p class="text-gray-500 dark:text-darkText mb-8 max-w-md mx-auto">
                    Presiona iniciar para comenzar la reproducción. Se intercalará tu audio personalizado cada <?php echo $config['canciones_intervalo']; ?> canciones.
                </p>
                <button onclick="iniciarReproduccion()" id="btnIniciar"
                        class="bg-spotify hover:bg-spotifyDark text-white font-semibold py-4 px-10 rounded-full text-lg transition-all duration-300 transform hover:scale-105 active:scale-95 inline-flex items-center gap-3 shadow-lg shadow-spotify/25">
                    <i class="fas fa-play"></i>
                    Iniciar reproducción
                </button>
            </div>

            <div id="playerContent" class="hidden">
                <div class="mb-6">
                    <img id="currentTrackImage" src="" alt=""
                         class="w-48 h-48 mx-auto rounded-2xl object-cover shadow-xl mb-6"
                         onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 200 200%22><rect fill=%22%23333%22 width=%22200%22 height=%22200%22/><text fill=%22%23999%22 font-size=%2280%22 x=%2250%25%22 y=%2255%25%22 text-anchor=%22middle%22>♪</text></svg>'">
                    <h3 id="currentTrackName" class="text-xl font-bold">Canción</h3>
                    <p id="currentTrackArtist" class="text-gray-400 mt-1">Artista</p>
                </div>

                <div class="max-w-md mx-auto mb-6">
                    <div class="relative pt-1">
                        <div class="w-full bg-gray-200 dark:bg-darkBorder rounded-full h-1.5">
                            <div id="progressBar" class="bg-spotify h-1.5 rounded-full transition-all duration-1000" style="width: 0%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-400 mt-2">
                            <span id="currentTime">0:00</span>
                            <span id="totalTime">0:00</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-center gap-6">
                    <button onclick="anteriorCancion()" class="p-3 rounded-full hover:bg-gray-100 dark:hover:bg-darkBorder transition-colors text-gray-400 hover:text-white">
                        <i class="fas fa-step-backward text-xl"></i>
                    </button>
                    <button onclick="togglePlayPause()" id="btnPlayPause"
                            class="w-16 h-16 rounded-full bg-spotify hover:bg-spotifyDark text-white flex items-center justify-center transition-all duration-300 transform hover:scale-105 active:scale-95 shadow-lg shadow-spotify/25">
                        <i id="playPauseIcon" class="fas fa-pause text-2xl"></i>
                    </button>
                    <button onclick="siguienteCancion()" class="p-3 rounded-full hover:bg-gray-100 dark:hover:bg-darkBorder transition-colors text-gray-400 hover:text-white">
                        <i class="fas fa-step-forward text-xl"></i>
                    </button>
                </div>

                <div class="mt-6 flex items-center justify-center gap-3 text-sm">
                    <span class="px-3 py-1 rounded-full bg-spotify/10 text-spotify font-medium" id="trackCounter">
                        Canción <span id="cancionActualNum">0</span>
                    </span>
                    <span class="px-3 py-1 rounded-full bg-purple-500/10 text-purple-500 font-medium" id="nextAudioIndicator">
                        <i class="fas fa-headphones mr-1"></i>
                        Próximo audio en <span id="cancionesRestantes"><?php echo $config['canciones_intervalo']; ?></span>
                    </span>
                </div>
            </div>

            <!-- Audio personalizado overlay -->
            <div id="customAudioOverlay" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-50">
                <div class="text-center">
                    <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-spotify/20 flex items-center justify-center animate-pulse">
                        <i class="fas fa-headphones text-5xl text-spotify"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2">Audio personalizado</h3>
                    <p id="customAudioName" class="text-gray-300 mb-4"><?php echo e($config['audio_nombre']); ?></p>
                    <div class="w-64 mx-auto">
                        <div class="bg-white/10 rounded-full h-1">
                            <div id="customAudioProgress" class="bg-spotify h-1 rounded-full" style="width: 0%"></div>
                        </div>
                    </div>
                    <p class="text-gray-400 text-sm mt-3">Reproduciendo audio personalizado...</p>
                </div>
            </div>
        </div>

        <!-- Lista de canciones -->
        <div class="bg-white dark:bg-darkCard rounded-xl border border-gray-200 dark:border-darkBorder mt-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-darkBorder">
                <h3 class="font-semibold">Lista de reproducción</h3>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-darkBorder max-h-80 overflow-y-auto">
                <?php foreach ($canciones as $i => $item): ?>
                <?php $track = $item['item'] ?? []; ?>
                <div class="flex items-center gap-3 px-6 py-2.5 hover:bg-gray-50 dark:hover:bg-darkBorder/50 transition-colors track-item" data-track-index="<?php echo $i; ?>" data-track-id="<?php echo e($track['id'] ?? ''); ?>">
                    <span class="text-xs text-gray-400 w-5"><?php echo $i + 1; ?></span>
                    <img src="<?php echo e($track['album']['images'][2]['url'] ?? $track['album']['images'][0]['url'] ?? ''); ?>" alt="" class="w-8 h-8 rounded object-cover">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm truncate"><?php echo e($track['name'] ?? ''); ?></p>
                    </div>
                    <span class="text-xs text-gray-400"><?php echo isset($track['duration_ms']) ? gmdate('i:s', (int)($track['duration_ms'] / 1000)) : ''; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Panel Derecho: Audio personalizado info -->
    <div class="lg:col-span-3">
        <div class="bg-white dark:bg-darkCard rounded-xl border border-gray-200 dark:border-darkBorder p-6">
            <h3 class="font-semibold text-sm text-gray-500 uppercase tracking-wider mb-4">Audio personalizado</h3>
            <?php if ($audio): ?>
            <div class="text-center">
                <div class="w-24 h-24 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-spotify/20 to-purple-500/20 flex items-center justify-center">
                    <i class="fas fa-headphones text-4xl text-spotify"></i>
                </div>
                <h4 class="font-semibold"><?php echo e($audio['nombre']); ?></h4>
                <p class="text-sm text-gray-400 mt-1"><?php echo tiempoFormateado($audio['duracion']); ?></p>
                <button onclick="document.getElementById('previewCustomAudio').play(); this.style.display='none'; document.getElementById('stopPreviewBtn').style.display='inline-flex';"
                        class="mt-4 text-sm text-spotify hover:underline flex items-center justify-center gap-2 mx-auto">
                    <i class="fas fa-play"></i>
                    Vista previa
                </button>
                <button id="stopPreviewBtn" onclick="document.getElementById('previewCustomAudio').pause(); document.getElementById('previewCustomAudio').currentTime=0; this.style.display='none'; document.querySelector('[onclick*=\"previewCustomAudio\"]').style.display='inline-flex';"
                        class="mt-4 text-sm text-red-500 hover:underline hidden items-center justify-center gap-2 mx-auto">
                    <i class="fas fa-stop"></i>
                    Detener
                </button>
                <audio id="previewCustomAudio" src="uploads/audios/<?php echo e($audio['archivo']); ?>" class="hidden"></audio>
            </div>
            <?php endif; ?>

            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-darkBorder" id="nextInsertionPanel">
                <h3 class="font-semibold text-sm text-gray-500 uppercase tracking-wider mb-3">Próxima inserción</h3>
                <div class="bg-gray-50 dark:bg-darkBg rounded-lg p-4 text-center">
                    <div class="text-3xl font-bold text-spotify" id="nextInsertionCount"><?php echo $config['canciones_intervalo']; ?></div>
                    <p class="text-sm text-gray-400 mt-1">canciones restantes</p>
                </div>
            </div>

            <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <div class="flex items-start gap-2 text-xs text-blue-700 dark:text-blue-300">
                    <i class="fas fa-info-circle mt-0.5"></i>
                    <p>Después de cada canción, el contador se incrementa. Cuando alcanza el intervalo configurado, se reproduce tu audio personalizado.</p>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://sdk.scdn.co/spotify-player.js"></script>
<script src="public/js/player.js"></script>
<script>
const CONFIG = {
    configId: <?php echo $config['id']; ?>,
    playlistId: '<?php echo e($config['playlist_id']); ?>',
    audioId: <?php echo $config['audio_id']; ?>,
    intervalo: <?php echo $config['canciones_intervalo']; ?>,
    audioUrl: 'uploads/audios/<?php echo e($audio['archivo'] ?? ''); ?>',
    totalCanciones: <?php echo count($canciones); ?>,
    csrfToken: '<?php echo csrf_token(); ?>',
    spotifyPlaylistId: '<?php echo e($spotifyPlaylistId); ?>'
};
</script>
