<?php require_once "JWT/config/index.php";
// ob_start(); 

function modificarMov($con, $id_input, $monto, $fechaHora){        
    $search = $con->select("movimientos");
    $search->where("idMovimientos", "=", $id_input);
    $fetch = $search->fetch();
        
    if ($fetch) {
        $update = $con->update("movimientos");
        $update->set("monto", $monto);
        $update->set("fechaHora", $fechaHora);
        $update->where("idMovimientos", "=", $id_input);
        $update->execute();       
        return "Movimiento '$monto' y fecha:' $fechaHora  N° $id_input modificado.";
    } else { return "Modificación fallida. Movimiento no encontrado.";  }
}

function EnviarNotificacion($con, $id_usuario, $title, $body){    
$search_tokendevice = $con->Select("usuarios")->where("Id_Usuario", "=", $id_usuario);
$tk_device = $search_tokendevice->fetch();

require_once "firebasetest.php";
$message = ["title" => $title, "body" => $body];

if ($title === null || $body === null) { return ["status" => "error", "mensaje" => "Mensaje no construido"]; }
if (!$tk_device) { return ["status" => "error", "mensaje" => "Usuario no encontrado"]; }

$Tipo_Permiso = $tk_device["Permisos"] ?? null;
if (!$Tipo_Permiso) { return ["status" => "error", "mensaje" => "Permisos no definidos para el usuario"]; }

$adminsQuery = $con->Select("usuarios")->where("Permisos", "=", "Admin");
$admins = $adminsQuery->fetchAll();
if (!$admins) { return ["status" => "error", "mensaje" => "No se encontraron administradores para enviar la notificación"]; }

$errores = [];
foreach ($admins as $admin) {
    if (empty($admin["Token_Device"])) { continue; } 
        $sendmessage = crear_mensaje($message["title"], $message["body"], $admin["Token_Device"]);
    try { $firebase->send($sendmessage); } 
    catch (\Kreait\Firebase\Exception\MessagingException $e) { $errores[] = "Error al enviar a admin ID {$admin['Id_Usuario']}: " . $e->getMessage(); }
}

if (count($errores) === 0) {
    return["status" => "success", "mensaje" => "Notificacion enviada a todos los administradores"];
} else {
    return["status" => "error", "mensaje" => "Errores al enviar notificaciones", "detalle" => $errores];
    }
}

// try {
//  $headers = getallheaders();

//  if (!isset($headers["Authorization"])) {
//         throw new Exception("Token requerido", 401);
//     }
//     $token = str_replace("Bearer ", "", $headers["Authorization"]);
//     $decoded = Firebase\JWT\JWT::decode($token, new Firebase\JWT\Key("Test12345", "HS256"));
//     // echo json_encode(array("message" => "Acceso autorizado", "user_id" => $decoded->sub));
//     $usuario = explode("/", $decoded->sub);
//     $id_usuario   = $usuario[0];
//     $tipo = $usuario[1];
    
$id_usuario   = '1';  
$tipo = 'Admin';    
    // $permisoQuery = $con->select("usuarios");
    // $permisoQuery->where("Id_Usuario", "=", $id_usuario);
    // $permiso = $permisoQuery->fetch();
    // $acceso = $permiso['Permisos'] ?? 'Sin Permisos'; 
    
header("Content-Type: application/json");

if (isset($_GET["preferencias"])) {
    $select = $con->select("usuarios", "Id_Usuario, Tipo_Usuario, Preferencias, Token_Tipo, Token_STAT, Permisos");
    $select->where("Id_Usuario", "=", $id_usuario);
    echo json_encode($select->fetch());    exit;
}   
elseif (isset($_GET["cambiarPreferencias"])) {
    $update = $con->update("usuarios");
    $update->set("Preferencias", $_POST["preferencias"]);
    $update->where("Id_Usuario", "=", $id_usuario);
    $update->execute();
    echo json_encode(["status" => "ok"]);    exit;
}    

if (strpos($_SERVER['REQUEST_URI'], "RecibirTokenDevice") !== false) {
    $input = json_decode(file_get_contents("php://input"), true);
    $tokenDevice = $input["deviceToken"] ?? null;

if (!$tokenDevice) { 
    http_response_code(400); exit;
}
    $update = $con -> update("usuarios");
    $update ->set("Token_Device", $tokenDevice);
    $update ->where("Id_Usuario", "=", $id_usuario);
    $update ->execute();
 
    echo json_encode(["status" => "ok"]);    exit;
}

elseif (isset($_GET["Vehiculos"])) {
    $select1 = $con->select("vehiculos");
    $carros = $select1->fetchAll();

    foreach ($carros as $x => $carro) {
        $idcarro = $carro["id_carro"];

        $carros[$x]["acciones"] = '
        <a class="btn btn-primary me-2" ng-click="modificarVehiculo(' . $idcarro . ')">
            <i class="bi bi-pencil"></i>
            <span class="d-none d-lg-block d-xl-block">Editar</span>
        </a>
        <a class="btn btn-danger" ng-click="eliminarVehiculo(' . $idcarro . ')">
            <i class="bi bi-trash"></i>
            <span class="d-none d-lg-block d-xl-block">Eliminar</span>
        </a>';
    }

    echo json_encode([
        "data" => $carros
    ]);
    exit;
}
elseif (isset($_GET["eliminarVehiculo"])) {    
    $input = json_decode(file_get_contents("php://input"), true);
    $id = $input['id'] ?? null;
   
    $delete = $con->delete("vehiculos");
    $delete->where('id_carro = ?', [$id]);
    $delete->execute();   
    // $title = 'Notificación';
    // $body  = 'Etiqueta N° '.$id.' Eliminada';

    // $Resultado = EnviarNotificacion($con, $id_usuario, $title, $body);
    echo json_encode(["status" => "ok"]);    exit;
}
    
