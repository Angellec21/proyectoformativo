<?php
require 'bd.php';

// Intentar con la tabla 'usuarios' primero, luego 'users'
try {
    $stmt = $conexion->query('SELECT user_id, usuario, email, rol, password FROM usuarios ORDER BY user_id');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si falla, intentar con la tabla 'users'
    try {
        $stmt = $conexion->query('SELECT user_id, username as usuario, email, role as rol, password FROM users ORDER BY user_id');
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e2) {
        echo "Error: No se encontró la tabla de usuarios.\n";
        echo $e2->getMessage();
        exit;
    }
}

if (empty($users)) {
    echo "No hay usuarios registrados en el sistema.\n";
} else {
    echo "USUARIOS DEL SISTEMA:\n";
    echo str_repeat("=", 100) . "\n";
    foreach($users as $u) {
        echo sprintf('ID: %d | Usuario: %-15s | Email: %-30s | Rol: %-12s', 
            $u['user_id'], 
            $u['usuario'], 
            $u['email'], 
            $u['rol']
        ) . "\n";
        echo sprintf('     Password Hash: %s', $u['password']) . "\n";
        echo str_repeat("-", 100) . "\n";
    }
    echo "Total: " . count($users) . " usuario(s)\n";
}
