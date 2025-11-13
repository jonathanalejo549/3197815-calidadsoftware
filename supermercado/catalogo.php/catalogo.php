<?php
require_once __DIR__ . '/../conexion.php/conexion.php';

$stmt = $conexion->prepare("
    SELECT p.*, c.nombre AS categoria, pr.nombre AS proveedor
    FROM productos p
    LEFT JOIN categorias c ON p.id_categoria = c.id
    LEFT JOIN proveedores pr ON p.id_proveedor = pr.id
");
$stmt->execute();
$result = $stmt->get_result();
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
        $img_url = '/supermercado/img/placeholder.png';

        if (isset($p['imagen']) && empty($p['imagen']) === false) {
            $filename = basename($p['imagen']);
            $uploads_dir = realpath(__DIR__ . '/../uploads');

            if ($uploads_dir !== false) {
                $filesystem_path = $uploads_dir . DIRECTORY_SEPARATOR . $filename;
                if (file_exists($filesystem_path) === true) {
                    $img_url = '/supermercado/uploads/' . rawurlencode($filename);
                }
            }
        }

        $safe_img = htmlspecialchars($img_url, ENT_QUOTES, 'UTF-8');
        $safe_name = htmlspecialchars($p['nombre'] ?? '', ENT_QUOTES, 'UTF-8');
        $safe_alt = $safe_name;
        $safe_price = htmlspecialchars(number_format(floatval($p['precio'] ?? 0), 2), ENT_QUOTES, 'UTF-8');
        ?>
        <img src="<?= $safe_img; ?>" alt="<?= $safe_alt; ?>" style="width:100%;height:140px;object-fit:cover;border-radius:8px;">
        <h3><?= $safe_name; ?></h3>
        <p style="font-weight:bold;">$<?= $safe_price; ?></p>
      </div>
    <?php endwhile; ?>
  </div>
</div>
</body>
</html>
