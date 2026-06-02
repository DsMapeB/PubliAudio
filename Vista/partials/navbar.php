<aside class="w-64 bg-white dark:bg-darkCard border-r border-gray-200 dark:border-darkBorder flex-shrink-0 hidden md:flex flex-col">
    <div class="h-16 flex items-center gap-2 px-6 border-b border-gray-200 dark:border-darkBorder">
        <i class="fab fa-spotify text-spotify text-2xl"></i>
        <span class="font-bold text-lg">Spotify Audi</span>
    </div>
    <nav class="flex-1 py-4 px-3 space-y-1 overflow-y-auto">
        <a href="<?php echo url('dashboard'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 <?php echo str_contains($_SERVER['QUERY_STRING'] ?? '', 'dashboard') || $_SERVER['QUERY_STRING'] === '' ? 'bg-spotify/10 text-spotify' : 'text-gray-600 dark:text-darkText hover:bg-gray-100 dark:hover:bg-darkBorder'; ?>">
            <i class="fas fa-th-large w-5 text-center"></i>
            <span>Dashboard</span>
        </a>
        <a href="<?php echo url('playlists'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 <?php echo str_contains($_SERVER['QUERY_STRING'] ?? '', 'playlist') ? 'bg-spotify/10 text-spotify' : 'text-gray-600 dark:text-darkText hover:bg-gray-100 dark:hover:bg-darkBorder'; ?>">
            <i class="fas fa-music w-5 text-center"></i>
            <span>Playlists</span>
        </a>
        <a href="<?php echo url('audios'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 <?php echo str_contains($_SERVER['QUERY_STRING'] ?? '', 'audio') ? 'bg-spotify/10 text-spotify' : 'text-gray-600 dark:text-darkText hover:bg-gray-100 dark:hover:bg-darkBorder'; ?>">
            <i class="fas fa-microphone w-5 text-center"></i>
            <span>Mis Audios</span>
        </a>
    </nav>
    <div class="border-t border-gray-200 dark:border-darkBorder p-4">
        <div class="flex items-center gap-3">
            <img src="<?php echo e($_SESSION['foto'] ?? ''); ?>" alt="" class="w-9 h-9 rounded-full object-cover bg-gray-200" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect fill=%22%23333%22 width=%22100%22 height=%22100%22/><text fill=%22%23fff%22 font-size=%2250%22 x=%2250%22 y=%2265%22 text-anchor=%22middle%22>%3C?php echo substr($_SESSION['nombre'] ?? 'U', 0, 1); %3E</text></svg>'">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate"><?php echo e($_SESSION['nombre'] ?? 'Usuario'); ?></p>
            </div>
            <div class="flex items-center gap-1">
                <button onclick="toggleDarkMode()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-darkBorder transition-colors text-sm" title="Modo oscuro/claro">
                    <i class="fas fa-moon dark:hidden"></i>
                    <i class="fas fa-sun hidden dark:inline"></i>
                </button>
                <a href="<?php echo url('logout'); ?>" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-darkBorder transition-colors text-sm text-gray-400 hover:text-red-500" title="Cerrar sesión">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>
</aside>

<main class="flex-1 overflow-y-auto">
    <div class="p-6">
