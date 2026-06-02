<?php

class SpotifyService {
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $db;

    public function __construct() {
        $this->clientId = config('SPOTIFY_CLIENT_ID');
        $this->clientSecret = config('SPOTIFY_CLIENT_SECRET');
        $this->redirectUri = config('SPOTIFY_REDIRECT_URI');
        $this->db = Conexion::getInstancia();
    }

    public function generarUrlAutorizacion() {
        $state = bin2hex(random_bytes(16));
        $_SESSION['spotify_state'] = $state;

        $verifier = bin2hex(random_bytes(64));
        $_SESSION['spotify_verifier'] = $verifier;

        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'scope' => 'user-read-private user-read-email playlist-read-private playlist-read-collaborative user-modify-playback-state user-read-playback-state user-read-currently-playing streaming',
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
            'code_challenge_method' => 'S256',
            'code_challenge' => $challenge,
        ]);

        return 'https://accounts.spotify.com/authorize?' . $params;
    }

    public function procesarCallback($code, $state) {
        if ($state !== ($_SESSION['spotify_state'] ?? '')) {
            throw new Exception('State inválido. Posible ataque CSRF.');
        }

        $verifier = $_SESSION['spotify_verifier'] ?? '';
        unset($_SESSION['spotify_state'], $_SESSION['spotify_verifier']);

        $tokenData = $this->solicitarToken($code, $verifier);

        if (!$tokenData || empty($tokenData['access_token'])) {
            throw new Exception('No se pudo obtener el token de acceso');
        }

        $perfil = $this->obtenerPerfil($tokenData['access_token']);

        if (!$perfil || empty($perfil['id'])) {
            throw new Exception('No se pudo obtener el perfil del usuario');
        }

        $expiresAt = date('Y-m-d H:i:s', time() + ($tokenData['expires_in'] ?? 3600));

        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->obtenerPorSpotifyId($perfil['id']);

        if ($usuario) {
            $usuarioModel->actualizarTokens(
                $usuario['id'],
                $tokenData['access_token'],
                $tokenData['refresh_token'] ?? $usuario['refresh_token'],
                $expiresAt
            );
            $usuarioModel->actualizar($usuario['id'], [
                'nombre' => $perfil['display_name'] ?? '',
                'email' => $perfil['email'] ?? '',
                'foto' => $perfil['images'][0]['url'] ?? '',
            ]);
            $usuarioId = $usuario['id'];
        } else {
            $usuarioId = $usuarioModel->crear([
                'spotify_id' => $perfil['id'],
                'nombre' => $perfil['display_name'] ?? '',
                'email' => $perfil['email'] ?? null,
                'foto' => $perfil['images'][0]['url'] ?? null,
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'],
                'token_expires_at' => $expiresAt,
            ]);
        }

        if (!$usuarioId) {
            $errorMsg = 'Error al guardar el usuario en la base de datos';
            error_log($errorMsg);
            throw new Exception($errorMsg);
        }

        $_SESSION['usuario_id'] = (int)$usuarioId;
        $_SESSION['spotify_id'] = $perfil['id'];
        $_SESSION['nombre'] = $perfil['display_name'] ?? '';
        $_SESSION['foto'] = $perfil['images'][0]['url'] ?? '';
        $_SESSION['access_token'] = $tokenData['access_token'];
    }

    private function solicitarToken($code, $verifier) {
        $ch = curl_init('https://accounts.spotify.com/api/token');
        $opts = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->redirectUri,
                'client_id' => $this->clientId,
                'code_verifier' => $verifier,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 15,
        ];
        if (config('DEBUG') === 'true') {
            $opts[CURLOPT_SSL_VERIFYPEER] = false;
            $opts[CURLOPT_SSL_VERIFYHOST] = false;
        }
        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('Error al obtener token: ' . $response);
        }

        return json_decode($response, true);
    }

    public function refrescarToken($usuarioId) {
        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->obtenerPorId($usuarioId);

        if (!$usuario || empty($usuario['refresh_token'])) {
            return false;
        }

        $ch = curl_init('https://accounts.spotify.com/api/token');
        $opts = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'refresh_token',
                'refresh_token' => $usuario['refresh_token'],
                'client_id' => $this->clientId,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
            ],
            CURLOPT_TIMEOUT => 15,
        ];
        if (config('DEBUG') === 'true') {
            $opts[CURLOPT_SSL_VERIFYPEER] = false;
            $opts[CURLOPT_SSL_VERIFYHOST] = false;
        }
        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) return false;

        $data = json_decode($response, true);
        $expiresAt = date('Y-m-d H:i:s', time() + $data['expires_in']);

        $usuarioModel->actualizarTokens(
            $usuarioId,
            $data['access_token'],
            $data['refresh_token'] ?? $usuario['refresh_token'],
            $expiresAt
        );

        $_SESSION['access_token'] = $data['access_token'];
        return $data['access_token'];
    }

    public function getAccessToken($usuarioId) {
        $usuario = (new Usuario())->obtenerPorId($usuarioId);
        if (!$usuario) return null;

        if (strtotime($usuario['token_expires_at']) < time() + 60) {
            return $this->refrescarToken($usuarioId);
        }

        return $usuario['access_token'];
    }

    private function apiRequest($method, $endpoint, $token, $body = null) {
        $ch = curl_init('https://api.spotify.com/v1/' . $endpoint);
        $headers = ['Authorization: Bearer ' . $token];

        if ($body) {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $curlOpts = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 15,
        ];

        if (config('DEBUG') === 'true') {
            $curlOpts[CURLOPT_SSL_VERIFYPEER] = false;
            $curlOpts[CURLOPT_SSL_VERIFYHOST] = false;
        }

        curl_setopt_array($ch, $curlOpts);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("Spotify API cURL error: $error");
            return null;
        }

        if ($httpCode >= 400) {
            error_log("Spotify API error ($httpCode): $response");
            return null;
        }

        if ($httpCode === 204 || empty($response)) {
            return ['success' => true];
        }

        return json_decode($response, true);
    }

    public function diagnosticarToken($usuarioId) {
        $resultado = [
            'usuario_id' => $usuarioId,
            'hay_sesion' => isset($_SESSION['usuario_id']),
            'token_en_db' => false,
            'token_en_sesion' => isset($_SESSION['access_token']),
            'token_expirado' => true,
            'hay_refresh_token' => false,
            'me' => null,
            'playlists' => null,
            'errores' => [],
        ];

        $usuario = (new Usuario())->obtenerPorId($usuarioId);
        if ($usuario) {
            $resultado['token_en_db'] = !empty($usuario['access_token']);
            $resultado['token_expirado'] = strtotime($usuario['token_expires_at']) < time();
            $resultado['hay_refresh_token'] = !empty($usuario['refresh_token']);
        } else {
            $resultado['errores'][] = 'Usuario no encontrado en DB';
        }

        $token = $this->getAccessToken($usuarioId);
        if (!$token) {
            $resultado['errores'][] = 'No se pudo obtener token de acceso';
        } else {
            $resultado['me'] = $this->apiRequest('GET', 'me', $token);
            if (!$resultado['me']) {
                $resultado['errores'][] = 'Fallo GET /me - token puede ser inválido';
            }
            $playlistsData = $this->apiRequest('GET', 'me/playlists?limit=50', $token);
            $resultado['playlists'] = $playlistsData;
            if (!$playlistsData) {
                $resultado['errores'][] = 'Fallo GET /me/playlists';
            } else {
                $resultado['total_playlists'] = $playlistsData['total'] ?? 0;
            }
        }

        return $resultado;
    }

    public function obtenerPerfil($token) {
        return $this->apiRequest('GET', 'me', $token);
    }

    public function obtenerPlaylists($usuarioId) {
        $token = $this->getAccessToken($usuarioId);
        if (!$token) return [];
        $data = $this->apiRequest('GET', 'me/playlists?limit=50', $token);
        $items = $data['items'] ?? [];
        // Normalize: Spotify API returns 'items' instead of 'tracks' on playlist objects
        foreach ($items as &$pl) {
            if (!isset($pl['tracks']) && isset($pl['items'])) {
                $pl['tracks'] = $pl['items'];
            }
        }
        unset($pl);
        return $items;
    }

    public function obtenerPlaylist($usuarioId, $playlistId) {
        $token = $this->getAccessToken($usuarioId);
        if (!$token) return null;
        $pl = $this->apiRequest('GET', "playlists/$playlistId", $token);
        if (!$pl) return null;
        if (!isset($pl['tracks']) && isset($pl['items'])) {
            $pl['tracks'] = $pl['items'];
        }
        return $pl;
    }

    public function obtenerCancionesPlaylist($usuarioId, $playlistId) {
        $token = $this->getAccessToken($usuarioId);
        if (!$token) return [];
        $data = $this->apiRequest('GET', "playlists/$playlistId/items?limit=50", $token);
        return $data['items'] ?? [];
    }

    public function transferirDispositivo($usuarioId, $deviceId) {
        $token = $this->getAccessToken($usuarioId);
        if (!$token) return false;
        return $this->apiRequest('PUT', 'me/player', $token, [
            'device_ids' => [$deviceId],
        ]);
    }

    public function reproducir($usuarioId, $contextUri = null, $offset = null, $deviceId = null) {
        $token = $this->getAccessToken($usuarioId);
        if (!$token) return false;
        $endpoint = 'me/player/play';
        if ($deviceId) $endpoint .= '?device_id=' . $deviceId;
        $body = [];
        if ($contextUri) $body['context_uri'] = $contextUri;
        if ($offset !== null) $body['offset'] = ['position' => $offset];
        return $this->apiRequest('PUT', $endpoint, $token, $body);
    }

    public function reanudar($usuarioId) {
        $token = $this->getAccessToken($usuarioId);
        if (!$token) return false;
        return $this->apiRequest('PUT', 'me/player/play', $token);
    }

    public function pausar($usuarioId) {
        $token = $this->getAccessToken($usuarioId);
        if (!$token) return false;
        return $this->apiRequest('PUT', 'me/player/pause', $token);
    }

    public function siguiente($usuarioId) {
        $token = $this->getAccessToken($usuarioId);
        if (!$token) return false;
        return $this->apiRequest('POST', 'me/player/next', $token);
    }

    public function estadoActual($usuarioId) {
        $token = $this->getAccessToken($usuarioId);
        if (!$token) return null;
        return $this->apiRequest('GET', 'me/player', $token);
    }

    public function dispositivos($usuarioId) {
        $token = $this->getAccessToken($usuarioId);
        if (!$token) return [];
        $data = $this->apiRequest('GET', 'me/player/devices', $token);
        return $data['devices'] ?? [];
    }

    public function buscar($usuarioId, $query, $tipo = 'track') {
        $token = $this->getAccessToken($usuarioId);
        if (!$token) return [];
        $data = $this->apiRequest('GET', 'search?q=' . urlencode($query) . '&type=' . $tipo . '&limit=10', $token);
        return $data;
    }
}
