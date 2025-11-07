<?php
// Habilitar reporte de errores
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);
ini_set('error_log', __DIR__ . '/error.log');

// Incluir conexión
include_once($_SERVER['DOCUMENT_ROOT'] . '/supermercado/conexion.php/conexion.php');
session_start();

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /supermercado/index.php/index.php");
    exit();
}

// Sanitizar entrada
$usuario = isset($_POST['usuario']) ? $conexion->real_escape_string($_POST['usuario']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (empty($usuario) || empty($password)) {
    error_log("Intento de login con campos vacíos");
    echo "<script>alert('Por favor completa todos los campos'); window.location='/supermercado/index.php/index.php';</script>";
    exit();
}

try {
    // Buscar usuario
    $stmt = $conexion->prepare("SELECT id, usuario, nombre, password, rol FROM usuarios WHERE usuario = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception("Error preparando la consulta: " . $conexion->error);
    }
    
    $stmt->bind_param("s", $usuario);
    if (!$stmt->execute()) {
        throw new Exception("Error ejecutando la consulta: " . $stmt->error);
    }
    
    $resultado = $stmt->get_result();
    
    if ($resultado && $resultado->num_rows > 0) {
        $usuario_data = $resultado->fetch_assoc();
        
        // Para la primera vez que se usa la contraseña en texto plano
        if (strlen($usuario_data['password']) < 60 && $usuario_data['password'] === $password) {
            // Actualizar a hash
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $update = $conexion->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            if (!$update) {
                throw new Exception("Error preparando actualización de contraseña: " . $conexion->error);
            }
            
            $update->bind_param("si", $hash, $usuario_data['id']);
            if (!$update->execute()) {
                throw new Exception("Error actualizando contraseña: " . $update->error);
            }
            
            $autenticado = true;
        } else {
            // Verificar contraseña hasheada
            $autenticado = password_verify($password, $usuario_data['password']);
        }
        
        if ($autenticado) {
            // Actualizar última sesión
            $update_sesion = $conexion->prepare("UPDATE usuarios SET ultima_sesion = NOW() WHERE id = ?");
            if ($update_sesion) {
                $update_sesion->bind_param("i", $usuario_data['id']);
                $update_sesion->execute();
            }
            
            // Establecer variables de sesión
            $_SESSION['usuario'] = $usuario_data['usuario'];
            $_SESSION['nombre'] = $usuario_data['nombre'];
            $_SESSION['rol'] = $usuario_data['rol'];
            $_SESSION['id'] = $usuario_data['id'];
            
            // Registrar en logs
            $log_stmt = $conexion->prepare("INSERT INTO logs (id_usuario, accion, descripcion) VALUES (?, 'login', 'Inicio de sesión exitoso')");
            if ($log_stmt) {
                $log_stmt->bind_param("i", $usuario_data['id']);
                $log_stmt->execute();
            }
            
            error_log("Login exitoso: Usuario={$usuario_data['usuario']}, Rol={$usuario_data['rol']}");
            header("Location: /supermercado/dashboard.php/dashboard.php");
            exit();
        } else {
            error_log("Contraseña incorrecta para usuario: {$usuario_data['usuario']}");
            echo "<script>alert('Usuario o contraseña incorrectos'); window.location='/supermercado/index.php/index.php';</script>";
            exit();
        }
    } else {
        error_log("Intento de login con usuario inexistente: {$usuario}");
        echo "<script>alert('Usuario o contraseña incorrectos'); window.location='/supermercado/index.php/index.php';</script>";
        exit();
    }
} catch (Exception $e) {
    error_log("Error en validar_login.php: " . $e->getMessage());
    echo "<script>alert('Error en el sistema. Por favor intente más tarde.'); window.location='/supermercado/index.php/index.php';</script>";
    exit();
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($update)) $update->close();
    if (isset($update_sesion)) $update_sesion->close();
    if (isset($log_stmt)) $log_stmt->close();
}
?>
