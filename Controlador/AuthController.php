<?php

class AuthController {
    private $spotify;

    public function __construct() {
        $this->spotify = new SpotifyService();
    }

    public function login() {
        $url = $this->spotify->generarUrlAutorizacion();
        redirect($url);
    }

    public function callback() {
        try {
            $code = sanitize($_GET['code'] ?? '');
            $state = sanitize($_GET['state'] ?? '');
            $this->spotify->procesarCallback($code, $state);
            redirect(url('dashboard'));
        } catch (Exception $e) {
            error_log("Error en callback Spotify (" . get_class($e) . "): " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            redirect('index.php?error=spotify_error');
        }
    }

    public function logout() {
        $_SESSION = [];
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
        redirect('index.php?success=logout');
    }

    public function showLogin() {
        require_once __DIR__ . '/../Vista/layouts/header.php';
        require_once __DIR__ . '/../Vista/auth/login.php';
        require_once __DIR__ . '/../Vista/layouts/footer.php';
    }
}
