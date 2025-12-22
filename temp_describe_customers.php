<?php
require 'bd.php';

echo "Estructura de la tabla customers:\n";
echo str_repeat("=", 80) . "\n";
$stmt = $conexion->query('DESCRIBE customers');
while($row = $stmt->fetch()) {
    echo sprintf("%-20s | %-15s | %-5s | %-5s\n", 
        $row['Field'], 
        $row['Type'], 
        $row['Null'], 
        $row['Key']
    );
}
echo str_repeat("=", 80) . "\n\n";

echo "Datos de clientes:\n";
echo str_repeat("=", 120) . "\n";
$stmt2 = $conexion->query('SELECT * FROM customers ORDER BY customer_id LIMIT 10');
$customers = $stmt2->fetchAll(PDO::FETCH_ASSOC);

if (empty($customers)) {
    echo "No hay clientes registrados.\n";
} else {
    foreach($customers as $c) {
        echo "ID: {$c['customer_id']} | ";
        echo "Nombre: {$c['first_name']} {$c['last_name']} | ";
        echo "Teléfono: " . ($c['phone'] ?? 'N/A') . " | ";
        echo "Ciudad: " . ($c['city'] ?? 'N/A') . "\n";
    }
    echo str_repeat("=", 120) . "\n";
    echo "Total: " . count($customers) . " cliente(s)\n";
}
