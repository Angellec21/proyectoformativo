<?php
require 'bd.php';

echo "=== DESCARGANDO Y ASIGNANDO IMÁGENES A PRODUCTOS ===\n\n";

// Mapeo de productos a URLs de imágenes de ejemplo
$productImages = [
    1 => [ // SSD Kingston 480GB
        'url' => 'https://images.unsplash.com/photo-1625948515291-69613efd103f?w=400&h=400&fit=crop',
        'filename' => 'ssd_kingston_480gb.jpg'
    ],
    2 => [ // Memoria RAM 8GB DDR4
        'url' => 'https://images.unsplash.com/photo-1591799264318-7e6ef8ddb7ea?w=400&h=400&fit=crop',
        'filename' => 'ram_8gb_ddr4.jpg'
    ],
    3 => [ // Teclado Mecánico
        'url' => 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=400&h=400&fit=crop',
        'filename' => 'teclado_mecanico.jpg'
    ],
    4 => [ // Monitor 24" IPS
        'url' => 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=400&h=400&fit=crop',
        'filename' => 'monitor_24_ips.jpg'
    ],
    5 => [ // PC Armada
        'url' => 'https://images.unsplash.com/photo-1587202372634-32705e3bf49c?w=400&h=400&fit=crop',
        'filename' => 'pc_armada_gaming.jpg'
    ]
];

$imgDir = __DIR__ . '/secciones/Productos/img/';

// Verificar que el directorio existe
if (!is_dir($imgDir)) {
    mkdir($imgDir, 0755, true);
}

foreach ($productImages as $productId => $imgData) {
    $filename = $imgData['filename'];
    $url = $imgData['url'];
    $filepath = $imgDir . $filename;
    
    echo "Producto #{$productId}: ";
    
    // Intentar descargar imagen
    $imageContent = @file_get_contents($url);
    
    if ($imageContent !== false) {
        file_put_contents($filepath, $imageContent);
        
        // Actualizar base de datos
        $stmt = $conexion->prepare('UPDATE products SET image_url = ? WHERE product_id = ?');
        $stmt->execute([$filename, $productId]);
        
        echo "✓ Imagen descargada y asignada: {$filename}\n";
    } else {
        echo "✗ Error al descargar imagen desde {$url}\n";
    }
}

echo "\n=== PRODUCTOS ACTUALIZADOS ===\n\n";
$stmt = $conexion->query('SELECT product_id, name, image_url FROM products ORDER BY product_id');
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($products as $p) {
    echo "ID: {$p['product_id']} | {$p['name']} | Imagen: {$p['image_url']}\n";
}

echo "\n✓ Proceso completado!\n";
