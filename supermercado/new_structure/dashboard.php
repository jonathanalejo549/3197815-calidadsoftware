<?php
require_once 'includes/config.php';
require_once 'includes/Database.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['usuario']) || !isset($_SESSION['id'])) {
    $_SESSION['error'] = 'Por favor inicie sesión para continuar';
    header("Location: index.php");
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get product stats
    $stats = $conn->query("
        SELECT 
            COUNT(*) as total_productos,
            SUM(CASE WHEN stock <= stock_minimo THEN 1 ELSE 0 END) as productos_bajos,
            COUNT(DISTINCT id_categoria) as total_categorias,
            COUNT(DISTINCT id_proveedor) as total_proveedores
        FROM productos
        WHERE activo = 1
    ")->fetch();
    
    // Get recent movements
    $movimientos = $conn->query("
        SELECT m.*, p.nombre as producto_nombre, u.usuario
        FROM movimientos m
        JOIN productos p ON m.id_producto = p.id
        JOIN usuarios u ON m.id_usuario = u.id
        ORDER BY m.fecha DESC
        LIMIT 5
    ")->fetchAll();
    
    // Get low stock products
    $productos_bajos = $conn->query("
        SELECT p.*, c.nombre as categoria, pr.nombre as proveedor
        FROM productos p
        LEFT JOIN categorias c ON p.id_categoria = c.id
        LEFT JOIN proveedores pr ON p.id_proveedor = pr.id
        WHERE p.stock <= p.stock_minimo AND p.activo = 1
        ORDER BY p.stock ASC
        LIMIT 5
    ")->fetchAll();
    
} catch (PDOException $e) {
    error_log("Error en dashboard: " . $e->getMessage());
    $_SESSION['error'] = "Error al cargar el dashboard";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Supermercado Fénix</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="user-info">
                <h1>Bienvenido, <?= htmlspecialchars($_SESSION['nombre']); ?> 👋</h1>
                <p class="role-badge">Rol: <?= htmlspecialchars($_SESSION['rol']); ?></p>
            </div>
            <nav class="dashboard-nav">
                <?php if($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'empleado'): ?>
                    <a href="includes/crud_productos.php" class="nav-link">🛍️ Productos</a>
                    <?php if($_SESSION['rol'] === 'admin'): ?>
                        <a href="includes/crud_usuarios.php" class="nav-link">👥 Usuarios</a>
                        <a href="includes/crud_proveedores.php" class="nav-link">🏭 Proveedores</a>
                        <a href="includes/crud_categorias.php" class="nav-link">📑 Categorías</a>
                    <?php endif; ?>
                <?php endif; ?>
                <a href="includes/logout.php" class="nav-link logout">🚪 Cerrar sesión</a>
            </nav>
        </header>

        <main class="dashboard-content">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_SESSION['message']); ?>
                    <?php unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Productos</h3>
                    <p class="stat-number"><?= $stats['total_productos'] ?? 0 ?></p>
                </div>
                <div class="stat-card warning">
                    <h3>Stock Bajo</h3>
                    <p class="stat-number"><?= $stats['productos_bajos'] ?? 0 ?></p>
                </div>
                <div class="stat-card">
                    <h3>Categorías</h3>
                    <p class="stat-number"><?= $stats['total_categorias'] ?? 0 ?></p>
                </div>
                <div class="stat-card">
                    <h3>Proveedores</h3>
                    <p class="stat-number"><?= $stats['total_proveedores'] ?? 0 ?></p>
                </div>
            </div>

            <div class="dashboard-grid">
                <section class="dashboard-section">
                    <h2>⚠️ Productos con Stock Bajo</h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Stock</th>
                                    <th>Mínimo</th>
                                    <th>Categoría</th>
                                    <th>Proveedor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos_bajos as $producto): ?>
                                <tr class="<?= $producto['stock'] === 0 ? 'critical' : 'warning' ?>">
                                    <td><?= htmlspecialchars($producto['nombre']) ?></td>
                                    <td><?= $producto['stock'] ?></td>
                                    <td><?= $producto['stock_minimo'] ?></td>
                                    <td><?= htmlspecialchars($producto['categoria']) ?></td>
                                    <td><?= htmlspecialchars($producto['proveedor']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="dashboard-section">
                    <h2>📋 Últimos Movimientos</h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Producto</th>
                                    <th>Tipo</th>
                                    <th>Cantidad</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($movimientos as $movimiento): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($movimiento['fecha'])) ?></td>
                                    <td><?= htmlspecialchars($movimiento['producto_nombre']) ?></td>
                                    <td>
                                        <span class="badge <?= $movimiento['tipo'] === 'entrada' ? 'success' : 'danger' ?>">
                                            <?= $movimiento['tipo'] === 'entrada' ? '↑' : '↓' ?>
                                            <?= ucfirst($movimiento['tipo']) ?>
                                        </span>
                                    </td>
                                    <td><?= $movimiento['cantidad'] ?></td>
                                    <td><?= htmlspecialchars($movimiento['usuario']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>
    <script src="js/scripts.js"></script>
</body>
</html>