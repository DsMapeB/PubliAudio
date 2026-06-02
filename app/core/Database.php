<?php

/**
 * Database - Clase singleton de conexión PDO.
 *
 * Compatible con Laragon (MySQL/MariaDB).
 * Proporciona prepared statements, transacciones y helpers CRUD.
 */
class Database
{
    private static ?self $instance = null;
    private ?PDO $pdo = null;
    private int $transactionDepth = 0;

    private function __construct()
    {
        $this->connect();
    }

    private function __clone() {}

    public function __wakeup()
    {
        throw new \Exception("No se puede deserializar un singleton");
    }

    /**
     * Obtiene la instancia única de Database.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establece la conexión PDO usando la configuración de /app/config/database.php
     */
    private function connect(): void
    {
        $config = require __DIR__ . '/../config/database.php';

        try {
            $dsn = sprintf(
                '%s:host=%s;port=%s;dbname=%s;charset=%s',
                $config['driver'],
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        } catch (PDOException $e) {
            error_log('[Database] Error de conexión: ' . $e->getMessage());
            throw new \RuntimeException(
                'No se pudo conectar a la base de datos. Verifica que MySQL esté corriendo en Laragon.'
            );
        }
    }

    /**
     * Retorna el objeto PDO subyacente para operaciones avanzadas.
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    // ========================================================================
    // TRANSACCIONES
    // ========================================================================

    /**
     * Inicia una transacción (soporta anidamiento).
     */
    public function beginTransaction(): bool
    {
        if ($this->transactionDepth === 0) {
            $result = $this->pdo->beginTransaction();
        } else {
            $result = $this->pdo->exec("SAVEPOINT trans{$this->transactionDepth}");
        }
        $this->transactionDepth++;
        return $result !== false;
    }

    /**
     * Confirma la transacción activa.
     */
    public function commit(): bool
    {
        $this->transactionDepth--;
        if ($this->transactionDepth === 0) {
            return $this->pdo->commit();
        }
        return true;
    }

    /**
     * Revierte la transacción activa.
     */
    public function rollback(): bool
    {
        $this->transactionDepth--;
        if ($this->transactionDepth === 0) {
            return $this->pdo->rollBack();
        }
        return $this->pdo->exec("ROLLBACK TO trans{$this->transactionDepth}") !== false;
    }

    /**
     * Ejecuta una función callback dentro de una transacción.
     * Si la función lanza una excepción, se revierte la transacción.
     *
     * @param callable $callback Función que recibe $this (Database) como argumento
     * @return mixed Resultado del callback
     * @throws \Throwable
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    // ========================================================================
    // CONSULTAS PREPARADAS
    // ========================================================================

    /**
     * Ejecuta una consulta SELECT y retorna todos los registros.
     *
     * @param string $sql Consulta SQL con placeholders (? o :nombre)
     * @param array $params Parámetros para la consulta preparada
     * @return array Arreglo de registros (asociativo)
     */
    public function select(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("[Database] select error: {$e->getMessage()} — SQL: $sql");
            return [];
        }
    }

    /**
     * Ejecuta una consulta SELECT y retorna el primer registro.
     *
     * @param string $sql Consulta SQL con placeholders
     * @param array $params Parámetros para la consulta preparada
     * @return array|null Registro encontrado o null
     */
    public function selectOne(string $sql, array $params = []): ?array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result !== false ? $result : null;
        } catch (PDOException $e) {
            error_log("[Database] selectOne error: {$e->getMessage()} — SQL: $sql");
            return null;
        }
    }

    /**
     * Ejecuta una consulta INSERT y retorna el ID insertado.
     *
     * @param string $sql Consulta SQL INSERT con placeholders
     * @param array $params Parámetros para la consulta preparada
     * @return int|string ID del registro insertado, 0 si falla
     */
    public function insert(string $sql, array $params = []): int|string
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("[Database] insert error: {$e->getMessage()} — SQL: $sql");
            return 0;
        }
    }

    /**
     * Ejecuta una consulta UPDATE y retorna el número de filas afectadas.
     *
     * @param string $sql Consulta SQL UPDATE con placeholders
     * @param array $params Parámetros para la consulta preparada
     * @return int Número de filas actualizadas
     */
    public function update(string $sql, array $params = []): int
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("[Database] update error: {$e->getMessage()} — SQL: $sql");
            return 0;
        }
    }

    /**
     * Ejecuta una consulta DELETE y retorna el número de filas eliminadas.
     *
     * @param string $sql Consulta SQL DELETE con placeholders
     * @param array $params Parámetros para la consulta preparada
     * @return int Número de filas eliminadas
     */
    public function delete(string $sql, array $params = []): int
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("[Database] delete error: {$e->getMessage()} — SQL: $sql");
            return 0;
        }
    }

    /**
     * Ejecuta una consulta sql (INSERT/UPDATE/DELETE) retorna filas afectadas.
     *
     * @param string $sql Consulta SQL con placeholders
     * @param array $params Parámetros para la consulta preparada
     * @return int Número de filas afectadas
     */
    public function execute(string $sql, array $params = []): int
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("[Database] execute error: {$e->getMessage()} — SQL: $sql");
            return 0;
        }
    }

    /**
     * Retorna el resultado de una consulta de conteo.
     *
     * @param string $sql Consulta SQL tipo COUNT con placeholders
     * @param array $params Parámetros para la consulta preparada
     * @return int Valor del conteo
     */
    public function count(string $sql, array $params = []): int
    {
        $result = $this->selectOne($sql, $params);
        return $result ? (int) reset($result) : 0;
    }

    /**
     * Retorna el último ID insertado.
     */
    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    // ========================================================================
    // VALIDACIÓN DE CONEXIÓN
    // ========================================================================

    /**
     * Verifica si la conexión a la base de datos está activa.
     *
     * @return array Estado de la conexión con detalles
     */
    public function ping(): array
    {
        try {
            $stmt = $this->pdo->query("SELECT 1 AS alive");
            $row = $stmt->fetch();

            $serverInfo = $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
            $connectionStatus = $row['alive'] === 1;

            return [
                'connected' => $connectionStatus,
                'server'    => $serverInfo,
                'driver'    => $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME),
                'database'  => $this->pdo->query("SELECT DATABASE() AS db")->fetch()['db'],
                'charset'   => $this->pdo->query("SHOW VARIABLES LIKE 'character_set_connection'")->fetch()['Value'] ?? 'unknown',
            ];
        } catch (PDOException $e) {
            return [
                'connected' => false,
                'error'     => $e->getMessage(),
            ];
        }
    }

    // ========================================================================
    // ESQUEMA
    // ========================================================================

    /**
     * Retorna todas las tablas de la base de datos actual.
     */
    public function getTables(): array
    {
        $tables = $this->select("SHOW TABLES");
        $result = [];
        foreach ($tables as $row) {
            $result[] = reset($row);
        }
        return $result;
    }

    /**
     * Retorna la estructura de una tabla específica.
     */
    public function describeTable(string $table): array
    {
        return $this->select("DESCRIBE `$table`");
    }
}
