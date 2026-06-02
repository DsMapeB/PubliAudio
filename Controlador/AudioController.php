<?php

class AudioController {
    private $usuarioId;
    private $audioModel;

    public function __construct() {
        $this->usuarioId = $_SESSION['usuario_id'] ?? 0;
        $this->audioModel = new Audio();
    }

    public function index() {
        $audios = $this->audioModel->listarPorUsuario($this->usuarioId);
        require_once __DIR__ . '/../Vista/layouts/header.php';
        require_once __DIR__ . '/../Vista/partials/navbar.php';
        require_once __DIR__ . '/../Vista/audios/index.php';
        require_once __DIR__ . '/../Vista/layouts/footer.php';
    }

    public function crear() {
        require_once __DIR__ . '/../Vista/layouts/header.php';
        require_once __DIR__ . '/../Vista/partials/navbar.php';
        require_once __DIR__ . '/../Vista/audios/crear.php';
        require_once __DIR__ . '/../Vista/layouts/footer.php';
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('audios'));
        }

        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            redirect(url('audios', ['error' => 'csrf']));
        }

        $nombre = sanitize($_POST['nombre'] ?? '');

        if (empty($nombre)) {
            redirect(url('audios', ['error' => 'nombre_vacio']));
        }

        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            redirect(url('audios', ['error' => 'archivo_invalido']));
        }

        $archivo = $_FILES['archivo'];
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        if ($extension !== 'mp3') {
            redirect(url('audios', ['error' => 'formato_invalido']));
        }

        if ($archivo['size'] > maxAudioSize()) {
            redirect(url('audios', ['error' => 'tamano_excedido']));
        }

        $nombreArchivo = 'audio_' . $this->usuarioId . '_' . time() . '.' . $extension;
        $rutaDestino = __DIR__ . '/../uploads/audios/' . $nombreArchivo;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            redirect(url('audios', ['error' => 'subida_fallida']));
        }

        $duracion = self::calcularDuracionMP3($rutaDestino);

        $resultado = $this->audioModel->crear([
            'usuario_id' => $this->usuarioId,
            'nombre' => $nombre,
            'archivo' => $nombreArchivo,
            'duracion' => (int)$duracion,
        ]);

        if ($resultado) {
            redirect(url('audios', ['success' => 'creado']));
        } else {
            redirect(url('audios', ['error' => 'duplicado']));
        }
    }

    public function editar() {
        $id = (int)($_GET['id'] ?? 0);
        $audio = $this->audioModel->obtenerPorId($id, $this->usuarioId);
        if (!$audio) redirect(url('audios', ['error' => 'no_encontrado']));

        require_once __DIR__ . '/../Vista/layouts/header.php';
        require_once __DIR__ . '/../Vista/partials/navbar.php';
        require_once __DIR__ . '/../Vista/audios/editar.php';
        require_once __DIR__ . '/../Vista/layouts/footer.php';
    }

    public function actualizar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('audios'));
        }

        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            redirect(url('audios', ['error' => 'csrf']));
        }

        $id = (int)($_POST['id'] ?? 0);
        $nombre = sanitize($_POST['nombre'] ?? '');

        if (empty($nombre)) {
            redirect(url('audios', ['error' => 'nombre_vacio']));
        }

        $resultado = $this->audioModel->actualizar($id, ['nombre' => $nombre], $this->usuarioId);

        if ($resultado) {
            redirect(url('audios', ['success' => 'actualizado']));
        } else {
            redirect(url('audios', ['error' => 'error']));
        }
    }

    public function eliminar() {
        $id = (int)($_GET['id'] ?? 0);
        $audio = $this->audioModel->obtenerPorId($id, $this->usuarioId);

        if ($audio) {
            $rutaArchivo = __DIR__ . '/../uploads/audios/' . $audio['archivo'];
            if (file_exists($rutaArchivo)) {
                unlink($rutaArchivo);
            }
            $this->audioModel->eliminar($id, $this->usuarioId);
        }

        jsonResponse(['success' => true]);
    }

    public function obtenerInfo() {
        $id = (int)($_GET['id'] ?? 0);
        $audio = $this->audioModel->obtenerPorId($id, $this->usuarioId);
        if ($audio) {
            $audio['url'] = 'uploads/audios/' . ($audio['archivo'] ?? '');
        }
        jsonResponse($audio ?: []);
    }

    public static function calcularDuracionMP3($rutaArchivo) {
        if (!file_exists($rutaArchivo)) return 0;

        $tamano = filesize($rutaArchivo);
        $fp = fopen($rutaArchivo, 'rb');
        if (!$fp) return 0;

        $buf = fread($fp, min($tamano, 262144));
        fclose($fp);

        $inicio = -1;
        $len = strlen($buf);
        for ($i = 0; $i < $len - 4 && $i < 4096; $i++) {
            if (ord($buf[$i]) === 0xFF && (ord($buf[$i + 1]) & 0xE0) === 0xE0) {
                $inicio = $i;
                break;
            }
        }
        if ($inicio < 0) return 0;

        $h2 = ord($buf[$inicio + 2]);
        $bri = ($h2 >> 4) & 0x0F;
        $sri = ($h2 >> 2) & 0x03;
        $ver = (ord($buf[$inicio + 1]) >> 3) & 0x03;

        $bitrates = [0, 32, 40, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320, 0];
        $rates = [44100, 48000, 32000, 0];

        $bitrate = $bitrates[$bri] ?? 0;
        $sampling = $rates[$sri] ?? 0;
        if ($bitrate === 0 || $sampling === 0) return 0;

        $xingOffsets = [36, 21, 36];
        foreach ($xingOffsets as $off) {
            $pos = $inicio + $off;
            if ($pos + 8 <= $len) {
                $tag = substr($buf, $pos, 4);
                if ($tag === 'Xing' || $tag === 'Info') {
                    $flags = unpack('N', substr($buf, $pos + 4, 4))[1];
                    if ($flags & 1) {
                        $frames = unpack('N', substr($buf, $pos + 8, 4))[1];
                        $spf = ($ver === 3) ? 1152 : 576;
                        $dur = ($frames * $spf) / $sampling;
                        return (int)round($dur);
                    }
                }
            }
        }

        return (int)round(($tamano * 8) / ($bitrate * 1000));
    }
}
