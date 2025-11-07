<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Supermercado Fénix</title>
<link rel="stylesheet" href="/supermercado/css/estilos.css">
</head>
<body>
<div class="contenedor">
  <h1>🛒 Supermercado Fénix</h1>
  <p class="subtitulo">Calidad y frescura a tu alcance</p>
  <form action="/supermercado/validar_login.php/validar_login.php" method="POST" class="login-box">
    <input type="text" name="usuario" placeholder="Usuario" required>
    <input type="password" name="password" placeholder="Contraseña" required>
    <button type="submit">Ingresar</button>
    <a href="/supermercado/registro.php/registro.php" style="display:block;margin-top:10px;color:white;">¿No tienes cuenta? Regístrate</a>
  </form>
</div>
<script src="/supermercado/js/scripts.js"></script>
</body>
</html>
