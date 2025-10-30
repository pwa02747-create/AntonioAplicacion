<?php
require_once 'database.php';

$db = new Database();

try {
    $sql = "SELECT id, placa, marca, modelo FROM vehiculos ORDER BY id DESC";
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
