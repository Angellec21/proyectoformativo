<?php
// Plantilla de configuración de base de datos
// INSTRUCCIONES: Copia este archivo como 'bd.php' y ajusta las credenciales

$servidor = '127.0.0.1';
$puerto = '3306';
$basededatos = 'tech_service';
$usuario = 'root';
$contrasenia = ''; // Cambia esto en producción

try {
    $dsn = "mysql:host={$servidor};port={$puerto};dbname={$basededatos};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $conexion = new PDO($dsn, $usuario, $contrasenia, $options);
} catch (PDOException $ex) {
    // Mostrar mensaje simple en desarrollo
    echo 'Error de conexión a la base de datos: ' . $ex->getMessage();
    exit;
}
?>
