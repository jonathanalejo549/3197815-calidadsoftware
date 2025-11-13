<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/supermercado/conexion/conexion.php');
session_start();

if(!isset($_SESSION['usuario']) || !in_array($_SESSION['rol'], ['admin','empleado'])){
    header("Location: /supermercado/index.php");
    exit();
}

$crear = "CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    correo VARCHAR(150),
    telefono VARCHAR(50),
    direccion VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
$conexion->query($crear);

if(isset($_POST['agregar'])){
    $nombre = $conexion->real_escape_string($_POST['nombre']);
    $correo = $conexion->real_escape_string($_POST['correo']);
    $telefono = $conexion->real_escape_string($_POST['telefono']);
    $direccion = $conexion->real_escape_string($_POST['direccion']);

    $conexion->query("INSERT INTO clientes (nombre, correo, telefono, direccion)
                      VALUES ('$nombre','$correo','$telefono','$direccion')");

    header('Location: /supermercado/crud_clientes.php');
    exit();
}

if(isset($_GET['eliminar'])){
    $id = (int)$_GET['eliminar'];
    $conexion->query("DELETE FROM clientes WHERE id=$id");
    header('Location: /supermercado/crud_clientes.php');
    exit();
}

if(isset($_POST['editar'])){
    $id = (int)$_POST['id'];
    $nombre = $conexion->real_escape_string($_POST['nombre']);
    $correo = $conexion->real_escape_string($_POST['correo']);
    $telefono = $conexion->real_escape_string($_POST['telefono']);
    $direccion = $conexion->real_escape_string($_POST['direccion']);

    $conexion->query("UPDATE clientes SET nombre='$nombre', correo='$correo', 
                      telefono='$telefono', direccion='$direccion' WHERE id=$id");

    header('Location: /supermercado/crud_clientes.php');
    exit();
}

$resultado = $conexion->query("SELECT * FROM clientes ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Clientes</title>
<link rel="stylesheet" href="/supermercado/css/estilos.css">
</head>
<body>
<div class="contenedor-crud">
  <h1>Gestión de Clientes 👥</h1>

  <form action="" method="POST" class="form-producto">
    <input type="text" name="nombre" placeholder="Nombre" required>
    <input type="email" name="correo" placeholder="Correo">
    <input type="text" name="telefono" placeholder="Teléfono">
    <input type="text" name="direccion" placeholder="Dirección">
    <button type="submit" name="agregar">Agregar</button>
  </form>

  <table>
    <tr>
      <th>ID</th>
      <th>Nombre</th>
      <th>Correo</th>
      <th>Teléfono</th>
      <th>Dirección</th>
      <th>Acciones</th>
    </tr>

    <?php while($fila = $resultado->fetch_assoc()): ?>
      <tr>
        <td><?= $fila['id']; ?></td>
        <td><?= htmlspecialchars($fila['nombre']); ?></td>
        <td><?= htmlspecialchars($fila['correo']); ?></td>
        <td><?= htmlspecialchars($fila['telefono']); ?></td>
        <td><?= htmlspecialchars($fila['direccion']); ?></td>
        <td>
          <a href="/supermercado/crud_clientes.php?editar_form=<?= $fila['id']; ?>" class="btn-editar">Editar</a>
          <a href="/supermercado/crud_clientes.php?eliminar=<?= $fila['id']; ?>" class="btn-eliminar" onclick="return confirm('¿Eliminar cliente?');">Eliminar</a>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>

  <?php if(isset($_GET['editar_form'])):
    $id_edit = (int)$_GET['editar_form'];
    $res = $conexion->query("SELECT * FROM clientes WHERE id=$id_edit LIMIT 1");
    if($res && $res->num_rows>0){ $c = $res->fetch_assoc(); ?>
      <div class="editar-modal">
        <form action="" method="POST">
          <input type="hidden" name="id" value="<?= $c['id']; ?>">
          <input type="text" name="nombre" value="<?= htmlspecialchars($c['nombre']); ?>" required>
          <input type="email" name="correo" value="<?= htmlspecialchars($c['correo']); ?>">
          <input type="text" name="telefono" value="<?= htmlspecialchars($c['telefono']); ?>">
          <input type="text" name="direccion" value="<?= htmlspecialchars($c['direccion']); ?>">
          <button type="submit" name="editar">Guardar cambios</button>
          <a href="/supermercado/crud_clientes.php" class="boton-cancelar">Cancelar</a>
        </form>
      </div>
  <?php }
  endif; ?>

</div>
</body>
</html>
