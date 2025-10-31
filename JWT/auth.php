<?php

require "config/index.php";

$nombre_usuario = $_POST["usuario"] ?? null;
$contrasena     = $_POST["contrasena"] ?? null;

// $nombre_usuario = "rosendo";
// $contrasena     = "123";

if (!$nombre_usuario || !$contrasena) {
    header("Content-Type: application/json");
    http_response_code(400);
    echo json_encode(["error" => "Faltan datos de usuario o contraseña"]);
    exit;
}


$select = $con->select("usuarios", "idUsuario, usuario, password, rol, EmailToken, Preferencias");
$select->where("usuario", "=", $nombre_usuario)->where("password", "=", $contrasena);
$usuarios = $select->fetchAll();

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
        "sub" => $usuario["idUsuario"] . "/" . $usuario["rol"]
    ];

    $jwt = Firebase\JWT\JWT::encode($payload, $jwtKey, "HS256");
    echo "correcto----->" . json_encode($usuario) . "----->";
    echo $jwt;
    exit;
}

echo "error";
?>
