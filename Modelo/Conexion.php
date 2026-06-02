<?php

require_once __DIR__ . '/../Funciones/helpers.php';

class Conexion {
    private static $instance = null;
    private $pdo = null;

    private function __construct() {
        $this->conectar();
    }

    private function conectar() {
        $cfg = dbConfig();
        try {
            $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ]);
        } catch (PDOException $e) {
            error_log("Error de conexión: " . $e->getMessage());
            if (config('DEBUG', false)) {
                die("Error de conexión: " . $e->getMessage());
            }
            die("Error al conectar con la base de datos. Verifica tu conexión.");
        }
    }

    public static function getInstancia() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPdo() {
        return $this->pdo;
    }

    public function consultar($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en consulta: " . $e->getMessage());
            return [];
        }
    }

    public function consultarUno($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("Error en consulta: " . $e->getMessage());
            return null;
        }
    }

    public function ejecutar($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Error al ejecutar: " . $e->getMessage());
            return 0;
        }
    }

    public function insertar($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error al insertar: " . $e->getMessage());
            return 0;
        }
    }

    public function cantidad($sql, $params = []) {
        $result = $this->consultarUno($sql, $params);
        return $result ? (int)reset($result) : 0;
    }
}
