<?php
// auth.php - proteger rutas: permite acceso si hay usuario logueado o invitado
if (session_status() === PHP_SESSION_NONE) session_start();

// Si ya estamos en login or register, no redirigir
$current = basename($_SERVER['PHP_SELF']);
if ($current === 'login.php' || $current === 'register.php' || $current === 'cerrar.php') {
    return;
}

if (empty($_SESSION['user_id']) && empty($_SESSION['guest'])) {
    header('Location: ' . dirname($_SERVER['SCRIPT_NAME']) . '/../../login.php');
    exit;
}

// Si hay usuario logueado, intentar cargar rol en sesión si no está presente
if (!empty($_SESSION['user_id']) && empty($_SESSION['rol'])) {
    try {
        require_once __DIR__ . '/bd.php';
        $stmt = $conexion->prepare('SELECT rol FROM usuarios WHERE user_id = :id LIMIT 1');
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($r && isset($r['rol'])) {
            $_SESSION['rol'] = $r['rol'];
        } else {
            // valor por defecto
            $_SESSION['rol'] = 'user';
        }
    } catch (Exception $e) {
        $_SESSION['rol'] = 'user';
    }
}

// Helper para comprobar rol desde código
function require_role($role) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_id']) || (empty($_SESSION['rol']) || $_SESSION['rol'] !== $role)) {
        header('HTTP/1.1 403 Forbidden');
        echo 'Acceso denegado.';
        exit;
    }
}

?>
