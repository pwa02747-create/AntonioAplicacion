<?php
header("Content-Type: application/json"); // Siempre enviar JSON

require "config/index.php";

$nombre_usuario = $_POST["usuario"] ?? "";
$contrasena     = $_POST["contrasena"] ?? "";

// $nombre_usuario = "Rosendo";
// $contrasena     = "123";

if (!$nombre_usuario || !$contrasena) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Todos los campos son obligatorios"
    ]);
    exit;
}

// Consultar usuario
$select = $con->select("usuarios", "idUsuario, usuario, password, rol, EmailToken, Preferencias");
$select->where("usuario", "=", $nombre_usuario)
       ->where("password", "=", $contrasena);

$usuarios = $select->fetchAll();

if (count($usuarios) > 0) {
    $usuario = $usuarios[0];

    if (!empty($usuario["EmailToken"])) {
        echo json_encode([
            "status" => "error",
            "message" => "Revisa tu correo para poder iniciar sesión."
        ]);
        exit;
    }

    // Generar JWT
    $payload = [
        "iat" => time(),
        "exp" => time() + (60 * 60 * 24 * 7), // 7 días
        "sub" => $usuario["idUsuario"] . "/" . $usuario["rol"]
    ];

    $jwt = Firebase\JWT\JWT::encode($payload, $jwtKey, "HS256");

    echo json_encode([
        "status" => "ok",
        "usuario" => $usuario,
        "jwt" => $jwt
    ]);
    exit;
}

// Usuario no encontrado
echo json_encode([
    "status" => "error",
    "message" => "Usuario y/o contraseña incorrectos"
]);
?>
