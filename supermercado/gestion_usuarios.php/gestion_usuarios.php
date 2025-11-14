<?php
include_once __DIR__ . '/conexion/conexion.php';
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    header("Location: /supermercado/index.php");
    exit();
}

$agregar = filter_input(INPUT_POST, 'agregar', FILTER_DEFAULT);
$editar = filter_input(INPUT_POST, 'editar', FILTER_DEFAULT);
$eliminar = filter_input(INPUT_GET, 'eliminar', FILTER_VALIDATE_INT);
$editar_form = filter_input(INPUT_GET, 'editar_form', FILTER_VALIDATE_INT);

if ($agregar !== null) {
    $nombre = $conexion->real_escape_string(filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $usuario = $conexion->real_escape_string(filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $correo = $conexion->real_escape_string(filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL));
    $rol = $conexion->real_escape_string(filter_input(INPUT_POST, 'rol', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE usuario=? LIMIT 1");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('El usuario ya existe'); window.history.back();</script>";
        exit();
    }

    $insert = $conexion->prepare("INSERT INTO usuarios (nombre, usuario, password, correo, rol) VALUES (?,?,?,?,?)");
    $insert->bind_param("sssss", $nombre, $usuario, $password_hash, $correo, $rol);
    $insert->execute();
    header('Location: /supermercado/gestion_usuarios.php');
    exit();
}

if ($eliminar !== null) {
    $stmt = $conexion->prepare("SELECT usuario FROM usuarios WHERE id=?");
    $stmt->bind_param("i", $eliminar);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
        $user = $res->fetch_assoc();
        if ($user['usuario'] !== $_SESSION['usuario']) {
            $del = $conexion->prepare("DELETE FROM usuarios WHERE id=?");
            $del->bind_param("i", $eliminar);
            $del->execute();
        }
    }
    header('Location: /supermercado/gestion_usuarios.php');
    exit();
}

if ($editar !== null) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $nombre = $conexion->real_escape_string(filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $usuario = $conexion->real_escape_string(filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $correo = $conexion->real_escape_string(filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL));
    $rol = $conexion->real_escape_string(filter_input(INPUT_POST, 'rol', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $password = filter_input(INPUT_POST, 'password', FILTER_DEFAULT);

    if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conexion->prepare("UPDATE usuarios SET nombre=?, usuario=?, correo=?, rol=?, password=? WHERE id=?");
        $stmt->bind_param("sssssi", $nombre, $usuario, $correo, $rol, $hash, $id);
    } else {
        $stmt = $conexion->prepare("UPDATE usuarios SET nombre=?, usuario=?, correo=?, rol=? WHERE id=?");
        $stmt->bind_param("ssssi", $nombre, $usuario, $correo, $rol, $id);
    }

    $stmt->execute();
    header('Location: /supermercado/gestion_usuarios.php');
    exit();
}

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
  <a href="/supermercado/dashboard.php" class="boton-cancelar" style="margin-bottom:20px;display:inline-block;">← Volver al Dashboard</a>

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

  <table>
    <tr>
      <th>ID</th>
      <th>Nombre</th>
      <th>Usuario</th>
      <th>Correo</th>
      <th>Rol</th>
      <th>Acciones</th>
    </tr>

    <?php while ($fila = $resultado->fetch_assoc()): ?>
    <tr>
      <td><?= htmlspecialchars($fila['id']); ?></td>
      <td><?= htmlspecialchars($fila['nombre']); ?></td>
      <td><?= htmlspecialchars($fila['usuario']); ?></td>
      <td><?= htmlspecialchars($fila['correo']); ?></td>
      <td><?= htmlspecialchars($fila['rol']); ?></td>
      <td>
        <?php if ($fila['usuario'] !== $_SESSION['usuario']): ?>
        <a href="?editar_form=<?= htmlspecialchars($fila['id']); ?>" class="btn-editar">Editar</a>
        <a href="?eliminar=<?= htmlspecialchars($fila['id']); ?>" class="btn-eliminar" onclick="return confirm('¿Eliminar usuario?');">Eliminar</a>
        <?php else: ?>
        <span style="opacity:0.7">Usuario actual</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>

  <?php if ($editar_form !== null):
    $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $editar_form);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0):
      $u = $res->fetch_assoc();
  ?>
    <div class="editar-modal">
      <form action="" method="POST">
        <input type="hidden" name="id" value="<?= htmlspecialchars($u['id']); ?>">
        <input type="text" name="nombre" value="<?= htmlspecialchars($u['nombre']); ?>" required>
        <input type="text" name="usuario" value="<?= htmlspecialchars($u['usuario']); ?>" required>
        <input type="email" name="correo" value="<?= htmlspecialchars($u['correo']); ?>" required>
        <input type="password" name="password" placeholder="Nueva contraseña (opcional)" class="password-field">
        <select name="rol" required>
          <option value="admin" <?= $u['rol']=='admin'?'selected':'' ?>>Administrador</option>
          <option value="empleado" <?= $u['rol']=='empleado'?'selected':'' ?>>Empleado</option>
          <option value="cliente" <?= $u['rol']=='cliente'?'selected':'' ?>>Cliente</option>
        </select>
        <button type="submit" name="editar">Guardar cambios</button>
        <a href="/supermercado/gestion_usuarios.php" class="boton-cancelar">Cancelar</a>
      </form>
    </div>
  <?php endif; endif; ?>

</div>
</body>
</html>
