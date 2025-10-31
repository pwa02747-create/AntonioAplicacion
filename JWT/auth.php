<?php

require "config/index.php";

$nombre_usuario = $_POST["usuario"];
$contrasena     = $_POST["contrasena"];

$select = $con->select("usuarios", "Id_Usuario, Tipo_Usuario, Preferencias, EmailToken");
$select->where("Nombre_Usuario", "=", $nombre_usuario);
$select->where_and("Contrasena", "=", $contrasena);

$usuarios = $select->execute();

if (count($usuarios)) {
    $usuario = $usuarios[0];

    $emailToken = $usuario["EmailToken"];

    if ($emailToken) {
        echo "Revisa tu correo para poder iniciar sesión.";
        exit;
    }

    $payload = [
        "iat" => time(),
        "exp" => time() + (60 * 60 * 24 * 7),
        "sub" => $usuario["Id_Usuario"] . "/" . $usuario["Tipo_Usuario"]
    ];

    $jwt = Firebase\JWT\JWT::encode($payload, $jwtKey, "HS256");
    echo "correcto----->" . json_encode($usuario) . "----->";
    echo $jwt;
    exit;
}

echo "error";

?>
