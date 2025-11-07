<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/supermercado/conexion.php/conexion.php');
session_start();

// Solo admin puede acceder
if(!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin'){
    header("Location: /supermercado/index.php/index.php");
    exit();
}

// AGREGAR USUARIO
if(isset($_POST['agregar'])){
    $nombre = $conexion->real_escape_string($_POST['nombre']);
    $usuario = $conexion->real_escape_string($_POST['usuario']);
    $password = $conexion->real_escape_string($_POST['password']);
    $correo = $conexion->real_escape_string($_POST['correo']);
    $rol = $conexion->real_escape_string($_POST['rol']);
    
    // Hash de la contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Verificar si el usuario ya existe
    $check = $conexion->query("SELECT id FROM usuarios WHERE usuario = '$usuario' LIMIT 1");
    if($check->num_rows > 0){
        echo "<script>alert('El usuario ya existe'); window.history.back();</script>";
        exit();
    }
    
    $sql = "INSERT INTO usuarios (nombre, usuario, password, correo, rol) VALUES ('$nombre','$usuario','$password_hash','$correo','$rol')";
    if($conexion->query($sql)){
        header('Location: /supermercado/gestion_usuarios.php/gestion_usuarios.php');
    } else {
        echo "<script>alert('Error: ".$conexion->error."');</script>";
    }
    exit();
}

// ELIMINAR USUARIO
if(isset($_GET['eliminar'])){
    $id = (int)$_GET['eliminar'];
    // No permitir eliminar al propio admin
    $check = $conexion->query("SELECT usuario FROM usuarios WHERE id=$id");
    if($check && $check->num_rows > 0){
        $user = $check->fetch_assoc();
        if($user['usuario'] !== $_SESSION['usuario']){
            $conexion->query("DELETE FROM usuarios WHERE id=$id");
        }
    }
    header('Location: /supermercado/gestion_usuarios.php/gestion_usuarios.php');
    exit();
}

// EDITAR USUARIO
if(isset($_POST['editar'])){
    $id = (int)$_POST['id'];
    $nombre = $conexion->real_escape_string($_POST['nombre']);
    $usuario = $conexion->real_escape_string($_POST['usuario']);
    $correo = $conexion->real_escape_string($_POST['correo']);
    $rol = $conexion->real_escape_string($_POST['rol']);
    
    // Si se proporciona nueva contraseña, actualizarla
    $password_update = "";
    if(!empty($_POST['password'])){
        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $password_update = ", password='$password_hash'";
    }
    
    $sql = "UPDATE usuarios SET nombre='$nombre', usuario='$usuario', correo='$correo', rol='$rol' $password_update WHERE id=$id";
    $conexion->query($sql);
    
    header('Location: /supermercado/gestion_usuarios.php/gestion_usuarios.php');
    exit();
}

// Obtener lista de usuarios
$resultado = $conexion->query("SELECT * FROM usuarios ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Usuarios</title>
<link rel="stylesheet" href="/supermercado/css/estilos.css">
<style>
.password-field { background: rgba(255,255,255,0.1); }
</style>
</head>
<body>
<div class="contenedor-crud">
  <h1>Gestión de Usuarios 👥</h1>
  <a href="/supermercado/dashboard.php/dashboard.php" class="boton-cancelar" style="margin-bottom:20px;display:inline-block;">← Volver al Dashboard</a>

  <!-- FORMULARIO AGREGAR -->
  <form action="" method="POST" class="form-producto">
    <input type="text" name="nombre" placeholder="Nombre completo" required>
    <input type="text" name="usuario" placeholder="Nombre de usuario" required>
    <input type="email" name="correo" placeholder="Correo" required>
    <input type="password" name="password" placeholder="Contraseña" required>
    <select name="rol" required>
      <option value="">Selecciona rol</option>
      <option value="admin">Administrador</option>
      <option value="empleado">Empleado</option>
      <option value="cliente">Cliente</option>
    </select>
    <button type="submit" name="agregar">Agregar Usuario</button>
  </form>

  <!-- LISTADO -->
  <table>
    <tr>
      <th>ID</th>
      <th>Nombre</th>
      <th>Usuario</th>
      <th>Correo</th>
      <th>Rol</th>
      <th>Acciones</th>
    </tr>
    <?php while($fila = $resultado->fetch_assoc()): ?>
    <tr>
      <td><?= $fila['id']; ?></td>
      <td><?= htmlspecialchars($fila['nombre']); ?></td>
      <td><?= htmlspecialchars($fila['usuario']); ?></td>
      <td><?= htmlspecialchars($fila['correo']); ?></td>
      <td><?= htmlspecialchars($fila['rol']); ?></td>
      <td>
        <?php if($fila['usuario'] !== $_SESSION['usuario']): ?>
        <a href="?editar_form=<?= $fila['id']; ?>" class="btn-editar">Editar</a>
        <a href="?eliminar=<?= $fila['id']; ?>" class="btn-eliminar" onclick="return confirm('¿Eliminar usuario?');">Eliminar</a>
        <?php else: ?>
        <span style="opacity:0.7">Usuario actual</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>

  <?php 
  // FORMULARIO EDITAR
  if(isset($_GET['editar_form'])):
    $id_edit = (int)$_GET['editar_form'];
    $res = $conexion->query("SELECT * FROM usuarios WHERE id=$id_edit LIMIT 1");
    if($res && $res->num_rows>0):
      $u = $res->fetch_assoc();
  ?>
    <div class="editar-modal">
      <form action="" method="POST">
        <input type="hidden" name="id" value="<?= $u['id']; ?>">
        <input type="text" name="nombre" value="<?= htmlspecialchars($u['nombre']); ?>" required>
        <input type="text" name="usuario" value="<?= htmlspecialchars($u['usuario']); ?>" required>
        <input type="email" name="correo" value="<?= htmlspecialchars($u['correo']); ?>" required>
        <input type="password" name="password" placeholder="Nueva contraseña (dejar vacío para mantener)" class="password-field">
        <select name="rol" required>
          <option value="admin" <?= $u['rol']=='admin'?'selected':'' ?>>Administrador</option>
          <option value="empleado" <?= $u['rol']=='empleado'?'selected':'' ?>>Empleado</option>
          <option value="cliente" <?= $u['rol']=='cliente'?'selected':'' ?>>Cliente</option>
        </select>
        <button type="submit" name="editar">Guardar cambios</button>
        <a href="/supermercado/gestion_usuarios.php/gestion_usuarios.php" class="boton-cancelar">Cancelar</a>
      </form>
    </div>
  <?php 
    endif;
  endif; 
  ?>

</div>
</body>
</html>