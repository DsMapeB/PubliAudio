<?php

session_start();

require_once __DIR__ . '/Funciones/helpers.php';
require_once __DIR__ . '/Modelo/Conexion.php';
require_once __DIR__ . '/Modelo/Usuario.php';
require_once __DIR__ . '/Modelo/Audio.php';
require_once __DIR__ . '/Modelo/Configuracion.php';
require_once __DIR__ . '/Servicio/SpotifyService.php';
require_once __DIR__ . '/Controlador/AuthController.php';
require_once __DIR__ . '/Controlador/DashboardController.php';
require_once __DIR__ . '/Controlador/AudioController.php';
require_once __DIR__ . '/Controlador/PlaylistController.php';
require_once __DIR__ . '/Controlador/PlayerController.php';

$accion = $_GET['accion'] ?? 'dashboard';

$authController = new AuthController();
$dashboardController = new DashboardController();
$audioController = new AudioController();
$playlistController = new PlaylistController();
$playerController = new PlayerController();

if (isLoggedIn()) {
    switch ($accion) {
        case 'logout':
            $authController->logout();
            break;
        case 'callback':
            $authController->callback();
            break;
        case 'spotify_diagnostico':
            $dashboardController->diagnostico();
            break;
        case 'debug_playlists':
            $dashboardController->debugPlaylists();
            break;
        case 'playlists':
            $playlistController->index();
            break;
        case 'playlist_ver':
            $playlistController->ver();
            break;
        case 'playlist_guardar_config':
            $playlistController->guardarConfiguracion();
            break;
        case 'playlist_eliminar_config':
            $playlistController->eliminarConfiguracion();
            break;
        case 'audios':
            $audioController->index();
            break;
        case 'audio_crear':
            $audioController->crear();
            break;
        case 'audio_guardar':
            $audioController->guardar();
            break;
        case 'audio_editar':
            $audioController->editar();
            break;
        case 'audio_actualizar':
            $audioController->actualizar();
            break;
        case 'audio_eliminar':
            $audioController->eliminar();
            break;
        case 'audio_obtener_info':
            $audioController->obtenerInfo();
            break;
        case 'player':
            $playerController->index();
            break;
        case 'player_play':
            $playerController->play();
            break;
        case 'player_resume':
            $playerController->resume();
            break;
        case 'player_pause':
            $playerController->pause();
            break;
        case 'player_next':
            $playerController->next();
            break;
        case 'player_status':
            $playerController->status();
            break;
        case 'player_devices':
            $playerController->devices();
            break;
        case 'player_transfer':
            $playerController->transfer();
            break;
        case 'player_token':
            $playerController->token();
            break;
        case 'player_canciones':
            $playerController->canciones();
            break;
        case 'dashboard':
        default:
            $dashboardController->index();
            break;
    }
} else {
    switch ($accion) {
        case 'login':
            $authController->login();
            break;
        case 'callback':
            $authController->callback();
            break;
        case 'logout':
            $authController->logout();
            break;
        default:
            $authController->showLogin();
            break;
    }
}
