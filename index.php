<?php

require "JWT/config/index.php";

$headers = getallheaders();

// if (!isset($headers["Authorization"])) {
//     http_response_code(401);
//     echo "Token requerido.";
//     exit;
// }

$token = str_replace("Bearer ", "", $headers["Authorization"]);

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
elseif (isset($_GET["cambiarPreferencias"])) {
    $update = $con->update("usuarios");
    $update->set("Preferencias", $_POST["preferencias"]);
    $update->where("idUsuario", "=", $id);
    $update->execute();
}

elseif (isset($_GET["Vehiculos"])) {
    $offset = $_GET["start"];
    $limit  = $_GET["length"];
    $search = $_GET["search"]["value"];
    
    $select1 = $con->select("vehiculos", "id_carro, Placa, Marca, Modelo");
    $select1->where("Placa", "LIKE", $search);

    if (isset($_GET["order"])) {
        $ordercol = $_GET["order"][0]["column"];
        $orderdir = $_GET["order"][0]["dir"];

        $cols = array("Id_carro", "Placa", "Marca", "Modelo");
        if (isset($cols[$ordercol])) {
            $dir = "ASC";
            if ($orderdir == "desc") {
                $dir = "DESC";
            }
            $select1->orderby($cols[$ordercol] . " " . $dir);
        }
    }

    if (is_numeric($offset)
    &&  is_numeric($limit)) {
        $select1->limit("$offset, $limit");
    }

    $select2 = $con->select("vehiculos", "COUNT(id_carro) AS total");
    $total   = $select2->execute();

    $productos = $select1->execute();

    foreach ($productos as $x => $producto) {
        $id_producto = $producto["id_carro"];

        $productos[$x]["acciones"] = '<a class="btn btn-primary" href="#/productos/' . $id_producto . '">
            <i class="bi bi-pencil"></i>
                <span class="d-none d-lg-block d-xl-block">Editar</span>
        </a>';
    }

    header("Content-Type: application/json");
    echo json_encode(array(
        "recordsTotal"    => $total[0][0],
        "recordsFiltered" => $total[0][0],
        "data"            => $productos
    ));
}
elseif (isset($_GET["imagenProducto"])) {
    $select = $con->select("productos", "TO_BASE64(Imagen) AS Imagen");
    $select->where("Id_Producto", "=", $_GET["id"]);
    header("Content-Type: application/json");
    echo json_encode($select->execute());
}
elseif (isset($_GET["producto"])) {
    $select = $con->select("productos", "Id_Producto, Nombre_Producto, Precio, Existencias, TO_BASE64(Imagen) AS Imagen");
    $select->where("Id_Producto", "=", $_GET["id"]);
    header("Content-Type: application/json");
    echo json_encode($select->execute());
}
elseif (isset($_GET["guardarProducto"])) {
    $id_producto     = $_POST["id"];
    $nombre_producto = $_POST["nombreProducto"];
    $precio          = $_POST["precio"];
    $existencias     = $_POST["existencias"];

    if (isset($_FILES["imagen"])) {
        $allowed_image_extension = array("image/webp");

        $imagen = $_FILES["imagen"];

        $name   = basename($imagen["name"]);
        $type   = $imagen["type"];
        $size   = $imagen["size"];

        if (in_array($type, $allowed_image_extension)) {
            $tmp_name = $imagen["tmp_name"];
            $imagen   = file_get_contents($tmp_name);
            # move_uploaded_file($tmp_name, "images/$name");
        }
        else {
            $imagen = null;
        }
    }

    if ($id_producto && is_numeric($id_producto)) {
        if ($tipo == 1) {
            if (isset($_GET["eliminarActions"])) {
                $eliminarActions = $_GET["eliminarActions"];

                $update = $con->update("notificaciones");
                $update->set("ACTIONS", NULL);
                $update->where("Id_Notificacion", "=", $eliminarActions);
                $update->execute();
            }

            $guardar = $con->update("productos");
            $guardar->set("Nombre_Producto", $nombre_producto);
            $guardar->set("Precio", $precio);
            $guardar->set("Existencias", ($existencias == "" ? null : $existencias));
            if (isset($imagen)) {
                $guardar->set("Imagen", $imagen);
            }
            $guardar->where("Id_Producto", "=", $id_producto);
            $guardar->execute();
            echo "✅Cambios guardados con &eacute;xito.";

            $titulo    = "🚀 Actualización";
            $contenido = "Se actualizó un producto.";
            $insert = $con->insert("notificaciones", "Tipo_Usuario, Titulo, Contenido, Fecha_Hora");
            $insert->value(1);
            $insert->value($titulo);
            $insert->value($contenido);
            $insert->value(date("Y-m-d H:i:s"));
            $insert->execute();
        }
        elseif ($tipo == 2) {
            $titulo    = "🚀 Se solicita actualización";
            $contenido = "Se solicita actualizar un producto, nombre: $nombre_producto, precio: $precio, existencias: $existencias.";
            $insert = $con->insert("notificaciones", "Tipo_Usuario, Titulo, Contenido, Fecha_Hora, ACTIONS");
            $insert->value(1);
            $insert->value($titulo);
            $insert->value($contenido);
            $insert->value(date("Y-m-d H:i:s"));
            $insert->value(json_encode(array(
                "Actualizar" => array(
                    "GET"  => $_GET,
                    "POST" => $_POST
                ),
                "Rechazar" => array(
                    "GET"  => array("notificarRechazoActualizacion"),
                    "POST" => array("idUsuario" => $id, "Contenido" => "Se rechazó la actualización de datos al producto, nombre: $nombre_producto, precio: $precio, existencias: $existencias.")
                )
            )));
            $insert->execute();

            echo "ℹ️Se solicit&oacute; el guardado de los cambios.";
        }

        # Consultamos admins
        $select = $con->select("usuarios", "FBToken");
        $select->where("Tipo_Usuario", "=", 1);
        $select->where_and("FBToken", "IS NOT", NULL);
        $admins = $select->execute();
        # Iteramos admins
        foreach($admins as $admin) {
            # Enviamos push notification
            $message = array(
                "token" => $admin["FBToken"],
                "notification" => array(
                    "title" => $titulo,
                    "body" => $contenido
                )
            );

            $firebase->send($message);
        }
    }
    else {
        $guardar = $con->insert("productos", "Nombre_Producto, Precio, Existencias" . (isset($imagen) ? ", Imagen" : ""));
        $guardar->value($nombre_producto);
        $guardar->value($precio);
        $guardar->value(($existencias == "" ? NULL : $existencias));
        if (isset($imagen)) {
            $guardar->value($imagen);
        }
        $guardar->execute();
        echo "✅Cambios guardados con &eacute;xito.";

        $titulo    = "🚀 Regitro";
        $contenido = "Se registró un producto.";
        $insert = $con->insert("notificaciones", "Tipo_Usuario, Titulo, Contenido, Fecha_Hora");
        $insert->value(1);
        $insert->value($titulo);
        $insert->value($contenido);
        $insert->value(date("Y-m-d H:i:s"));
        $insert->execute();

        # Consultamos admins
        $select = $con->select("usuarios", "FBToken");
        $select->where("Tipo_Usuario", "=", 1);
        $select->where_and("FBToken", "IS NOT", NULL);
        $admins = $select->execute();
        # Iteramos admins
        foreach($admins as $admin) {
            # Enviamos push notification
            $message = array(
                "token" => $admin["FBToken"],
                "notification" => array(
                    "title" => $titulo,
                    "body" => $contenido
                )
            );

            $firebase->send($message);
        }
    }
}

