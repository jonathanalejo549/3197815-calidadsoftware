<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/supermercado/conexion.php/conexion.php');
session_start();

if(!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['admin','empleado'])){
  error_log("Intento de acceso no autorizado a crud_productos.php - Usuario: " . (isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'No autenticado'));
  header("Location: /supermercado/index.php/index.php");
  exit();
}

$puede_modificar = in_array($_SESSION['rol'], ['admin', 'empleado']);

function registrar_actividad($conexion, $accion, $id_producto = null) {
  if(isset($_SESSION['id'])) {
    $descripcion = "Producto ID: " . ($id_producto ? $id_producto : 'N/A');
    $stmt = $conexion->prepare("INSERT INTO logs (id_usuario, accion, tabla_afectada, id_registro, descripcion) VALUES (?, ?, 'productos', ?, ?)");
    $stmt->bind_param("isis", $_SESSION['id'], $accion, $id_producto, $descripcion);
    $stmt->execute();
    $stmt->close();
  }
}

function registrar_movimiento_inventario($conexion, $id_producto, $tipo, $cantidad, $stock_anterior, $stock_nuevo) {
  $stmt = $conexion->prepare("INSERT INTO movimientos_inventario (id_producto, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, id_usuario, descripcion) VALUES (?, ?, ?, ?, ?, ?, ?)");
  $descripcion = "Movimiento de inventario por {$_SESSION['usuario']}";
  $stmt->bind_param("isiiiss", $id_producto, $tipo, $cantidad, $stock_anterior, $stock_nuevo, $_SESSION['id'], $descripcion);
  $stmt->execute();
  $stmt->close();
}

