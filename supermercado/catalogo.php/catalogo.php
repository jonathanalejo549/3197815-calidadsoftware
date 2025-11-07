<?php
// Incluir conexión usando DOCUMENT_ROOT para evitar problemas de rutas relativas
include_once($_SERVER['DOCUMENT_ROOT'] . '/supermercado/conexion.php/conexion.php');
// Página pública de catálogo de productos para clientes

$result = $conexion->query("SELECT p.*, c.nombre AS categoria, pr.nombre AS proveedor FROM productos p LEFT JOIN categorias c ON p.id_categoria=c.id LEFT JOIN proveedores pr ON p.id_proveedor=pr.id");
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
    <?php while($p = $result->fetch_assoc()): ?>
      <div style="background:rgba(255,255,255,0.08);padding:12px;border-radius:10px;width:220px;text-align:center;color:#fff;">
        <!-- Si existe campo 'imagen' se muestra, si no, placeholder -->
        <?php
        $img = '/supermercado/img/placeholder.png';
        if(isset($p['imagen']) && !empty($p['imagen'])){
            $img = '/supermercado/' . ltrim($p['imagen'], '/');
        }
        ?>
        <img src="<?= $img; ?>" alt="<?= htmlspecialchars($p['nombre']); ?>" style="width:100%;height:140px;object-fit:cover;border-radius:8px;">
        <h3><?= htmlspecialchars($p['nombre']); ?></h3>
        <p style="font-weight:bold;">$<?= number_format($p['precio'],2); ?></p>
      </div>
    <?php endwhile; ?>
  </div>
</div>
</body>
</html>