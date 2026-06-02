<?php

class DashboardController {
    private $usuarioId;

    public function __construct() {
        $this->usuarioId = $_SESSION['usuario_id'] ?? 0;
    }

    public function index() {
        $audioModel = new Audio();
        $configModel = new Configuracion();
        $spotify = new SpotifyService();

        $totalAudios = $audioModel->totalPorUsuario($this->usuarioId);
        $totalPlaylists = $configModel->totalPlaylistsUnicas($this->usuarioId);
        $configuracionesActivas = $configModel->activasPorUsuario($this->usuarioId);
        $configuraciones = $configModel->listarPorUsuario($this->usuarioId);
        $audios = $audioModel->listarPorUsuario($this->usuarioId);

        $playlists = [];
        try {
            $playlists = $spotify->obtenerPlaylists($this->usuarioId);
        } catch (Exception $e) {
            error_log("Error obteniendo playlists: " . $e->getMessage());
        }

        require_once __DIR__ . '/../Vista/layouts/header.php';
        require_once __DIR__ . '/../Vista/partials/navbar.php';
        require_once __DIR__ . '/../Vista/dashboard/index.php';
        require_once __DIR__ . '/../Vista/layouts/footer.php';
    }

    public function diagnostico() {
        $spotify = new SpotifyService();
        $diagnostico = $spotify->diagnosticarToken($this->usuarioId);

        require_once __DIR__ . '/../Vista/layouts/header.php';
        require_once __DIR__ . '/../Vista/partials/navbar.php';
        require_once __DIR__ . '/../Vista/diagnostico/index.php';
        require_once __DIR__ . '/../Vista/layouts/footer.php';
    }

    public function debugPlaylists() {
        require_once __DIR__ . '/../Vista/layouts/header.php';
        require_once __DIR__ . '/../Vista/partials/navbar.php';
        require_once __DIR__ . '/../Vista/diagnostico/playlists_raw.php';
        require_once __DIR__ . '/../Vista/layouts/footer.php';
    }
}
