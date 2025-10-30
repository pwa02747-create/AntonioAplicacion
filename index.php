<?php
require_once 'conexion.php';


if(isset($_GET['Vehiculos'])){
    
$db = new Database();

try {
    $sql = "SELECT * FROM vehiculos ORDER BY id_carro DESC";
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
}
?>
