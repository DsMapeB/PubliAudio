<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-900 via-green-950 to-gray-900">
    <div class="w-full max-w-md p-8">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-spotify/20 mb-6">
                <i class="fab fa-spotify text-5xl text-spotify"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Spotify Audi</h1>
            <p class="text-gray-400">Reproduce playlists con audios personalizados</p>
        </div>

        <div class="bg-white/5 backdrop-blur-xl rounded-2xl p-8 border border-white/10">
            <h2 class="text-xl font-semibold text-white mb-2">Bienvenido</h2>
            <p class="text-gray-400 text-sm mb-8">Conecta tu cuenta de Spotify para empezar</p>

            <a href="<?php echo url('login'); ?>"
               class="flex items-center justify-center gap-3 w-full bg-spotify hover:bg-spotifyDark text-white font-semibold py-3.5 px-6 rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98]">
                <i class="fab fa-spotify text-xl"></i>
                Continuar con Spotify
            </a>

            <p class="text-center text-xs text-gray-500 mt-6">
                Al continuar, aceptas los términos de servicio de Spotify.
            </p>
        </div>

        <div class="flex items-center justify-center gap-2 mt-6">
            <button onclick="toggleDarkMode()" class="text-gray-500 hover:text-white transition-colors text-sm flex items-center gap-2" title="Modo oscuro/claro">
                <i class="fas fa-moon dark:hidden"></i>
                <i class="fas fa-sun hidden dark:inline"></i>
                <span>Modo oscuro</span>
            </button>
        </div>
    </div>
</div>

