<?php

function config($key, $default = null) {
    static $env = [];
    if (empty($env)) {
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#')) continue;
                if (str_contains($line, '=')) {
                    [$k, $v] = explode('=', $line, 2);
                    $env[trim($k)] = trim($v);
                }
            }
        }
    }
    return $env[$key] ?? $default;
}

function env($key, $default = null) {
    static $vars = [];
    if (empty($vars)) {
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#')) continue;
                if (str_contains($line, '=')) {
                    [$k, $v] = explode('=', $line, 2);
                    $vars[trim($k)] = trim($v);
                }
            }
        }
    }
    return $vars[$key] ?? $default;
}

function dbConfig() {
    return [
        'host' => env('DB_HOST', config('DB_HOST', 'localhost')),
        'port' => env('DB_PORT', config('DB_PORT', '3306')),
        'name' => env('DB_DATABASE', config('DB_NAME', 'spotyaudio')),
        'user' => env('DB_USERNAME', config('DB_USER', 'root')),
        'pass' => env('DB_PASSWORD', config('DB_PASSWORD', '')),
    ];
}

function isLoggedIn() {
    return isset($_SESSION['usuario_id']) && isset($_SESSION['spotify_id']);
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function e($text) {
    echo htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

function csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function maxAudioSize() {
    return (int)config('MAX_AUDIO_SIZE', 10) * 1024 * 1024;
}

function asset($path) {
    return $path;
}

function url($accion, $params = []) {
    $url = 'index.php?accion=' . $accion;
    if (!empty($params)) {
        foreach ($params as $k => $v) {
            $url .= '&' . urlencode($k) . '=' . urlencode($v);
        }
    }
    return $url;
}

function formatoFecha($fecha) {
    return date('d/m/Y H:i', strtotime($fecha));
}

function tiempoFormateado($segundos) {
    if (!$segundos) return '0:00';
    $min = floor($segundos / 60);
    $seg = $segundos % 60;
    return sprintf('%d:%02d', $min, $seg);
}

function mensajeError($codigo) {
    $mensajes = [
        'csrf' => 'Token de seguridad inválido',
        'nombre_vacio' => 'El nombre no puede estar vacío',
        'archivo_invalido' => 'Error al subir el archivo',
        'formato_invalido' => 'Solo se permiten archivos MP3',
        'tamano_excedido' => 'El archivo excede el tamaño máximo permitido',
        'subida_fallida' => 'Error al guardar el archivo',
        'duplicado' => 'Ya existe un elemento con ese nombre',
        'no_encontrado' => 'Elemento no encontrado',
        'error' => 'Ocurrió un error inesperado',
        'campos_vacios' => 'Todos los campos son requeridos',
        'config_no_encontrada' => 'Configuración no encontrada',
        'spotify_error' => 'Error al conectar con Spotify',
    ];
    return $mensajes[$codigo] ?? 'Error desconocido';
}

function mensajeExito($codigo) {
    $mensajes = [
        'creado' => 'Elemento creado correctamente',
        'actualizado' => 'Elemento actualizado correctamente',
        'eliminado' => 'Elemento eliminado correctamente',
        'config_creada' => 'Configuración guardada correctamente',
        'logout' => 'Sesión cerrada correctamente',
    ];
    return $mensajes[$codigo] ?? 'Operación exitosa';
}
