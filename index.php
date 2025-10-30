<?php
require_once 'conexion.php';

$db = new Database();

try {
    $sql = "SELECT * FROM vehiculos ORDER BY id DESC";
    $vehiculos = $db->fetchAll($sql);

    echo json_encode([
        "success" => true,
        "data" => $vehiculos
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>
