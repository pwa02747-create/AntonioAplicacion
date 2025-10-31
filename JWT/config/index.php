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
require __DIR__ . '/../vendor/autoload.php';

require "conexion.php";
require "enviarCorreo.php";
# mkdir firebase-php-jwt
# cd firebase-php-jwt
# composer require firebase/php-jwt
// require "firebase-php-jwt/vendor/autoload.php";
# mkdir kreait-firebase-php
# cd kreait-firebase-php
# composer require kreait/firebase-php
# extensiones php -----> curl, json, openssl, mbstring o gmp
// require "kreait-firebase-php/vendor/autoload.php";

$jwtKey = "Test12345";

$serviceAccountJson = json_decode('', true);
$firebase = (new Kreait\Firebase\Factory)->withServiceAccount($serviceAccountJson)->createMessaging();

$con = new Conexion(array(
    "tipo"       => "mysql",
    "servidor"   => "hopper.proxy.rlwy.net",
    "puerto"     => 19011,
    "bd"         => "railway",
    "usuario"    => "root",
    "contrasena" => "XDbSRyQSXGpPaFMswLqBtyodyAKsHSdu"
));

?>
