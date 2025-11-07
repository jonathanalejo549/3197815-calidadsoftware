<?php
require_once 'config.php';
require_once 'Database.php';

session_start();

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['rol'], ['admin', 'empleado'])) {
    $_SESSION['error'] = 'No tiene permisos para acceder a esta página';
    header("Location: ../index.php");
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Get categories and providers for forms
try {
    $categorias = $conn->query("SELECT id, nombre FROM categorias WHERE activo = 1 ORDER BY nombre")->fetchAll();
    $proveedores = $conn->query("SELECT id, nombre FROM proveedores WHERE activo = 1 ORDER BY nombre")->fetchAll();
} catch (PDOException $e) {
    error_log("Error loading categories/providers: " . $e->getMessage());
    $_SESSION['error'] = "Error cargando datos del formulario";
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                    $stmt = $conn->prepare("
                        INSERT INTO productos (
                            codigo, nombre, descripcion, precio_compra, precio_venta,
                            stock, stock_minimo, id_categoria, id_proveedor
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $_POST['codigo'],
                        $_POST['nombre'],
                        $_POST['descripcion'],
                        $_POST['precio_compra'],
                        $_POST['precio_venta'],
                        $_POST['stock'],
                        $_POST['stock_minimo'],
                        $_POST['id_categoria'],
                        $_POST['id_proveedor']
                    ]);

                    // Register movement
                    $id_producto = $conn->lastInsertId();
                    $stmt = $conn->prepare("
                        INSERT INTO movimientos (id_producto, id_usuario, tipo, cantidad, motivo)
                        VALUES (?, ?, 'entrada', ?, 'Stock inicial')
                    ");
                    $stmt->execute([$id_producto, $_SESSION['id'], $_POST['stock']]);

                    $_SESSION['message'] = "Producto agregado exitosamente";
                    break;

                case 'update':
                    // Get current stock
                    $stmt = $conn->prepare("SELECT stock FROM productos WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $old_stock = $stmt->fetchColumn();

                    $stmt = $conn->prepare("
                        UPDATE productos SET 
                            codigo = ?, nombre = ?, descripcion = ?,
                            precio_compra = ?, precio_venta = ?,
                            stock = ?, stock_minimo = ?,
                            id_categoria = ?, id_proveedor = ?
                        WHERE id = ?
                    ");
                    
                    $stmt->execute([
                        $_POST['codigo'],
                        $_POST['nombre'],
                        $_POST['descripcion'],
                        $_POST['precio_compra'],
                        $_POST['precio_venta'],
                        $_POST['stock'],
                        $_POST['stock_minimo'],
                        $_POST['id_categoria'],
                        $_POST['id_proveedor'],
                        $_POST['id']
                    ]);

                    // Register movement if stock changed
                    $stock_diff = $_POST['stock'] - $old_stock;
                    if ($stock_diff !== 0) {
                        $stmt = $conn->prepare("
                            INSERT INTO movimientos (id_producto, id_usuario, tipo, cantidad, motivo)
                            VALUES (?, ?, ?, ABS(?), 'Actualización de stock')
                        ");
                        $stmt->execute([
                            $_POST['id'],
                            $_SESSION['id'],
                            $stock_diff > 0 ? 'entrada' : 'salida',
                            abs($stock_diff)
                        ]);
                    }

                    $_SESSION['message'] = "Producto actualizado exitosamente";
                    break;

                case 'delete':
                    $stmt = $conn->prepare("UPDATE productos SET activo = 0 WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $_SESSION['message'] = "Producto eliminado exitosamente";
                    break;
            }
        }
    } catch (PDOException $e) {
        error_log("Error in CRUD operation: " . $e->getMessage());
        $_SESSION['error'] = "Error en la operación. Por favor intente nuevamente.";
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get all active products with their relations
try {
    $productos = $conn->query("
        SELECT p.*, c.nombre as categoria, pr.nombre as proveedor
        FROM productos p
        LEFT JOIN categorias c ON p.id_categoria = c.id
        LEFT JOIN proveedores pr ON p.id_proveedor = pr.id
        WHERE p.activo = 1
        ORDER BY p.nombre
    ")->fetchAll();
} catch (PDOException $e) {
    error_log("Error loading products: " . $e->getMessage());
    $_SESSION['error'] = "Error cargando productos";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos - Supermercado Fénix</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
    <div class="contenedor-crud">
        <header>
            <h1>🛍️ Gestión de Productos</h1>
            <a href="../dashboard.php" class="boton-volver">← Volver al Dashboard</a>
        </header>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="mensaje agregado">
                <?= htmlspecialchars($_SESSION['message']); ?>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mensaje error">
                <?= htmlspecialchars($_SESSION['error']); ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form action="<?= $_SERVER['PHP_SELF'] ?>" method="POST" class="form-producto">
            <input type="hidden" name="action" value="create">
            <input type="text" name="codigo" placeholder="Código" required>
            <input type="text" name="nombre" placeholder="Nombre del producto" required>
            <textarea name="descripcion" placeholder="Descripción"></textarea>
            <input type="number" name="precio_compra" step="0.01" placeholder="Precio de compra" required>
            <input type="number" name="precio_venta" step="0.01" placeholder="Precio de venta" required>
            <input type="number" name="stock" placeholder="Stock inicial" required>
            <input type="number" name="stock_minimo" placeholder="Stock mínimo" value="10" required>
            
            <select name="id_categoria" required>
                <option value="">Seleccione categoría</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?= $categoria['id'] ?>">
                        <?= htmlspecialchars($categoria['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="id_proveedor" required>
                <option value="">Seleccione proveedor</option>
                <?php foreach ($proveedores as $proveedor): ?>
                    <option value="<?= $proveedor['id'] ?>">
                        <?= htmlspecialchars($proveedor['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Agregar Producto</button>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Precio Compra</th>
                        <th>Precio Venta</th>
                        <th>Stock</th>
                        <th>Categoría</th>
                        <th>Proveedor</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $producto): ?>
                        <tr class="<?= $producto['stock'] <= $producto['stock_minimo'] ? 'stock-bajo' : '' ?>">
                            <td><?= htmlspecialchars($producto['codigo']) ?></td>
                            <td><?= htmlspecialchars($producto['nombre']) ?></td>
                            <td>$<?= number_format($producto['precio_compra'], 2) ?></td>
                            <td>$<?= number_format($producto['precio_venta'], 2) ?></td>
                            <td><?= $producto['stock'] ?></td>
                            <td><?= htmlspecialchars($producto['categoria']) ?></td>
                            <td><?= htmlspecialchars($producto['proveedor']) ?></td>
                            <td>
                                <button onclick="editarProducto(<?= htmlspecialchars(json_encode($producto)) ?>)" class="btn-editar">
                                    ✏️
                                </button>
                                <form action="<?= $_SERVER['PHP_SELF'] ?>" method="POST" style="display:inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $producto['id'] ?>">
                                    <button type="submit" class="btn-eliminar" onclick="return confirm('¿Está seguro de eliminar este producto?')">
                                        🗑️
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal de edición -->
    <div id="modalEditar" class="editar-modal" style="display: none;">
        <form action="<?= $_SERVER['PHP_SELF'] ?>" method="POST" class="form-producto">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            
            <input type="text" name="codigo" id="edit_codigo" placeholder="Código" required>
            <input type="text" name="nombre" id="edit_nombre" placeholder="Nombre del producto" required>
            <textarea name="descripcion" id="edit_descripcion" placeholder="Descripción"></textarea>
            <input type="number" name="precio_compra" id="edit_precio_compra" step="0.01" placeholder="Precio de compra" required>
            <input type="number" name="precio_venta" id="edit_precio_venta" step="0.01" placeholder="Precio de venta" required>
            <input type="number" name="stock" id="edit_stock" placeholder="Stock" required>
            <input type="number" name="stock_minimo" id="edit_stock_minimo" placeholder="Stock mínimo" required>
            
            <select name="id_categoria" id="edit_id_categoria" required>
                <option value="">Seleccione categoría</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?= $categoria['id'] ?>">
                        <?= htmlspecialchars($categoria['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="id_proveedor" id="edit_id_proveedor" required>
                <option value="">Seleccione proveedor</option>
                <?php foreach ($proveedores as $proveedor): ?>
                    <option value="<?= $proveedor['id'] ?>">
                        <?= htmlspecialchars($proveedor['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="modal-buttons">
                <button type="submit">Actualizar</button>
                <button type="button" onclick="cerrarModal()" class="boton-cancelar">Cancelar</button>
            </div>
        </form>
    </div>

    <script>
        function editarProducto(producto) {
            document.getElementById('edit_id').value = producto.id;
            document.getElementById('edit_codigo').value = producto.codigo;
            document.getElementById('edit_nombre').value = producto.nombre;
            document.getElementById('edit_descripcion').value = producto.descripcion;
            document.getElementById('edit_precio_compra').value = producto.precio_compra;
            document.getElementById('edit_precio_venta').value = producto.precio_venta;
            document.getElementById('edit_stock').value = producto.stock;
            document.getElementById('edit_stock_minimo').value = producto.stock_minimo;
            document.getElementById('edit_id_categoria').value = producto.id_categoria;
            document.getElementById('edit_id_proveedor').value = producto.id_proveedor;
            
            document.getElementById('modalEditar').style.display = 'flex';
        }

        function cerrarModal() {
            document.getElementById('modalEditar').style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            if (event.target == document.getElementById('modalEditar')) {
                cerrarModal();
            }
        }

        // Animación de mensajes
        document.addEventListener('DOMContentLoaded', function() {
            const mensajes = document.querySelectorAll('.mensaje');
            mensajes.forEach(mensaje => {
                setTimeout(() => {
                    mensaje.style.opacity = '0';
                    setTimeout(() => mensaje.remove(), 300);
                }, 3000);
            });
        });
    </script>
</body>
</html>