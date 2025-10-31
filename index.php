<?php

require "config/index.php";

$headers = getallheaders();

if (!isset($headers["Authorization"])) {
    http_response_code(401);
    echo "Token requerido.";
    exit;
}

$token = str_replace("Bearer ", "", $headers["Authorization"]);

try {
    $decoded = Firebase\JWT\JWT::decode($token, new Firebase\JWT\Key($jwtKey, "HS256"));
    # header("Content-Type: application/json");
    # echo json_encode(array("message" => "Acceso autorizado", "user_id" => $decoded->sub));

    $usuario = explode("/", $decoded->sub);
    $id   = $usuario[0];
    $tipo = $usuario[1];
}
catch (Exception $error) {
    http_response_code(401);
    echo "Token inválido.";
    # echo $error->getMessage();
    exit;
}

if (isset($_GET["preferencias"])) {
    $fbt = $_GET["token"];
    if ($fbt) {
        $update = $con->update("usuarios");
        $update->set("FBToken", NULL);
        $update->where("FBToken", "=", $fbt);
        $update->execute();

        $update = $con->update("usuarios");
        $update->set("FBToken", $fbt);
        $update->where("Id_Usuario", "=", $id);
        $update->execute();
    }

    $select = $con->select("usuarios", "Preferencias");
    $select->where("Id_Usuario", "=", $id);
    header("Content-Type: application/json");
    echo json_encode($select->execute());
}
elseif (isset($_GET["cambiarPreferencias"])) {
    $update = $con->update("usuarios");
    $update->set("Preferencias", $_POST["preferencias"]);
    $update->where("Id_Usuario", "=", $id);
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
elseif (isset($_GET["venta"])) {
    $select = $con->select("ventas", "SUM(detalles_ventas.Precio_Venta * detalles_ventas.Cantidad) AS Total_Precio, SUM(detalles_ventas.Cantidad) AS Total_Cantidad, ventas.Id_Venta, ventas.Fecha_Hora, ventas.Pago, usuarios.Nombre_Usuario");
    $select->innerjoin("usuarios ON usuarios.Id_Usuario = ventas.Id_Usuario");
    $select->leftjoin("detalles_ventas ON detalles_ventas.Id_Venta = ventas.Id_Venta");
    $select->where("ventas.Id_Venta", "=", $_GET["id"]);
    $select->groupby("Id_Venta");
    header("Content-Type: application/json");
    echo json_encode($select->execute());
}
elseif (isset($_GET["guardarVenta"])) {
    $id_venta   = $_GET["id"];
    $id_usuario = $id;
    $fecha_hora = date("Y-m-d H:i:s");
    $pago       = $_POST["pago"];

    if ($id_venta && is_numeric($id_venta)) {
        $guardar = $con->update("ventas");
        $guardar->set("Pago", ($pago == "" ? null : $pago));
        $guardar->where("Id_Venta", "=", $id_venta);
        $guardar->where_and("Id_Usuario", "=", $id_usuario);
        $guardar->where_and("Pago", "IS", NULL);
        $guardar->execute();

        if ($pago) {
            $titulo    = "🚀 Venta finalizada";
            $contenido = "Se realizó la venta #$id_venta.";
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

        echo "correcto";
    }
    else {
        $guardar = $con->insert("ventas", "Id_Usuario, Fecha_Hora");
        $guardar->value($id_usuario);
        $guardar->value($fecha_hora);
        $guardar->execute();
        echo $con->lastInsertId();
    }
}
elseif (isset($_GET["detallesVenta"])) {
    $select = $con->select("detalles_ventas", "detalles_ventas.*, ventas.Fecha_Hora, ventas.Pago, usuarios.Nombre_Usuario, productos.Nombre_Producto");
    $select->innerjoin("ventas ON ventas.Id_Venta = detalles_ventas.Id_Venta");
    $select->innerjoin("usuarios ON usuarios.Id_Usuario = ventas.Id_Usuario");
    $select->innerjoin("productos ON productos.Id_Producto = detalles_ventas.Id_Producto");
    $select->where("detalles_ventas.Id_Venta", "=", $_GET["id"]);
    header("Content-Type: application/json");
    echo json_encode($select->execute());
}
elseif (isset($_GET["autocompleteProductos"])) {
    $select = $con->select("productos", "Id_Producto AS value, Nombre_Producto AS label, Precio, Existencias");
    $select->where("Nombre_Producto", "LIKE", $_GET["text"]);
    $select->orderby("Nombre_Producto");
    $select->limit(10);
    header("Content-Type: application/json");
    echo json_encode($select->execute());
}
elseif (isset($_GET["guardarDetalleVenta"])) {
    $id_detalle_venta = $_POST["idDetalleVenta"];
    $id_venta         = $_GET["id"];
    $id_producto      = $_POST["idProducto"];
    $precio_venta     = $_POST["precioVenta"];
    $cantidad         = $_POST["cantidad"];

    if (is_numeric($id_venta)) {
        if ($id_detalle_venta && is_numeric($id_detalle_venta)) {
            $guardar = $con->update("detalles_ventas");
            $guardar->set("Id_Producto", $id_producto);
            $guardar->set("Precio_Venta", $precio_venta);
            $guardar->set("Cantidad", $cantidad);
            $guardar->set("Id_Producto", $id_producto);
            $guardar->where("Id_Detalle_Venta", "=", $id_detalle_venta);
            $guardar->where_and("Id_Venta",     "=", $id_venta);
        }
        else {
            $guardar = $con->insert("detalles_ventas", "Id_Venta, Id_Producto, Precio_Venta, Cantidad");
            $guardar->value($id_venta);
            $guardar->value($id_producto);
            $guardar->value($precio_venta);
            $guardar->value($cantidad);
        }

        $guardar->execute();
        echo "correcto";
    }
}
elseif (isset($_GET["eliminarDetalleVenta"])) {
    $id_detalle_venta = $_POST["idDetalleVenta"];
    $id_venta         = $_POST["id"];

    if (is_numeric($id_venta)) {
        $delete = $con->delete("detalles_ventas");
        $delete->where("Id_Detalle_Venta", "=", $id_detalle_venta);
        $delete->where_and("Id_Venta",     "=", $id_venta);
        $delete->execute();
        $con->truncate_AI("detalles_ventas", "Id_Detalle_Venta");
        echo "correcto";
    }
}

elseif (isset($_GET["notificaciones"])) {
    $select = $con->select("notificaciones", "notificaciones.*, usuarios.Nombre_Usuario");
    $select->leftjoin("usuarios ON usuarios.Id_Usuario = notificaciones.Id_Usuario");
    $select->where("notificaciones.Id_Usuario", "=", $id);
    $select->where_or("notificaciones.Tipo_Usuario", "=", $tipo);
    $select->orderby("Fecha_Hora DESC");
    $select->limit(10);
    header("Content-Type: application/json");
    echo json_encode($select->execute());
}
elseif (isset($_GET["eliminarNotificacion"])) {
    $id_notificacion = $_POST["id"];

    $delete = $con->delete("notificaciones");
    $delete->where("Id_Notificacion", "=", $id_notificacion);
    $delete->where_and("Id_Usuario", "=", $id);
    $delete->execute();

    $con->truncate_AI("notificaciones", "Id_Notificacion");
}
elseif (isset($_GET["marcarNotificacionComoLeida"])) {
    $id_notificacion = $_POST["id"];

    $update = $con->update("notificaciones");
    $update->set("`READ`", 1);
    $update->where("Id_Notificacion", "=", $id_notificacion);
    $update->where_and("Id_Usuario", "=", $id);
    $update->execute();
}
elseif (isset($_GET["notificarRechazoActualizacion"])) {
    $eliminarActions = $_GET["eliminarActions"];

    $update = $con->update("notificaciones");
    $update->set("ACTIONS", NULL);
    $update->where("Id_Notificacion", "=", $eliminarActions);
    $update->execute();

    $id_usuario = $_POST["idUsuario"];

    $titulo    = "Rechazo de actualización";
    $contenido = $_POST["Contenido"];
    $insert = $con->insert("notificaciones", "Id_Usuario, Titulo, Contenido, Fecha_Hora");
    $insert->value($id_usuario);
    $insert->value($titulo);
    $insert->value($contenido);
    $insert->value(date("Y-m-d H:i:s"));
    $insert->execute();
    echo "✅Solicitud rechazada.";

    $select = $con->select("usuarios", "FBToken");
    $select->where("Id_Usuario", "=", $id_usuario);
    $select->where_and("FBToken", "IS NOT", NULL);
    $usuarios = $select->execute();

    if (count($usuarios)) {
        $message = array(
            "token" => $usuarios[0]["FBToken"],
            "notification" => array(
                "title" => $titulo,
                "body" => $contenido
            )
        );

        $firebase->send($message);
    }
}

elseif (isset($_GET["graficaProductosExistencias"])) {
    $select = $con->select("productos", "Nombre_Producto, Existencias");
    $select->orderby("Existencias ASC, Nombre_Producto ASC");
    $select->limit(10);
    header("Content-Type: application/json");
    echo json_encode($select->execute());
}
elseif (isset($_GET["graficaVentasTotales"])) {
    $select = $con->select("ventas", "ventas.Id_Venta, ventas.Fecha_Hora, IFNULL( SUM( detalles_ventas.Precio_Venta * detalles_ventas.Cantidad ), 0 ) AS Total");
    $select->leftjoin("detalles_ventas ON detalles_ventas.Id_Venta = ventas.Id_Venta");
    $select->groupby("Id_Venta");
    $select->orderby("Fecha_Hora DESC");
    $select->limit(10);
    header("Content-Type: application/json");
    echo json_encode($select->execute());
}
elseif (isset($_GET["graficaVentasTotalesMeses"])) {
    $con->support_groupby();
    $select = $con->select("ventas", "ventas.Id_Venta, DATE_FORMAT( ventas.Fecha_Hora, '%M' ) AS Mes, IFNULL( SUM( detalles_ventas.Precio_Venta * detalles_ventas.Cantidad ), 0 ) AS Total");
    $select->leftjoin("detalles_ventas ON detalles_ventas.Id_Venta = ventas.Id_Venta");
    $select->groupby("DATE_FORMAT( ventas.Fecha_Hora, '%M' )");
    $select->orderby("Fecha_Hora DESC");
    header("Content-Type: application/json");
    echo json_encode($select->execute());
}
elseif (isset($_GET["graficaVentasTotalesUsuarios"])) {
    $con->support_groupby();
    $select = $con->select("ventas", "ventas.Id_Venta, ventas.Id_Usuario, usuarios.Nombre_Usuario, IFNULL( SUM( detalles_ventas.Precio_Venta * detalles_ventas.Cantidad ), 0 ) AS Total");
    $select->leftjoin("detalles_ventas ON detalles_ventas.Id_Venta = ventas.Id_Venta");
    $select->innerjoin("usuarios ON usuarios.Id_Usuario = ventas.Id_Usuario");
    $select->groupby("Id_Usuario");
    $select->orderby("Nombre_Usuario ASC");
    header("Content-Type: application/json");
    echo json_encode($select->execute());
}
elseif (isset($_GET["graficaVentasTotalesProductos"])) {
    $con->support_groupby();
    $select = $con->select("ventas", "ventas.Id_Venta, detalles_ventas.Id_Producto, productos.Nombre_Producto, IFNULL( SUM( detalles_ventas.Precio_Venta * detalles_ventas.Cantidad ), 0 ) AS Total");
    $select->leftjoin("detalles_ventas ON detalles_ventas.Id_Venta = ventas.Id_Venta");
    $select->innerjoin("productos ON productos.Id_Producto = detalles_ventas.Id_Producto");
    $select->groupby("Id_Producto");
    $select->orderby("Nombre_Producto ASC");
    header("Content-Type: application/json");
    echo json_encode($select->execute());
}

?>

