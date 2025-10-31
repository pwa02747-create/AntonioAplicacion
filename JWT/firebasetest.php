<?php

require "config/index.php";

$token = $_POST["token"];

# enviarCorreo("dfraga547@gmail.com", "Firebase Ventas App Log-" . date("Y-m-d"), date("H:i:s") . "<hr><p><b>Token:</b> $token</p>");
# $fo = fopen("log.txt", "w");
# fwrite($fo, $token);
# fclose($fo);
# exit;

/**
$message = array(
    "token" => $token,
    "notification" => array(
        "title" => "🚀 Notificación de Prueba",
        "body" => "Hola Mundo!"
    )
);

$firebase->send($message);
*/

?>
