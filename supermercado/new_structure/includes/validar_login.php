<?php
require_once 'config.php';
require_once 'Database.php';

session_start();

// Verify POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Sanitize input
    $username = filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_STRING);
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        error_log("Login attempt with empty fields");
        $_SESSION['error'] = 'Por favor completa todos los campos';
        header("Location: ../index.php");
        exit();
    }

    // Find user
    $stmt = $conn->prepare("SELECT id, usuario, nombre, password, rol FROM usuarios WHERE usuario = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        $authenticated = false;

        // Handle plain text password (first time)
        if (strlen($user['password']) < 60 && $user['password'] === $password) {
            // Update to hash
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $update->execute([$hash, $user['id']]);
            $authenticated = true;
        } else {
            // Verify hashed password
            $authenticated = password_verify($password, $user['password']);
        }

        if ($authenticated) {
            // Update last session
            $update_session = $conn->prepare("UPDATE usuarios SET ultima_sesion = NOW() WHERE id = ?");
            $update_session->execute([$user['id']]);

            // Set session variables
            $_SESSION['usuario'] = $user['usuario'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['rol'] = $user['rol'];
            $_SESSION['id'] = $user['id'];

            // Log successful login
            $log_stmt = $conn->prepare("INSERT INTO logs (id_usuario, accion, descripcion) VALUES (?, 'login', 'Inicio de sesión exitoso')");
            $log_stmt->execute([$user['id']]);

            error_log("Successful login: User={$user['usuario']}, Role={$user['rol']}");
            header("Location: ../dashboard.php");
            exit();
        } else {
            error_log("Incorrect password for user: {$user['usuario']}");
            $_SESSION['error'] = 'Usuario o contraseña incorrectos';
            header("Location: ../index.php");
            exit();
        }
    } else {
        error_log("Login attempt with non-existent user: {$username}");
        $_SESSION['error'] = 'Usuario o contraseña incorrectos';
        header("Location: ../index.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error in validar_login.php: " . $e->getMessage());
    $_SESSION['error'] = 'Error del sistema. Por favor intente más tarde.';
    header("Location: ../index.php");
    exit();
}
?>