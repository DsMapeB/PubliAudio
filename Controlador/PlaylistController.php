<?php

class PlaylistController {
    private $usuarioId;
    private $spotify;
    private $configModel;

    public function __construct() {
        $this->usuarioId = $_SESSION['usuario_id'] ?? 0;
        $this->spotify = new SpotifyService();
        $this->configModel = new Configuracion();
    }

    public function index() {
        $playlists = $this->spotify->obtenerPlaylists($this->usuarioId);
        $audios = (new Audio())->listarPorUsuario($this->usuarioId);

        require_once __DIR__ . '/../Vista/layouts/header.php';
        require_once __DIR__ . '/../Vista/partials/navbar.php';
        require_once __DIR__ . '/../Vista/playlists/index.php';
        require_once __DIR__ . '/../Vista/layouts/footer.php';
    }

    public function ver() {
        $playlistId = sanitize($_GET['id'] ?? '');
        $playlist = $this->spotify->obtenerPlaylist($this->usuarioId, $playlistId) ?: [];
        $canciones = $this->spotify->obtenerCancionesPlaylist($this->usuarioId, $playlistId) ?: [];
        $audios = (new Audio())->listarPorUsuario($this->usuarioId) ?: [];
        $configActual = $this->configModel->obtenerPorId((int)($_GET['config'] ?? 0), $this->usuarioId);

        require_once __DIR__ . '/../Vista/layouts/header.php';
        require_once __DIR__ . '/../Vista/partials/navbar.php';
        require_once __DIR__ . '/../Vista/playlists/ver.php';
        require_once __DIR__ . '/../Vista/layouts/footer.php';
    }

    public function guardarConfiguracion() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('playlists'));
        }

        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            redirect(url('playlists', ['error' => 'csrf']));
        }

        $spotifyPlaylistId = sanitize($_POST['playlist_id'] ?? '');
        $playlistNombre = sanitize($_POST['playlist_nombre'] ?? '');
        $playlistImagen = sanitize($_POST['playlist_imagen'] ?? '');
        $audioId = (int)($_POST['audio_id'] ?? 0);
        $intervalo = (int)($_POST['canciones_intervalo'] ?? 3);

        if (empty($spotifyPlaylistId) || $audioId === 0) {
            redirect(url('playlists', ['error' => 'campos_vacios']));
        }

        $intervalosValidos = [1, 2, 3, 5, 10];
        if (!in_array($intervalo, $intervalosValidos)) {
            $intervalo = 3;
        }

        $db = Conexion::getInstancia();
        $db->ejecutar(
            "INSERT IGNORE INTO playlists_favoritas (usuario_id, spotify_playlist_id, nombre_playlist, imagen)
             VALUES (?, ?, ?, ?)",
            [$this->usuarioId, $spotifyPlaylistId, $playlistNombre, $playlistImagen]
        );
        $playlistFav = $db->consultarUno(
            "SELECT id FROM playlists_favoritas WHERE usuario_id = ? AND spotify_playlist_id = ?",
            [$this->usuarioId, $spotifyPlaylistId]
        );
        $playlistFavId = $playlistFav['id'] ?? null;

        if (!$playlistFavId) {
            redirect(url('playlists', ['error' => 'error_playlist']));
        }

        $this->configModel->crear([
            'usuario_id' => $this->usuarioId,
            'playlist_id' => $playlistFavId,
            'audio_id' => $audioId,
            'canciones_intervalo' => $intervalo,
        ]);

        redirect(url('dashboard', ['success' => 'config_creada']));
    }

    public function eliminarConfiguracion() {
        $id = (int)($_GET['id'] ?? 0);
        $this->configModel->eliminar($id, $this->usuarioId);
        jsonResponse(['success' => true]);
    }
}
