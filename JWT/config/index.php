<?php

ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL & ~E_DEPRECATED);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Allow: GET, POST, OPTIONS");

// Manejar preflight (opcional, pero recomendado)
if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    http_response_code(200);
    exit();
}

date_default_timezone_set("America/Matamoros");

require __DIR__ . '/../../vendor/autoload.php';

require "conexion.php";
require "enviarCorreo.php";

// Clave JWT
$jwtKey = "Test12345";

// Inicializar Firebase usando variable de entorno
$serviceAccountJson = getenv('FIREBASE_CREDENTIALS');

if (!$serviceAccountJson) {
    throw new Exception('FIREBASE_CREDENTIALS no está definido en el entorno');
}

$serviceAccountArray = json_decode($serviceAccountJson, true);

$firebase = (new Kreait\Firebase\Factory)
    ->withServiceAccount($serviceAccountArray)
    ->createMessaging();

// Conexión a MySQL
$con = new Conexion([
    "tipo"       => "mysql",
    "servidor"   => "hopper.proxy.rlwy.net",
    "puerto"     => 19011,
    "bd"         => "railway",
    "usuario"    => "root",
    "contrasena" => "XDbSRyQSXGpPaFMswLqBtyodyAKsHSdu"
]);

$config = [
    "tipo"       => "mysql",
    "servidor"   => "hopper.proxy.rlwy.net",
    "puerto"     => 19011,
    "bd"         => "railway",
    "usuario"    => "root",
    "contrasena" => "XDbSRyQSXGpPaFMswLqBtyodyAKsHSdu"
];

?>
