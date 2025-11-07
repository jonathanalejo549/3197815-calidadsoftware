<?php
require_once 'includes/config.php';
require_once 'includes/Database.php';

session_start();

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get all active products with their categories
    $stmt = $conn->query("
        SELECT p.*, c.nombre as categoria
        FROM productos p
        LEFT JOIN categorias c ON p.id_categoria = c.id
        WHERE p.activo = 1
        ORDER BY p.nombre
    ");
    $productos = $stmt->fetchAll();

    // Get all active categories for filter
    $stmt = $conn->query("
        SELECT id, nombre
        FROM categorias
        WHERE activo = 1
        ORDER BY nombre
    ");
    $categorias = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Error loading products: " . $e->getMessage());
    $_SESSION['error'] = "Error al cargar los productos";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Productos - Supermercado Fénix</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="contenedor">
        <header class="catalogo-header">
            <h1>🛒 Catálogo de Productos</h1>
            <div class="filtros">
                <select id="filtroCategoria" onchange="filtrarProductos()">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?= $categoria['id'] ?>">
                            <?= htmlspecialchars($categoria['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" id="busqueda" placeholder="Buscar productos..." onkeyup="filtrarProductos()">
            </div>
        </header>

        <div class="productos-grid">
            <?php foreach ($productos as $producto): ?>
                <div class="producto-card" data-categoria="<?= $producto['id_categoria'] ?>">
                    <img src="<?= !empty($producto['imagen']) ? 'uploads/productos/' . $producto['imagen'] : 'img/producto-default.jpg' ?>" 
                         alt="<?= htmlspecialchars($producto['nombre']) ?>" 
                         class="producto-imagen">
                    <div class="producto-info">
                        <h3 class="producto-nombre"><?= htmlspecialchars($producto['nombre']) ?></h3>
                        <p class="producto-categoria"><?= htmlspecialchars($producto['categoria']) ?></p>
                        <p class="producto-descripcion"><?= htmlspecialchars($producto['descripcion']) ?></p>
                        <p class="producto-precio">$<?= number_format($producto['precio_venta'], 2) ?></p>
                        <?php if ($producto['stock'] > 0): ?>
                            <button class="btn-agregar" onclick="agregarAlCarrito(<?= $producto['id'] ?>)">
                                Agregar al carrito
                            </button>
                        <?php else: ?>
                            <button class="btn-agotado" disabled>Agotado</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    function filtrarProductos() {
        const categoria = document.getElementById('filtroCategoria').value;
        const busqueda = document.getElementById('busqueda').value.toLowerCase();
        const productos = document.querySelectorAll('.producto-card');

        productos.forEach(producto => {
            const nombre = producto.querySelector('.producto-nombre').textContent.toLowerCase();
            const categoriaId = producto.dataset.categoria;
            const mostrarCategoria = !categoria || categoriaId === categoria;
            const mostrarBusqueda = !busqueda || nombre.includes(busqueda);

            if (mostrarCategoria && mostrarBusqueda) {
                producto.style.display = 'block';
                producto.classList.add('fade-in');
            } else {
                producto.style.display = 'none';
                producto.classList.remove('fade-in');
            }
        });
    }

    function agregarAlCarrito(productoId) {
        // Aquí se implementaría la lógica del carrito de compras
        console.log('Producto agregado:', productoId);
    }

    // Animación de entrada inicial
    document.addEventListener('DOMContentLoaded', () => {
        const productos = document.querySelectorAll('.producto-card');
        productos.forEach((producto, index) => {
            setTimeout(() => {
                producto.classList.add('fade-in');
            }, index * 100);
        });
    });
    </script>
</body>
</html>