<?php
class Database {
    private $pdo;
    public function __construct($host, $db, $user, $pass, $charset = 'utf8mb4') {
        $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Excepciones en errores
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // Use native prepares
        ];
        $this->pdo = new PDO($dsn, $user, $pass, $options);
    }

    // Ejecuta SELECT y devuelve filas
    public function fetchAll($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Ejecuta SELECT y devuelve una fila
    public function fetch($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function execute($sql, $params = [], $returnLastId = false) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($returnLastId) return $this->pdo->lastInsertId();
        return $stmt->rowCount();
    }

    // Transacciones
    public function transaction(callable $callback) {
        try {
            $this->pdo->beginTransaction();
            $result = $callback($this);
            $this->pdo->commit();
            return $result;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
