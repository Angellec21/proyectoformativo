<?php
require 'bd.php';

echo "=== PRODUCTOS ACTUALES ===\n\n";
$stmt = $conexion->query('SELECT product_id, name, model, category_id, image_url FROM products ORDER BY product_id');
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($products)) {
    echo "No hay productos en el sistema.\n";
} else {
    foreach ($products as $p) {
        echo "ID: {$p['product_id']} | ";
        echo "Nombre: {$p['name']} | ";
        echo "Modelo: {$p['model']} | ";
        echo "Categoría: {$p['category_id']} | ";
        echo "Imagen: " . ($p['image_url'] ?? 'Sin imagen') . "\n";
    }
    echo "\nTotal: " . count($products) . " producto(s)\n";
}

echo "\n=== CATEGORÍAS ===\n\n";
$stmt2 = $conexion->query('SELECT category_id, name FROM categories ORDER BY category_id');
$categories = $stmt2->fetchAll(PDO::FETCH_ASSOC);

foreach ($categories as $c) {
    echo "ID: {$c['category_id']} | Nombre: {$c['name']}\n";
}
