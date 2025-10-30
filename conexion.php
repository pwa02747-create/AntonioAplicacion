<?php
// database.php
class Database {
    private $pdo;

    public function __construct() {
        $host = 'hopper.proxy.rlwy.net';
        $port = '19011';
        $db   = 'railway';
        $user = 'root';
        $pass = 'XDbSRyQSXGpPaFMswLqBtyodyAKsHSdu';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // Nunca mostrar errores detallados en producción
            http_response_code(500);
            die(json_encode([
                "error" => "Error de conexión a la base de datos",
                "details" => $e->getMessage() // puedes quitar esto en producción
            ]));
        }
    }

    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    public function execute($sql, $params = [], $returnLastId = false) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $returnLastId ? $this->pdo->lastInsertId() : $stmt->rowCount();
    }
}
?>