elseif (isset($_GET["ventas"])) {
    # if ($tipo != 1) {
        # http_response_code(401);
        # echo "Debes ser administrador.";
        # exit;
    # }

    $offset   = $_GET["start"];
    $limit    = $_GET["length"];
    $search   = $_GET["search"]["value"];

    $select1 = $con->select("ventas", "ventas.Id_Venta, usuarios.Nombre_Usuario, ventas.Fecha_Hora, ventas.Pago, SUM( detalles_ventas.Precio_Venta * detalles_ventas.Cantidad ) AS Total");
    $select1->leftjoin("detalles_ventas ON detalles_ventas.Id_Venta = ventas.Id_Venta");
    $select1->innerjoin("usuarios ON usuarios.Id_Usuario = ventas.Id_Usuario");
    $select1->where("Nombre_Usuario", "LIKE", $search);
    $select1->groupby("Id_Venta");

    if (isset($_GET["order"])) {
        $ordercol = $_GET["order"][0]["column"];
        $orderdir = $_GET["order"][0]["dir"];

        $cols = array("Id_Venta", "Nombre_Usuario", "Fecha_Hora", "Total");
        if (isset($cols[$ordercol])) {
            $dir = "ASC";
            if ($orderdir == "desc") {
                $dir = "DESC";
            }
            $select1->orderby($cols[$ordercol] . " " . $dir);
        }
    }

    if (is_numeric($offset)
    &&  is_numeric($limit)) {
        $select1->limit("$offset, $limit");
    }

    $select2 = $con->select("ventas", "COUNT(Id_Venta) AS total");
    $total   = $select2->execute();

    $ventas = $select1->execute();

    foreach ($ventas as $x => $venta) {
        $id_venta = $venta["Id_Venta"];
        $pago     = $venta["Pago"];

        $ventas[$x]["acciones"] = '<a class="btn btn-primary" href="#/ventas/' . $id_venta . '">
            <i class="bi bi-pencil"></i>
                <span class="d-none d-lg-block d-xl-block">Editar</span>
        </a>';

        if ($pago) {
            $ventas[$x]["acciones"] = '<a class="btn btn-primary" href="#/ventas/' . $id_venta . '">
                <i class="bi bi-list-ol"></i>
                    <span class="d-none d-lg-block d-xl-block">Detalle</span>
            </a>';
        }
    }

    header("Content-Type: application/json");
    echo json_encode(array(
        "recordsTotal"    => $total[0][0],
        "recordsFiltered" => $total[0][0],
        "data"            => $ventas
    ));
}


?>





