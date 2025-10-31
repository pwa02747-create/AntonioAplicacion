<?php

require "config/config.php";

$insert = $con->insert("sensor", "Temperatura, Humedad, Fecha_Hora");
$insert->value($_POST["temperatura"]);
$insert->value($_POST["humedad"]);
$insert->value(date("Y-m-d H:i:s"));
$insert->execute();

?>