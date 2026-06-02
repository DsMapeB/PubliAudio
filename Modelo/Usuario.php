<?php

class Usuario {
    private $db;

    public function __construct() {
        $this->db = Conexion::getInstancia();
    }

    public function obtenerPorSpotifyId($spotifyId) {
        return $this->db->consultarUno(
            "SELECT * FROM usuarios WHERE spotify_id = ?",
            [$spotifyId]
        );
    }

    public function obtenerPorId($id) {
        return $this->db->consultarUno(
            "SELECT * FROM usuarios WHERE id = ?",
            [$id]
        );
    }

    public function crear($data) {
        return $this->db->insertar(
            "INSERT INTO usuarios (spotify_id, nombre, email, foto, access_token, refresh_token, token_expires_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $data['spotify_id'],
                $data['nombre'],
                $data['email'] ?? null,
                $data['foto'] ?? null,
                $data['access_token'],
                $data['refresh_token'],
                $data['token_expires_at']
            ]
        );
    }

    public function actualizarTokens($id, $accessToken, $refreshToken, $expiresAt) {
        return $this->db->ejecutar(
            "UPDATE usuarios SET access_token = ?, refresh_token = ?, token_expires_at = ? WHERE id = ?",
            [$accessToken, $refreshToken, $expiresAt, $id]
        );
    }

    public function actualizar($id, $data) {
        $sets = [];
        $params = [];
        foreach ($data as $key => $value) {
            $sets[] = "$key = ?";
            $params[] = $value;
        }
        $params[] = $id;
        return $this->db->ejecutar(
            "UPDATE usuarios SET " . implode(', ', $sets) . " WHERE id = ?",
            $params
        );
    }

    public function totalUsuarios() {
        return $this->db->cantidad("SELECT COUNT(*) FROM usuarios");
    }
}
