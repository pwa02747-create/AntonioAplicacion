<?php
require_once 'conexion.php';

$db = new Database();

$sql = "INSERT INTO vehiculos (placa, marca, modelo) VALUES (:placa, :marca, :modelo)";
$params = [
  ':placa' => 'XYZ-999',
  ':marca' => 'Nissan',
  ':modelo' => 'Versa'
];
$id = $db->execute($sql, $params, true);

echo json_encode(["success" => true, "vehiculo_id" => $id]);
