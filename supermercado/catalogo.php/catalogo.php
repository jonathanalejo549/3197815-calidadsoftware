<?php
// Incluir conexión de forma segura usando __DIR__ y rutas constantes
include_once __DIR__ . '/../conexion.php/conexion.php';

// Consulta del catálogo
$result = $conexion->query("
    SELECT p.*, c.nombre AS categoria, pr.nombre AS proveedor 
    FROM productos p 
    LEFT JOIN categorias c ON p.id_categoria = c.id 
    LEFT JOIN proveedores pr ON p.id_proveedor = pr.id
");
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

        if (!empty($p['imagen'])) {
            $img = '/supermercado/' . ltrim($p['imagen'], '/');
        }
        ?>

        <img 
            src="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" 
            alt="<?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8'); ?>" 
            style="width:100%;height:140px;object-fit:cover;border-radius:8px;"
        >

        <h3><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8'); ?></h3>

        <p style="font-weight:bold;">
            $<?= number_format(floatval($p['precio']), 2); ?>
        </p>

      </div>

    <?php endwhile; ?>

  </div>
</div>
</body>
</html>