try {
  if($puede_modificar && filter_input(INPUT_POST, 'agregar')){
    $stmt = $conexion->prepare("INSERT INTO productos (nombre, descripcion, precio, stock, stock_minimo, id_categoria, id_proveedor, codigo_barras) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
    $descripcion = filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_STRING);
    $precio = filter_input(INPUT_POST, 'precio', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $stock = filter_input(INPUT_POST, 'stock', FILTER_SANITIZE_NUMBER_INT);
    $stock_minimo = filter_input(INPUT_POST, 'stock_minimo', FILTER_SANITIZE_NUMBER_INT);
    $categoria = filter_input(INPUT_POST, 'categoria', FILTER_SANITIZE_NUMBER_INT);
    $proveedor = filter_input(INPUT_POST, 'proveedor', FILTER_SANITIZE_NUMBER_INT);
    $codigo_barras = filter_input(INPUT_POST, 'codigo_barras', FILTER_SANITIZE_STRING);

    if(!$stock_minimo){ $stock_minimo = 10; }

    $stmt->bind_param("ssdiiiis", $nombre, $descripcion, $precio, $stock, $stock_minimo, $categoria, $proveedor, $codigo_barras);

    if($stmt->execute()){
      $id_producto = $conexion->insert_id;
      registrar_actividad($conexion, 'crear', $id_producto);
      registrar_movimiento_inventario($conexion, $id_producto, 'entrada', $stock, 0, $stock);

      if($stock <= $stock_minimo) {
        error_log("Alerta: Producto {$nombre} creado con stock bajo el mínimo");
      }

      header("Location: /supermercado/crud_productos.php/crud_productos.php?mensaje=agregado");
    } else {
      throw new Exception("Error al agregar producto: " . $stmt->error);
    }
    $stmt->close();
    exit();
  }

  if($puede_modificar && filter_input(INPUT_GET, 'eliminar')){
    $id = filter_input(INPUT_GET, 'eliminar', FILTER_SANITIZE_NUMBER_INT);

    $stmt = $conexion->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $producto = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if($producto) {
      $stmt = $conexion->prepare("DELETE FROM productos WHERE id = ?");
      $stmt->bind_param("i", $id);

      if($stmt->execute()){
        registrar_actividad($conexion, 'eliminar', $id);
        registrar_movimiento_inventario($conexion, $id, 'ajuste', $producto['stock'], $producto['stock'], 0);
        header("Location: /supermercado/crud_productos.php/crud_productos.php?mensaje=eliminado");
      } else {
        throw new Exception("Error al eliminar producto: " . $stmt->error);
      }
      $stmt->close();
    }
    exit();
  }

  if($puede_modificar && filter_input(INPUT_POST, 'editar')){
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);

    $stmt = $conexion->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $producto_actual = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if($producto_actual) {
      $stmt = $conexion->prepare("UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, stock = ?, stock_minimo = ?, ultima_actualizacion = NOW() WHERE id = ?");

      $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
      $descripcion = filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_STRING);
      $precio = filter_input(INPUT_POST, 'precio', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
      $stock_nuevo = filter_input(INPUT_POST, 'stock', FILTER_SANITIZE_NUMBER_INT);
      $stock_minimo = filter_input(INPUT_POST, 'stock_minimo', FILTER_SANITIZE_NUMBER_INT);

      if(!$descripcion){ $descripcion = $producto_actual['descripcion']; }
      if(!$stock_minimo){ $stock_minimo = $producto_actual['stock_minimo']; }

      $stmt->bind_param("ssdiii", $nombre, $descripcion, $precio, $stock_nuevo, $stock_minimo, $id);

      if($stmt->execute()){
        registrar_actividad($conexion, 'actualizar', $id);

        if($stock_nuevo != $producto_actual['stock']) {
          $tipo = $stock_nuevo > $producto_actual['stock'] ? 'entrada' : 'salida';
          $cantidad = abs($stock_nuevo - $producto_actual['stock']);
          registrar_movimiento_inventario($conexion, $id, $tipo, $cantidad, $producto_actual['stock'], $stock_nuevo);
        }

        if($stock_nuevo <= $stock_minimo) {
          error_log("Alerta: Producto {$nombre} (ID: {$id}) con stock bajo el mínimo");
        }

        header("Location: /supermercado/crud_productos.php/crud_productos.php?mensaje=actualizado");
      } else {
        throw new Exception("Error al actualizar producto: " . $stmt->error);
      }
      $stmt->close();
    }
    exit();
  }

  $query = "SELECT p.*, c.nombre AS categoria, pr.nombre AS proveedor,
        (SELECT COUNT(*) FROM movimientos_inventario WHERE id_producto = p.id) as movimientos
        FROM productos p 
        LEFT JOIN categorias c ON p.id_categoria = c.id 
        LEFT JOIN proveedores pr ON p.id_proveedor = pr.id
        ORDER BY p.id DESC";

  $resultado = $conexion->query($query);
  if(!$resultado) {
    throw new Exception("Error al obtener productos: " . $conexion->error);
  }

  $editar_producto = null;
  if($puede_modificar && filter_input(INPUT_GET, 'editar_form')){
    $id_edit = filter_input(INPUT_GET, 'editar_form', FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conexion->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt->bind_param("i", $id_edit);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res && $res->num_rows > 0) {
      $editar_producto = $res->fetch_assoc();
    }
    $stmt->close();
  }

} catch (Exception $e) {
  error_log("Error en crud_productos.php: " . $e->getMessage());
  header("Location: /supermercado/crud_productos.php/crud_productos.php?error=1");
  exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Productos</title>
  <link rel="stylesheet" href="/supermercado/css/estilos.css">
</head>
<body>
<div class="contenedor-crud">
  <h1>Gestión de Productos 🛒</h1>
  <a href="/supermercado/dashboard.php/dashboard.php" class="boton-cancelar" style="margin-bottom:20px;display:inline-block;">← Volver al Dashboard</a>

  <?php if(isset($_GET['mensaje'])): ?>
    <div class="mensaje <?= $_GET['mensaje'] ?>">
      <?php
        switch($_GET['mensaje']) {
          case 'agregado': echo "Producto agregado exitosamente"; break;
          case 'actualizado': echo "Producto actualizado exitosamente"; break;
          case 'eliminado': echo "Producto eliminado exitosamente"; break;
        }
      ?>
    </div>
  <?php endif; ?>

  <?php if(isset($_GET['error'])): ?>
    <div class="error">
      Ocurrió un error. Por favor intente nuevamente.
    </div>
  <?php endif; ?>

  <?php if($puede_modificar): ?>
  <form action="" method="POST" class="form-producto">
    <input type="text" name="nombre" placeholder="Nombre del producto" required>
    <textarea name="descripcion" placeholder="Descripción del producto"></textarea>
    <input type="number" step="0.01" name="precio" placeholder="Precio" required>
    <input type="number" name="stock" placeholder="Stock inicial" required>
    <input type="number" name="stock_minimo" placeholder="Stock mínimo" value="10">
    <input type="text" name="codigo_barras" placeholder="Código de barras (opcional)">

    <select name="categoria" required>
      <option value="">Selecciona categoría</option>
      <?php
        $cat = $conexion->query("SELECT * FROM categorias WHERE activa = 1");
        while($c = $cat->fetch_assoc()){ 
          echo "<option value='".$c['id']."'>".$c['nombre']."</option>"; 
        }
      ?>
    </select>

    <select name="proveedor" required>
      <option value="">Selecciona proveedor</option>
      <?php
        $prov = $conexion->query("SELECT * FROM proveedores WHERE activo = 1");
        while($p = $prov->fetch_assoc()){ 
          echo "<option value='".$p['id']."'>".$p['nombre']."</option>"; 
        }
      ?>
    </select>

    <button type="submit" name="agregar">Agregar Producto</button>
  </form>
  <?php endif; ?>

  <?php if($editar_producto): ?>
  <div class="editar-modal">
    <form action="" method="POST">
      <input type="hidden" name="id" value="<?= $editar_producto['id']; ?>">
      <input type="text" name="nombre" value="<?= htmlspecialchars($editar_producto['nombre']); ?>" required>
      <textarea name="descripcion"><?= htmlspecialchars($editar_producto['descripcion']); ?></textarea>
      <input type="number" step="0.01" name="precio" value="<?= $editar_producto['precio']; ?>" required>
      <input type="number" name="stock" value="<?= $editar_producto['stock']; ?>" required>
      <input type="number" name="stock_minimo" value="<?= $editar_producto['stock_minimo']; ?>" required>
      <button type="submit" name="editar">Guardar cambios</button>
      <a href="/supermercado/crud_productos.php/crud_productos.php" class="boton-cancelar">Cancelar</a>
    </form>
  </div>
  <?php endif; ?>

  <table>
    <tr>
      <th>ID</th>
      <th>Nombre</th>
      <th>Descripción</th>
      <th>Precio</th>
      <th>Stock</th>
      <th>Stock Mín.</th>
      <th>Categoría</th>
      <th>Proveedor</th>
      <th>Última Act.</th>
      <th>Acciones</th>
    </tr>
    <?php while($fila = $resultado->fetch_assoc()): ?>
    <tr class="<?= $fila['stock'] <= $fila['stock_minimo'] ? 'stock-bajo' : '' ?>">
      <td><?= $fila['id'] ?></td>
      <td><?= htmlspecialchars($fila['nombre']) ?></td>
      <td><?= htmlspecialchars($fila['descripcion']) ?></td>
      <td>$<?= number_format($fila['precio'], 2) ?></td>
      <td><?= $fila['stock'] ?></td>
      <td><?= $fila['stock_minimo'] ?></td>
      <td><?= htmlspecialchars($fila['categoria']) ?></td>
      <td><?= htmlspecialchars($fila['proveedor']) ?></td>
      <td><?= $fila['ultima_actualizacion'] ? date('d/m/Y H:i', strtotime($fila['ultima_actualizacion'])) : 'N/A' ?></td>
      <td>
        <?php if($puede_modificar): ?>
          <a href='/supermercado/crud_productos.php/crud_productos.php?editar_form=<?= $fila['id'] ?>' class='btn-editar' title="Editar producto">✏️</a>
          <a href='/supermercado/crud_productos.php/crud_productos.php?eliminar=<?= $fila['id'] ?>' class='btn-eliminar' onclick="return confirm('¿Está seguro de eliminar este producto? Esta acción no se puede deshacer.');" title="Eliminar producto">🗑️</a>
          <span class="movimientos" title="Cantidad de movimientos de inventario">📊 <?= $fila['movimientos'] ?></span>
        <?php else: ?>
          <span class='solo-lectura'>👁️ Solo visualización</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>
</div>

<style>
.stock-bajo { background-color: #fff3cd; }
.mensaje { padding: 10px; margin: 10px 0; border-radius: 4px; }
.mensaje.agregado { background-color: #d4edda; }
.mensaje.actualizado { background-color: #cce5ff; }
.mensaje.eliminado { background-color: #fff3cd; }
.error { background-color: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 4px; }
.movimientos { margin-left: 10px; color: #666; }
.solo-lectura { color: #666; font-style: italic; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  setTimeout(function() {
    const mensajes = document.querySelector('.mensaje');
    if (mensajes) mensajes.style.display = 'none';
  }, 3000);
});
</script>
</body>
</html>

