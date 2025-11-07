<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/supermercado/conexion.php/conexion.php');
session_start();

if(isset($_POST['registro'])){
    // Validaciones básicas
    $nombre = trim($_POST['nombre'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $correo = trim($_POST['correo'] ?? '');

    if ($nombre === '' || $usuario === '' || $password === '' || $correo === '') {
        echo "<script>alert('Por favor completa todos los campos'); window.history.back();</script>";
        exit();
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Correo inválido'); window.history.back();</script>";
        exit();
    }

    // Verificar si el usuario ya existe (prepared statement)
    $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE usuario = ? LIMIT 1");
    if(!$stmt){
        error_log("Error preparando consulta de usuario: " . $conexion->error);
        echo "<script>alert('Error en el sistema'); window.history.back();</script>";
        exit();
    }
    $stmt->bind_param('s', $usuario);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res && $res->num_rows > 0){
        echo "<script>alert('El usuario ya existe'); window.history.back();</script>";
        $stmt->close();
        exit();
    }
    $stmt->close();

    // Hash de la contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insertar nuevo usuario con prepared statement
    $rol = 'cliente';
    $insert = $conexion->prepare("INSERT INTO usuarios (nombre, usuario, password, correo, rol) VALUES (?, ?, ?, ?, ?)");
    if(!$insert){
        error_log("Error preparando inserción de usuario: " . $conexion->error);
        echo "<script>alert('Error en el sistema. Intenta más tarde.'); window.history.back();</script>";
        exit();
    }
    $insert->bind_param('sssss', $nombre, $usuario, $password_hash, $correo, $rol);
    if($insert->execute()){
        $insert->close();
        echo "<script>alert('Registro exitoso. Ahora puedes iniciar sesión.'); window.location='/supermercado/index.php/index.php';</script>";
    } else {
        error_log("Error al insertar usuario: " . $insert->error);
        echo "<script>alert('Error al registrar. Intenta nuevamente.'); window.history.back();</script>";
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro - Supermercado Fénix</title>
    <link rel="stylesheet" href="/supermercado/css/estilos.css">
</head>
<body>
<div class="contenedor">
    <h1>🛒 Registro</h1>
    <form method="POST" class="login-box">
        <input type="text" name="nombre" placeholder="Nombre completo" required>
        <input type="text" name="usuario" placeholder="Nombre de usuario" required>
        <input type="email" name="correo" placeholder="Correo electrónico" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        <button type="submit" name="registro">Registrarse</button>
        <a href="/supermercado/index.php/index.php" class="boton-cancelar" style="display:block;margin-top:10px;text-decoration:none;">Volver al login</a>
    </form>
</div>
</body>
</html>