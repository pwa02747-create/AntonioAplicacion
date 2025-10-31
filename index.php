<?php

require "JWT/config/index.php";

$headers = getallheaders();

// if (!isset($headers["Authorization"])) {
//     http_response_code(401);
//     echo "Token requerido.";
//     exit;
// }
// $token = str_replace("Bearer ", "", $headers["Authorization"]);

// try {
//     $decoded = Firebase\JWT\JWT::decode($token, new Firebase\JWT\Key($jwtKey, "HS256"));
//     # header("Content-Type: application/json");
//     # echo json_encode(array("message" => "Acceso autorizado", "user_id" => $decoded->sub));

//     $usuario = explode("/", $decoded->sub);
//     $id   = $usuario[0];
//     $tipo = $usuario[3];
 $id   = '1';
 $tipo = 'admin';

// }
// catch (Exception $error) {
//     http_response_code(401);
//     echo "Token inválido.";
//     # echo $error->getMessage();
//     exit;
// }

if (isset($_GET["preferencias"])) {
    $fbt = $_GET["token"];
    if ($fbt) {
        $update = $con->update("usuarios");
        $update->set("FBToken", NULL);
        $update->where("FBToken", "=", $fbt);
        $update->execute();

        $update = $con->update("usuarios");
        $update->set("FBToken", $fbt);
        $update->where("idUsuario", "=", $id);
        $update->execute();
    }

    $select = $con->select("usuarios", "Preferencias");
    $select->where("idUsuario", "=", $id);
    header("Content-Type: application/json");
    echo json_encode($select->execute());
}

elseif (isset($_GET["Vehiculos"])) {
 
    $select = $con->select("vehiculos", "id_carro, Placa, Marca, Modelo");
    $select ->where("Placa", "LIKE", $search);   
    $carros = $select-> execute();

    foreach ($carros as $x => $carro) {
        $id_carro = $carro["id_carro"];

        $carro[$x]["acciones"] = '<a class="btn btn-primary" href="#/productos/' . $id_carro . '">
            <i class="bi bi-pencil"></i>
                <span class="d-none d-lg-block d-xl-block">Editar</span>
        </a>';
    }

    header("Content-Type: application/json");
 
   echo json_encode([
    "data" => $carros
   ]);
 exit;
}
 
elseif (isset($_GET["Mantenimiento"])) {
    $select = $con->select("mantenimientos", "id_mantenimiento, Placa, Fecha, kilometraje, Descripcion");
    $select->where("id_mantenimiento", "=", $_GET["id"]);
    
 header("Content-Type: application/json");
    echo json_encode($select->execute());
 exit;
}
 
?>







