<?php
require 'bd.php';

$stmt = $conexion->query('SELECT customer_id, first_name, last_name, phone, city, state FROM customers ORDER BY customer_id');
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($customers)) {
    echo "No hay clientes registrados en el sistema.\n";
} else {
    echo "CLIENTES DEL SISTEMA:\n";
    echo str_repeat("=", 120) . "\n";
    foreach($customers as $c) {
        echo sprintf('ID: %d | Nombre: %-25s | Teléfono: %-20s | Ciudad: %-25s | Estado: %s', 
            $c['customer_id'], 
            $c['first_name'] . ' ' . $c['last_name'], 
            $c['phone'] ?? 'Sin teléfono', 
            $c['city'] ?? 'Sin ciudad',
            $c['state'] ?? 'Sin estado'
        ) . "\n";
    }
    echo str_repeat("=", 120) . "\n";
    echo "Total: " . count($customers) . " cliente(s)\n";
}
