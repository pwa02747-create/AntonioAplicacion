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

elseif (isset($_GET["Vehiculo"])) {
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
elseif (isset($_GET["eliminarEtiqueta"])) {    
    $input = json_decode(file_get_contents("php://input"), true);
    $id = $input['id'] ?? null;
   
    $delete = $con->delete("etiquetas");
    $delete->where('idEtiqueta = ?', [$id]);
    $delete->execute();   
    $title = 'Notificación';
    $body  = 'Etiqueta N° '.$id.' Eliminada';

    $Resultado = EnviarNotificacion($con, $id_usuario, $title, $body);
    echo json_encode(["status" => "ok", "Notificacion" => $Resultado]);    exit;
}
elseif (isset($_GET["guardarEtiqueta"])) {
    try {
        $con->query("ALTER TABLE etiquetas MODIFY COLUMN idEtiqueta INT AUTO_INCREMENT")->execute();
    } catch (PDOException $e) {}
    
    $input = json_decode(file_get_contents('php://input'), true);
    $nombreEtiqueta = $input['nombreEtiqueta'] ?? null;

    if (!empty($nombreEtiqueta)) {
        $insert = $con->Insert("etiquetas");
        $insert->create("nombreEtiqueta", $nombreEtiqueta);
        $insert->execute();
        
        $title = 'Notificación';
        $body  = 'Nueva Etiqueta Insertada: '.$nombreEtiqueta;
        $Resultado = EnviarNotificacion($con, $id_usuario, $title, $body);
        echo json_encode(["status" => "ok: Etiqueta guardada", "Notificacion" => $Resultado]);  exit;
    } 
}
elseif (isset($_GET['obtenerEtiqueta'])) {
    $id = $_GET['id'] ?? null;

    $search = $con->select("etiquetas");
    $search->where("idEtiqueta", "=", $id);
    
    $etiqueta = $search->fetch();
    if ($etiqueta) {
        echo json_encode($etiqueta); exit;
    }else{
        echo json_encode(["status" => "error", "mensaje" => "Etiqueta no encontrada"]);
        exit;
    } 
}   
elseif (isset($_GET["Emparejamiento"])) {    
    $offset = isset($_GET["start"]) ? intval($_GET["start"]) : 0;
    $limit  = isset($_GET["length"]) ? intval($_GET["length"]) : 10;
    $search = isset($_GET["search"]["value"]) ? $_GET["search"]["value"] : '';

    $inner = $con->select("movimientosetiquetas",
        "movimientos.idMovimientos, movimientos.monto, movimientos.fechaHora,
         IFNULL(GROUP_CONCAT(etiquetas.nombreEtiqueta), 'Sin Etiqueta') AS etiquetas"
    ); 
    $inner->join("LEFT", "movimientos", "USING(idMovimientos)");
    $inner->join("LEFT", "etiquetas", "USING(idEtiqueta)");
    $inner->groupby("movimientos.idMovimientos, movimientos.monto, movimientos.fechaHora");
    $inner->orderby("movimientos.monto DESC");

    if (is_numeric($offset) && is_numeric($limit)) {
        $inner->limit("$offset, $limit");
    }

    $movimientos = $inner->fetchAll();
    $total = count($movimientos);
    foreach ($movimientos as $x => $movimiento) {
        $movimientos[$x]["acciones"] = '
            <a class="btn btn-primary me-2" ng-click="modificarMovimiento('.$movimiento["idMovimientos"].')">
                <i class="bi bi-pencil"></i>
                <span class="d-none d-lg-block d-xl-block">Editar</span>
            </a>
            <a class="btn btn-danger" ng-click="eliminarMovimiento('.$movimiento["idMovimientos"].')">
                <i class="bi bi-trash"></i>
                <span class="d-none d-lg-block d-xl-block">Eliminar</span>
            </a>';
    }

    echo json_encode(array(
        "recordsTotal"    => $total,
        "recordsFiltered" => $total,
        "data"            => $movimientos
    ));
    exit;
}
elseif(isset($_GET['guardarMovimientos'])){
try {
    $con->query("ALTER TABLE movimientos MODIFY COLUMN idMovimientos INT AUTO_INCREMENT")->execute();
} catch (PDOException $e) {}
    $input = json_decode(file_get_contents('php://input'), true);
    
    $monto = $input['monto'] ?? null;
    $fecha = $input['fechaHora'] ?? null;
    
if (!empty($monto) || !empty($fecha)) {
    $insert = $con->insert('movimientos');
    $insert->create('monto', $monto);
    $insert->create('fechaHora', $fecha);
    $insert->execute();
        
    $title = 'Notificación';
    $body  = 'Nuevo Movimiento Guardado, Monto: ' . $monto . " y fecha: " . $fecha;
    $Resultado = EnviarNotificacion($con, $id_usuario, $title, $body);
    echo json_encode(["status" => "ok", "resultado" => $Resultado]);    exit;
    }
}
elseif(isset($_GET['eliminarMovimientos'])){
    $input = json_decode(file_get_contents("php://input"), true);
    $id = $input['id']; 

    $delete = $con ->delete('movimientos');
    $delete->where('idMovimientos = ?', [$id]);
    $delete->execute();
    
    $title = 'Notificación';
    $body  = 'Movimiento N° '.$id.' Eliminado';
    $Resultado = EnviarNotificacion($con, $id_usuario, $title, $body);
    echo json_encode(["status" => "ok", "Notificacion" => $Resultado]);
    exit;
}
elseif (isset($_GET['obtenerMovimiento'])) {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(["status" => "error", "mensaje" => "ID no especificado"]);
        exit;
    }
    $Movimiento = $con->select("movimientos")->where("idMovimientos", "=", $id)->fetch();
    
     if ($Movimiento) {
        // Convertir el formato de fecha de Y-m-d a d-m-Y
        if (!empty($Movimiento["fechaHora"])) { $fechaObj = DateTime::createFromFormat('Y-m-d', $Movimiento["fechaHora"]);
            if ($fechaObj) { $Movimiento["fechaHora"] = $fechaObj->format('d-m-Y'); }
        }
        echo json_encode($Movimiento);    exit;
    }  else {
        http_response_code(404);
        echo json_encode(["status" => "error", "mensaje" => "Movimiento no encontrado"]);
        exit;
    } 
}
elseif (isset($_GET["movimientosetiquetas"])) {
    try {
        $offset = isset($_GET["start"]) ? intval($_GET["start"]) : 0;
        $limit  = isset($_GET["length"]) ? intval($_GET["length"]) : 10;
        $search = isset($_GET["search"]["value"]) ? $_GET["search"]["value"] : '';

        $selectTotal = $con->select("movimientosetiquetas", "COUNT(*) AS total");
        $total = $selectTotal->fetch();
        $selectFiltered = $con->select("movimientosetiquetas", "COUNT(*) AS total");
        if (!empty($search)) {
            $selectFiltered->where("idMovimientoEtiqueta", "LIKE", "%$search%");
        }
        $filtered = $selectFiltered->fetch();

        $order = $con->select("movimientosetiquetas",
            "idMovimientoEtiqueta,
             idMovimientos,
             idEtiqueta"
        );
        $order->groupby("idMovimientoEtiqueta, idMovimientos, idEtiqueta");
        $order->limit("$offset, $limit");

        $movimientosetiquetas = $order->fetchAll();
        foreach ($movimientosetiquetas as $x => $movimientosetiqueta) {
            $movimientosetiquetas[$x]["acciones"] = '
                <a class="btn btn-danger" ng-click="eliminarMovimientoEtiqueta(' . $movimientosetiqueta["idMovimientoEtiqueta"] . ')">
                    <i class="bi bi-trash"></i>
                    <span class="d-none d-lg-block d-xl-block">Eliminar</span>
                </a>';
        }
        echo json_encode(array(
            "recordsTotal"    => $total["total"],
            "recordsFiltered" => $filtered["total"],
            "data"            => $movimientosetiquetas
        ));
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "❌ Error interno: " . $e->getMessage()]);
        exit;
    } 
 echo json_encode(["status" => "ok"]);
 exit;
}   
elseif(isset($_GET['GuardarMovimientosEtiquetas'])){   
 try {
        $con->query("ALTER TABLE movimientosetiquetas MODIFY COLUMN idMovimientoEtiqueta INT AUTO_INCREMENT")->execute();
    } catch (PDOException $e) {}
    
    $input = json_decode(file_get_contents('php://input'), true);
    $idMovimiento = $input['idMovimiento'] ?? null;
    $idEtiqueta = $input['idEtiqueta'] ?? null;

    if(!empty($idMovimiento) && !empty($idEtiqueta)){
        $insert = $con->insert('movimientosetiquetas');
        $insert->create('idMovimientos', $idMovimiento);
        $insert->create('idEtiqueta', $idEtiqueta);
        $insert ->execute();
        
        $title = "Notificación";
        $body = "Movimientos-Etiqueta: ID del Movimiento = " . $idMovimiento . " + ID de la Etiqueta = " . $idEtiqueta . " [ Guardado ]";
        $Resultado = EnviarNotificacion($con, $id_usuario, $title, $body);
        echo json_encode(["status" => "ok", "Notificacion" => $Resultado]); exit;
    } else {
        $title = "Notificación";
        $body = "Error al guardar en: Movimientos-Etiqueta";
        echo json_encode(["status" => "Error al guardar"]); exit;
    }
}
elseif(isset($_GET['eliminarMovimientosEtiqueta'])){
$id = $_GET['id']; 

    $delete = $con ->delete('movimientosetiquetas');
    $delete->where('idMovimientoEtiqueta = ?', [$id]);
    $delete->execute();

    $title = "Notificación";
    $body = "Movimientos-Etiqueta eliminado: ".$id;
    $Resultado = EnviarNotificacion($con, $id_usuario, $title, $body);
    echo json_encode(["status" => "ok", "Notificacion" => $Resultado]); 
 exit;
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
elseif(isset($_GET["Insertar_Notificacion"])){
    try {
        $con->query("ALTER TABLE notificaciones MODIFY COLUMN Id_Notificacion INT AUTO_INCREMENT")->execute();
    } catch (PDOException $e) {}
    
    $input = json_decode(file_get_contents("php://input"), true);
    $id = $input['id'] ?? null;
    $nombreEtiqueta = $input['nombreEtiqueta'] ?? null;
    $tabla = $input['tabla'] ?? null;
    $monto = $input['monto'] ?? null;
    $fechaHora = $input['fechaHora'] ?? null;
    $tipoAccion = $input['tipoAccion'] ?? null;
    
    if ($tabla === "etiquetas" && $tipoAccion === 'Modificacion') {
        $datos = [
            "tabla" => "etiquetas", "id" => $id,
            "nombreEtiqueta" => $nombreEtiqueta, "tipoAccion" => 'Modificacion'
        ];
    } elseif ($tabla === "movimientos"  && $tipoAccion === 'Modificacion') {
        $datos = [
            "tabla" => "movimientos", "id" => $id, "monto" => $monto, 
            "fechaHora" => $fechaHora, "tipoAccion" => 'Modificacion'
        ];
    } elseif($tabla === "etiquetas" && $tipoAccion === 'Eliminacion'){
        $datos = ["tabla" => "etiquetas" ,"id" => $id, "tipoAccion" => 'Eliminacion'];
    } elseif($tabla === 'movimientos' && $tipoAccion === 'Eliminacion'){
        $datos = ["tabla" => "movimientos" ,"id" => $id, "tipoAccion" => 'Eliminacion'];
    }
    
    $nombreQuery = $con -> select("usuarios","Nombre_Usuario");
    $nombreQuery -> where("Id_Usuario", "=", $id_usuario);
    $nombre = $nombreQuery -> fetch();
    $nombreUsuario = $nombre['Nombre_Usuario'];
 
    $fecha = date("Y-m-d H:i:s"); 
    $insert =  $con -> Insert("notificaciones");
    $insert -> create("Id_Usuario", $id_usuario);    
    $insert -> create("Tipo", "Solicitud de {$tipoAccion} en: {$tabla}");
    $insert -> create("Mensaje", "{$tipoAccion} en {$tabla} ({$id})");    
    $insert -> create("Datos", json_encode($datos));
    $insert -> create("Fecha", $fecha);    
    $insert -> create("Permisos", $acceso);
    $insert -> create("Estado", "Pendiente");
    $insert -> create("NombreUsuario", $nombreUsuario);
    $insert->execute();
    
    $title = 'Nueva solicitud';
    $body = 'El usuario '.$nombreUsuario.' quiere realizar cambios';
    $Resultado = EnviarNotificacion($con, $id_usuario, $title, $body);
    echo json_encode(["status" => "ok", "Notificacion" => $Resultado]);
    exit;
}
elseif (isset($_GET['modificarEtiqueta'])) {
    $input = json_decode(file_get_contents("php://input"), true);
    $id_input = $input['id'] ?? null;
    $nombreEtiqueta = $input['nombreEtiqueta'] ?? null;

    if (!$id_input || !$nombreEtiqueta) {
        http_response_code(400);
        echo json_encode(["status" => "error", "mensaje" => "Campos faltantes (id, nombreEtiqueta)"]);
    exit; 
    }

    $permisoQuery = $con->select("usuarios");
    $permisoQuery->where("Id_Usuario", "=", $id_usuario);
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
elseif(isset($_GET['modificarMovimiento'])){
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
