<pre style="background:#1e1e1e;color:#d4d4d4;padding:20px;font-size:13px;line-height:1.5;overflow:auto;max-height:100vh;">
<?php
echo "=== DIAGNÓSTICO: Playlists desde Spotify API ===\n\n";

echo "Token en sesión: " . (isset($_SESSION['access_token']) ? 'SÍ' : 'NO') . "\n";
echo "Usuario ID en sesión: " . ($_SESSION['usuario_id'] ?? 'NO HAY') . "\n";
echo "Spotify ID en sesión: " . ($_SESSION['spotify_id'] ?? 'NO HAY') . "\n\n";

echo "=== Token en DB ===\n";
$usuarioModel = new Usuario();
$usuario = $usuarioModel->obtenerPorId($_SESSION['usuario_id'] ?? 0);
if ($usuario) {
    echo "Usuario encontrado en DB:\n";
    echo "  id: " . ($usuario['id'] ?? 'N/A') . "\n";
    echo "  spotify_id: " . ($usuario['spotify_id'] ?? 'N/A') . "\n";
    echo "  nombre: " . ($usuario['nombre'] ?? 'N/A') . "\n";
    echo "  access_token: " . (substr($usuario['access_token'] ?? '', 0, 20)) . "...\n";
    echo "  refresh_token: " . (empty($usuario['refresh_token']) ? 'VACÍO' : 'PRESENTE') . "\n";
    echo "  token_expires_at: " . ($usuario['token_expires_at'] ?? 'NULL') . "\n";
    echo "  expirado: " . (strtotime($usuario['token_expires_at'] ?? 'now') < time() ? 'SÍ' : 'NO') . "\n\n";
} else {
    echo "USUARIO NO ENCONTRADO EN DB\n\n";
}

echo "=== Llamando a API de Spotify: /v1/me/playlists?limit=50 ===\n\n";

$spotify = new SpotifyService();
$token = $spotify->getAccessToken($_SESSION['usuario_id'] ?? 0);
if (!$token) {
    echo "ERROR: No se pudo obtener token de acceso\n";
    exit;
}

echo "Token usado: " . substr($token, 0, 30) . "...\n\n";

// Make the API call directly via cURL
$ch = curl_init('https://api.spotify.com/v1/me/playlists?limit=50');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status: " . $httpCode . "\n";
echo "cURL Error: " . ($error ?: 'ninguno') . "\n";
echo "Response size: " . strlen($response) . " bytes\n\n";

$data = json_decode($response, true);
if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON decode error: " . json_last_error_msg() . "\n";
    echo "Raw response:\n" . htmlspecialchars(substr($response, 0, 2000)) . "\n";
    exit;
}

echo "=== RESPUESTA COMPLETA (estructura) ===\n";
echo "Total playlists reportadas: " . ($data['total'] ?? 'N/A') . "\n";
echo "Items count: " . count($data['items'] ?? []) . "\n\n";

if (empty($data['items'])) {
    echo "NO HAY PLAYSLISTS EN LA RESPUESTA\n";
    if (isset($data['error'])) {
        echo "Error de API: " . json_encode($data['error'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
    exit;
}

echo "=== PRIMERAS 5 PLAYSLISTS: DETALLE COMPLETO ===\n\n";
$limit = min(5, count($data['items']));
for ($i = 0; $i < $limit; $i++) {
    $pl = $data['items'][$i];
    echo "--- PLAYLIST #" . ($i + 1) . " ---\n";
    echo "id:           " . json_encode($pl['id'] ?? null) . "\n";
    echo "name:         " . json_encode($pl['name'] ?? null, JSON_UNESCAPED_UNICODE) . "\n";
    echo "public:       " . json_encode($pl['public'] ?? null) . "\n";
    echo "collaborative: " . json_encode($pl['collaborative'] ?? null) . "\n";
    echo "owner.id:     " . json_encode($pl['owner']['id'] ?? null) . "\n";
    echo "owner.name:   " . json_encode($pl['owner']['display_name'] ?? null, JSON_UNESCAPED_UNICODE) . "\n";

    // TRACKS - the critical field
    echo "\n>>> tracks FIELD <<<\n";
    echo "tracks existe como clave: " . (array_key_exists('tracks', $pl) ? 'SÍ' : 'NO') . "\n";
    echo "tracks type: " . gettype($pl['tracks'] ?? 'KEY_NOT_FOUND') . "\n";
    if (isset($pl['tracks'])) {
        echo "tracks value: " . json_encode($pl['tracks'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        echo "tracks.total: " . json_encode($pl['tracks']['total'] ?? null) . "\n";
        echo "tracks.total type: " . gettype($pl['tracks']['total'] ?? 'NOT_SET') . "\n";
    } else {
        echo "tracks IS NULL OR NOT SET\n";
        // Check if tracks is explicitly null
        echo "array_key_exists tracks: " . (array_key_exists('tracks', $pl) ? 'SÍ (pero valor es null)' : 'NO') . "\n";
    }

    echo "\nOtros campos presentes:\n";
    $keys = array_keys($pl);
    echo "  keys: " . implode(', ', $keys) . "\n";

    echo "\nFull JSON:\n";
    echo json_encode($pl, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    echo str_repeat("=", 60) . "\n\n";
}

echo "=== FIN DIAGNÓSTICO ===\n";
?>
</pre>