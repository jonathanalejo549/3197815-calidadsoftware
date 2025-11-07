<?php
$conexion = new mysqli("localhost", "root", "", "supermercado_db");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}
?>
