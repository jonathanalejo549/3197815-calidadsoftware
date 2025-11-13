<?php
// Conexión segura y correcta
require_once __DIR__ . '/conexion.php';

// Consulta a la base de datos
$sql = "
    SELECT p.*, 
           c.nombre AS categoria, 
           pr.nombre AS proveedor 
    FROM productos p
    LEFT JOIN categorias c ON p.id_categoria = c.id
    LEFT JOIN proveedores pr ON p.id_proveedor = pr.id
";

$result = $conexion->query($sql);

// Validar la consulta
if ($result === false) {
    die("Error en la consulta SQL: " . htmlspecialchars($conexion->error));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Catálogo de Productos</title>
<link rel="stylesheet" href="/supermercado/css/estilos.css">
</head>
<body>
<div class="contenedor">
  <h1>Catálogo de Productos</h1>

  <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:18px;">

    <?php while ($p = $result->fetch_assoc()): ?>

      <div style="background:rgba(255,255,255,0.08);padding:12px;border-radius:10px;width:220px;text-align:center;color:#fff;">

        <?php
        // Imagen segura
        $img = '/supermercado/img/placeholder.png';

        if (isset($p['imagen']) && $p['imagen'] !== '') {
            // Evita doble slash //
            $imagenLimpia = ltrim($p['imagen'], '/');
            $img = '/supermercado/' . $imagenLimpia;
        }
        ?>

        <img src="<?= htmlspecialchars($img); ?>" 
             alt="<?= htmlspecialchars($p['nombre']); ?>" 
             style="width:100%;height:140px;object-fit:cover;border-radius:8px;">

        <h3><?= htmlspecialchars($p['nombre']); ?></h3>
        <p style="font-weight:bold;">
            $<?= number_format((float)$p['precio'], 2); ?>
        </p>

      </div>

    <?php endwhile; ?>

  </div>
</div>
</body>
</html>
