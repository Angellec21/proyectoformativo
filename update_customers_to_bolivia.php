<?php
require 'bd.php';

echo "=== ACTUALIZACIÓN DE CLIENTES A BOLIVIA ===\n\n";

// Obtener clientes actuales
$stmt = $conexion->query('SELECT customer_id, first_name, last_name, phone, city FROM customers');
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Clientes a actualizar: " . count($customers) . "\n";
echo str_repeat("-", 80) . "\n";

$ciudadesBolivia = [
    'Lima' => 'La Paz',
    'Arequipa' => 'Santa Cruz de la Sierra',
    'Trujillo' => 'Cochabamba',
    'Cusco' => 'Sucre',
    'Chiclayo' => 'Oruro'
];

foreach ($customers as $c) {
    $id = $c['customer_id'];
    $nombre = $c['first_name'] . ' ' . $c['last_name'];
    $phone = $c['phone'];
    $city = $c['city'];
    
    // Actualizar teléfono: agregar +591 si no tiene código de país
    $newPhone = $phone;
    if (!empty($phone) && !preg_match('/^\+/', $phone)) {
        $newPhone = '+591 ' . $phone;
    }
    
    // Actualizar ciudad a boliviana
    $newCity = $city;
    if (isset($ciudadesBolivia[$city])) {
        $newCity = $ciudadesBolivia[$city];
    } else if ($city === 'Lima' || empty($city)) {
        $newCity = 'La Paz';
    }
    
    // Actualizar en base de datos
    $update = $conexion->prepare('UPDATE customers SET phone = ?, city = ? WHERE customer_id = ?');
    $update->execute([$newPhone, $newCity, $id]);
    
    echo "✓ Cliente #{$id}: {$nombre}\n";
    echo "  Teléfono: {$phone} → {$newPhone}\n";
    echo "  Ciudad: {$city} → {$newCity}\n";
    echo str_repeat("-", 80) . "\n";
}

echo "\n✓ Actualización completada!\n";

// Mostrar resultado final
echo "\nClientes actualizados:\n";
echo str_repeat("=", 80) . "\n";
$stmt2 = $conexion->query('SELECT customer_id, first_name, last_name, phone, city FROM customers');
$updated = $stmt2->fetchAll(PDO::FETCH_ASSOC);

foreach ($updated as $c) {
    echo "ID: {$c['customer_id']} | ";
    echo "Nombre: {$c['first_name']} {$c['last_name']} | ";
    echo "Teléfono: {$c['phone']} | ";
    echo "Ciudad: {$c['city']}\n";
}
