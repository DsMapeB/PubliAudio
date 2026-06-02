<?php

class Audio {
    private $db;

    public function __construct() {
        $this->db = Conexion::getInstancia();
    }

    public function listarPorUsuario($usuarioId) {
        return $this->db->consultar(
            "SELECT * FROM audios WHERE usuario_id = ? ORDER BY created_at DESC",
            [$usuarioId]
        );
    }

    public function obtenerPorId($id, $usuarioId = null) {
        $sql = "SELECT * FROM audios WHERE id = ?";
        $params = [$id];
        if ($usuarioId) {
            $sql .= " AND usuario_id = ?";
            $params[] = $usuarioId;
        }
        return $this->db->consultarUno($sql, $params);
    }

    public function crear($data) {
        return $this->db->insertar(
            "INSERT INTO audios (usuario_id, nombre, archivo, duracion) VALUES (?, ?, ?, ?)",
            [$data['usuario_id'], $data['nombre'], $data['archivo'], $data['duracion'] ?? 0]
        );
    }

    public function actualizar($id, $data, $usuarioId = null) {
        $sets = [];
        $params = [];
        foreach ($data as $key => $value) {
            $sets[] = "$key = ?";
            $params[] = $value;
        }
        $sql = "UPDATE audios SET " . implode(', ', $sets) . " WHERE id = ?";
        $params[] = $id;
        if ($usuarioId) {
            $sql .= " AND usuario_id = ?";
            $params[] = $usuarioId;
        }
        return $this->db->ejecutar($sql, $params);
    }

    public function eliminar($id, $usuarioId = null) {
        $sql = "DELETE FROM audios WHERE id = ?";
        $params = [$id];
        if ($usuarioId) {
            $sql .= " AND usuario_id = ?";
            $params[] = $usuarioId;
        }
        return $this->db->ejecutar($sql, $params);
    }

    public function totalPorUsuario($usuarioId) {
        return $this->db->cantidad(
            "SELECT COUNT(*) FROM audios WHERE usuario_id = ?",
            [$usuarioId]
        );
    }
}
