<?php
session_start();
if(!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel de Administración</title>
<link rel="stylesheet" href="/supermercado/css/estilos.css">
</head>
<body>
<div class="contenedor">
  <h1>Bienvenido, <?= htmlspecialchars($_SESSION['usuario']); ?> 👋</h1>
  <p>Rol: <?= htmlspecialchars($_SESSION['rol']); ?></p>

  <?php if(isset($_SESSION['rol']) && ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'empleado')): ?>
    <a href="/supermercado/crud_productos.php/crud_productos.php" class="boton">🛍️ Gestionar Productos</a>
    <?php if($_SESSION['rol'] === 'admin'): ?>
    <a href="/supermercado/gestion_usuarios.php/gestion_usuarios.php" class="boton">👥 Gestionar Usuarios</a>
    <?php endif; ?>
  <?php elseif(isset($_SESSION['rol']) && $_SESSION['rol'] === 'cliente'): ?>
    <a href="/supermercado/catalogo.php/catalogo.php" class="boton">Ver Productos</a>
  <?php endif; ?>

  <a href="/supermercado/logout.php/logout.php" class="boton-cancelar">Cerrar sesión</a>
</div>
</body>
</html>

