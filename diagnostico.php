<?php
echo "=== DIAGNÓSTICO DE PERMISOS Y DIRECTORIOS ===\n\n";

$dirs = [
    'secciones/Productos/img' => __DIR__ . '/secciones/Productos/img',
];

foreach ($dirs as $name => $path) {
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);
    $perms = $exists ? substr(sprintf('%o', fileperms($path)), -4) : 'N/A';
    
    echo "Directorio: $name\n";
    echo "  Ruta: $path\n";
    echo "  Existe: " . ($exists ? 'SÍ' : 'NO') . "\n";
    echo "  Escribible: " . ($writable ? 'SÍ' : 'NO') . "\n";
    echo "  Permisos: $perms\n";
    
    if ($exists) {
        $files = array_diff(scandir($path), ['.', '..']);
        echo "  Archivos (" . count($files) . "): " . implode(', ', array_slice($files, 0, 5)) . "\n";
    }
    echo "\n";
}

echo "=== PHP UPLOAD ===\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "file_uploads: " . (ini_get('file_uploads') ? 'SÍ' : 'NO') . "\n";
