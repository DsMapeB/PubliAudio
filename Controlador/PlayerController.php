<?php

class PlayerController {
    private $usuarioId;
    private $spotify;
    private $configModel;

    public function __construct() {
        $this->usuarioId = $_SESSION['usuario_id'] ?? 0;
        $this->spotify = new SpotifyService();
        $this->configModel = new Configuracion();
    }

    public function index() {
        $configId = (int)($_GET['config'] ?? 0);
        $config = $this->configModel->obtenerPorId($configId, $this->usuarioId);

        if (!$config) {
            redirect(url('dashboard', ['error' => 'config_no_encontrada']));
        }

        $spotifyPlaylistId = $config['spotify_playlist_id'];
        $playlist = $this->spotify->obtenerPlaylist($this->usuarioId, $spotifyPlaylistId) ?: [];
        $canciones = $this->spotify->obtenerCancionesPlaylist($this->usuarioId, $spotifyPlaylistId) ?: [];
        $audio = (new Audio())->obtenerPorId($config['audio_id']) ?: [];

        require_once __DIR__ . '/../Vista/layouts/header.php';
        require_once __DIR__ . '/../Vista/partials/navbar.php';
        require_once __DIR__ . '/../Vista/player/index.php';
        require_once __DIR__ . '/../Vista/layouts/footer.php';
    }

    public function play() {
        $configId = (int)($_POST['config_id'] ?? 0);
        $offset = (int)($_POST['offset'] ?? 0);
        $deviceId = sanitize($_POST['device_id'] ?? '');
        $config = $this->configModel->obtenerPorId($configId, $this->usuarioId);

        if (!$config) {
            jsonResponse(['error' => 'Configuración no encontrada'], 404);
        }

        $spotifyPlaylistId = $config['spotify_playlist_id'];

        if ($deviceId) {
            $this->spotify->transferirDispositivo($this->usuarioId, $deviceId);
            usleep(300000);
        }

        $result = $this->spotify->reproducir(
            $this->usuarioId,
            'spotify:playlist:' . $spotifyPlaylistId,
            $offset,
            $deviceId ?: null
        );

        if ($result === false || $result === null) {
            jsonResponse(['error' => 'No se pudo iniciar la reproducción. Verifica que el dispositivo esté activo.'], 200);
        }

        jsonResponse(['success' => true]);
    }

    public function pause() {
        $this->spotify->pausar($this->usuarioId);
        jsonResponse(['success' => true]);
    }

    public function resume() {
        $result = $this->spotify->reanudar($this->usuarioId);
        if ($result === false || $result === null) {
            jsonResponse(['error' => 'No se pudo reanudar'], 200);
        }
        jsonResponse(['success' => true]);
    }

    public function next() {
        $this->spotify->siguiente($this->usuarioId);
        jsonResponse(['success' => true]);
    }

    public function status() {
        $estado = $this->spotify->estadoActual($this->usuarioId);
        $configId = (int)($_GET['config'] ?? 0);

        $response = [
            'is_playing' => $estado['is_playing'] ?? false,
            'item' => $estado['item'] ?? null,
            'progress_ms' => $estado['progress_ms'] ?? 0,
            'device' => $estado['device'] ?? null,
        ];

        if ($configId) {
            $config = $this->configModel->obtenerPorId($configId, $this->usuarioId);
            $response['intervalo'] = $config['canciones_intervalo'] ?? 3;
            $response['audio_id'] = $config['audio_id'] ?? null;
        }

        jsonResponse($response);
    }

    public function devices() {
        $dispositivos = $this->spotify->dispositivos($this->usuarioId);
        jsonResponse(['dispositivos' => $dispositivos]);
    }

    public function transfer() {
        $deviceId = sanitize($_POST['device_id'] ?? '');
        if ($deviceId) {
            $this->spotify->transferirDispositivo($this->usuarioId, $deviceId);
        }
        jsonResponse(['success' => true]);
    }

    public function token() {
        $accessToken = $this->spotify->getAccessToken($this->usuarioId);
        if (!$accessToken) {
            jsonResponse(['error' => 'No se pudo obtener token'], 401);
        }
        jsonResponse(['access_token' => $accessToken]);
    }

    public function canciones() {
        $playlistId = sanitize($_GET['playlist_id'] ?? '');
        $canciones = $this->spotify->obtenerCancionesPlaylist($this->usuarioId, $playlistId);
        jsonResponse(['canciones' => $canciones]);
    }
}
