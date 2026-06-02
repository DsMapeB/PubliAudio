<div class="max-w-2xl mx-auto">
    <div class="mb-8">
        <a href="<?php echo url('audios'); ?>" class="text-sm text-gray-500 hover:text-spotify transition-colors flex items-center gap-2 mb-4">
            <i class="fas fa-arrow-left"></i>
            Volver a mis audios
        </a>
        <h1 class="text-2xl font-bold">Subir Audio</h1>
        <p class="text-gray-500 dark:text-darkText mt-1">Sube un archivo MP3 para intercalar en tus playlists</p>
    </div>

    <div class="bg-white dark:bg-darkCard rounded-xl border border-gray-200 dark:border-darkBorder p-8">
        <form action="<?php echo url('audio_guardar'); ?>" method="POST" enctype="multipart/form-data" id="audioForm">
            <?php echo csrf_field(); ?>

            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Nombre del audio</label>
                <input type="text" name="nombre" required
                       class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-darkBorder bg-white dark:bg-darkBg focus:ring-2 focus:ring-spotify focus:border-transparent outline-none transition-all"
                       placeholder="Ej: Intro, despedida, mensaje promocional...">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Archivo MP3</label>
                <div class="border-2 border-dashed border-gray-300 dark:border-darkBorder rounded-xl p-8 text-center hover:border-spotify transition-colors cursor-pointer" onclick="document.getElementById('archivo').click()" id="dropZone">
                    <div class="space-y-3">
                        <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-darkBorder flex items-center justify-center">
                            <i class="fas fa-cloud-upload-alt text-2xl text-gray-400"></i>
                        </div>
                        <div>
                            <p class="font-medium">Haz clic para seleccionar o arrastra un archivo</p>
                            <p class="text-sm text-gray-400 mt-1">MP3, máximo <?php echo config('MAX_AUDIO_SIZE', 10); ?>MB</p>
                        </div>
                        <p id="fileName" class="text-sm text-spotify hidden"></p>
                    </div>
                </div>
                <input type="file" name="archivo" id="archivo" accept=".mp3,audio/mpeg" required class="hidden" onchange="handleFileSelect(this)">
            </div>

            <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <div class="flex items-start gap-3">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <div class="text-sm text-blue-700 dark:text-blue-300">
                        <p class="font-medium mb-1">Requisitos:</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Formato MP3 solamente</li>
                            <li>Tamaño máximo: <?php echo config('MAX_AUDIO_SIZE', 10); ?>MB</li>
                            <li>El nombre debe ser único por usuario</li>
                        </ul>
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full bg-spotify hover:bg-spotifyDark text-white font-semibold py-3 px-6 rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2" id="submitBtn">
                <i class="fas fa-upload"></i>
                Subir Audio
            </button>
        </form>
    </div>
</div>

<script>
function handleFileSelect(input) {
    const file = input.files[0];
    if (file) {
        const maxSize = <?php echo maxAudioSize(); ?>;
        if (file.size > maxSize) {
            mostrarError('Error', 'El archivo excede el tamaño máximo permitido');
            input.value = '';
            return;
        }
        if (file.type !== 'audio/mpeg' && !file.name.endsWith('.mp3')) {
            mostrarError('Error', 'Solo se permiten archivos MP3');
            input.value = '';
            return;
        }
        document.getElementById('fileName').textContent = '✓ ' + file.name + ' (' + (file.size / 1024 / 1024).toFixed(1) + ' MB)';
        document.getElementById('fileName').classList.remove('hidden');
        document.getElementById('dropZone').classList.add('border-spotify', 'bg-spotify/5');
    }
}

document.getElementById('audioForm').addEventListener('submit', function(e) {
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subiendo...';
});
</script>