elseif (isset($_GET["guardarVehiculo"])) {
    try {
        $con->query("ALTER TABLE vehiculos MODIFY COLUMN id_carro INT AUTO_INCREMENT")->execute();
    } catch (PDOException $e) {}
    
    $input = json_decode(file_get_contents('php://input'), true);
    $placa = $input['Placa'] ?? null;
    $marca = $input['Marca'] ?? null;
    $modelo = $input['Modelo'] ?? null;

    if (!empty($placa)) {
        $insert = $con->Insert("vehiculos");
        $insert->create("Placa", $placa);
        $insert->create("Marca", $marca);
        $insert->create("Modelo", $modelo);
        $insert->execute();
        
        echo json_encode(["status" => "ok: Registro guardado"]);  exit;
    } 
}
    
elseif (isset($_GET['obtenerVehiculo'])) {
    $id = $_GET['id'] ?? null;

    $search = $con->select("vehiculos");
    $search->where("id_carro", "=", $id);
    
    $ve = $search->fetch();
    if ($ve) {
        echo json_encode($ve); exit;
    }else{
        echo json_encode(["status" => "error", "mensaje" => "vehiculo no encontrado"]);
        exit;
    } 
}   
    
elseif(isset($_GET['Notificaciones'])){
    $offset = isset($_GET["start"]) ? intval($_GET["start"]) : 0;
    $limit  = isset($_GET["length"]) ? intval($_GET["length"]) : 10;
    $search = isset($_GET["search"]["value"]) ? $_GET["search"]["value"] : '';

    $select1 = $con->select("notificaciones");
    $select1->where("Mensaje", "LIKE", "%$search%");
    $select1->orderby("Id_Notificacion DESC");

    if (is_numeric($offset) && is_numeric($limit)) {
        $select1->limit("$offset, $limit");
    }

    $select2 = $con->select("notificaciones", "COUNT(Id_Usuario) AS total");
    $total   = $select2->fetch();
    $notificaciones = $select1->fetchAll();
    echo json_encode(array(
        "recordsTotal"    => $total['total'],
        "recordsFiltered" => $total['total'],
        "data"            => $notificaciones
    ));
    exit;
}
elseif (isset($_GET['modificarVehiculo'])) {
    $input = json_decode(file_get_contents("php://input"), true);
    $id_input = $input['id'] ?? null;
    $placa = $input['Placa'] ?? null;

    if (!$id_input || !$placa) {
        http_response_code(400);
        echo json_encode(["status" => "error", "mensaje" => "Campos faltantes (id, nombreEtiqueta)"]);
    exit; 
    }

    $permisoQuery = $con->select("usuarios");
    $permisoQuery->where("idUsuario", "=", $id_usuario);
    $permiso = $permisoQuery->fetch();
    $acceso = $permiso['Permisos'] ?? 'Sin Permisos'; 
 
if($acceso){
    if ($acceso === 'Admin') {
        $mod_Etiqueta = modEtiqueta($con, $id_input, $nombreEtiqueta);
        $Resultado = EnviarNotificacion($con, $id_usuario, $title, $body);
        echo json_encode(["status" => "Exito", "accion" => $mod_Etiqueta, "Notificacion" => $Resultado]);
        exit;
    }   
} else {
        http_response_code(403);
        echo json_encode(["status" => "error", "mensaje" => "Acceso denegado"]);
        exit;
    }
}
elseif(isset($_GET['modificarMantenimiento'])){
    $input = json_decode(file_get_contents("php://input"), true); 
    $id_input = $input['id'] ?? null;
    $monto = $input['monto'] ?? null;
    $fechaHora = $input['fechaHora'] ?? null;

    if (!$id_input || !$monto || !$fechaHora) {
        http_response_code(400);
        echo json_encode(["status" => "error", "mensaje" => "Campos faltantes (id, monto, fecha)"]); exit; 
    }
    $permisoQuery = $con->select("usuarios");
    $permisoQuery->where("Id_Usuario", "=", $id_usuario);
    $permiso = $permisoQuery->fetch();
    $acceso = $permiso['Permisos'] ?? 'Sin Permisos'; 
 
if($acceso){
    if ($acceso === 'Admin') {
        $modificar_Mov = modificarMov($con, $id_input, $monto, $fechaHora);
        $Resultado = EnviarNotificacion($con, $id_usuario, $title, $body);
        echo json_encode(["status" => "Exito", "accion" => $modificar_Mov ,"Notificaciones" => $Resultado]);
        exit;
    }   
} else {
        http_response_code(403);
        echo json_encode(["status" => "error", "mensaje" => "Acceso denegado"]);
        exit;
    }
}   

    
// } catch (Exception $error) {
//     http_response_code(401);
//     echo json_encode([
//          "token: " => "Token invalido.", 
//           "error: "=> $error->getMessage()
//     ]); exit;
// }
?>


