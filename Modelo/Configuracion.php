<?php

class Configuracion {
    private $db;

    public function __construct() {
        $this->db = Conexion::getInstancia();
    }

    public function listarPorUsuario($usuarioId) {
        return $this->db->consultar(
            "SELECT c.*, a.nombre as audio_nombre, a.archivo as audio_archivo,
                    pf.nombre_playlist as playlist_nombre, pf.imagen as playlist_imagen,
                    pf.spotify_playlist_id
             FROM configuraciones c
             JOIN audios a ON c.audio_id = a.id
             JOIN playlists_favoritas pf ON c.playlist_id = pf.id
             WHERE c.usuario_id = ?
             ORDER BY c.created_at DESC",
            [$usuarioId]
        );
    }

    public function obtenerPorId($id, $usuarioId = null) {
        $sql = "SELECT c.*, a.nombre as audio_nombre, a.archivo as audio_archivo,
                       pf.nombre_playlist as playlist_nombre, pf.imagen as playlist_imagen,
                       pf.spotify_playlist_id
                FROM configuraciones c
                JOIN audios a ON c.audio_id = a.id
                JOIN playlists_favoritas pf ON c.playlist_id = pf.id
                WHERE c.id = ?";
        $params = [$id];
        if ($usuarioId) {
            $sql .= " AND c.usuario_id = ?";
            $params[] = $usuarioId;
        }
        return $this->db->consultarUno($sql, $params);
    }

    public function crear($data) {
        return $this->db->insertar(
            "INSERT INTO configuraciones (usuario_id, playlist_id, audio_id, canciones_intervalo)
             VALUES (?, ?, ?, ?)",
            [
                $data['usuario_id'],
                $data['playlist_id'],
                $data['audio_id'],
                $data['canciones_intervalo']
            ]
        );
    }

    public function actualizar($id, $data, $usuarioId = null) {
        $sets = [];
        $params = [];
        foreach ($data as $key => $value) {
            $sets[] = "$key = ?";
            $params[] = $value;
        }
        $sql = "UPDATE configuraciones SET " . implode(', ', $sets) . " WHERE id = ?";
        $params[] = $id;
        if ($usuarioId) {
            $sql .= " AND usuario_id = ?";
            $params[] = $usuarioId;
        }
        return $this->db->ejecutar($sql, $params);
    }

    public function eliminar($id, $usuarioId = null) {
        $sql = "DELETE FROM configuraciones WHERE id = ?";
        $params = [$id];
        if ($usuarioId) {
            $sql .= " AND usuario_id = ?";
            $params[] = $usuarioId;
        }
        return $this->db->ejecutar($sql, $params);
    }

    public function activasPorUsuario($usuarioId) {
        return $this->db->cantidad(
            "SELECT COUNT(*) FROM configuraciones WHERE usuario_id = ? AND activo = 1",
            [$usuarioId]
        );
    }

    public function totalPlaylistsUnicas($usuarioId) {
        return $this->db->cantidad(
            "SELECT COUNT(DISTINCT playlist_id) FROM configuraciones WHERE usuario_id = ?",
            [$usuarioId]
        );
    }
}
