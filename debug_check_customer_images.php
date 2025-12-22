<?php
include __DIR__ . '/bd.php';

echo "Checking customer images in DB and filesystem...\n";
$st = $conexion->prepare('SELECT customer_id, first_name, last_name, imagen FROM customers ORDER BY customer_id DESC');
$st->execute();
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    $img = $r['imagen'];
    $path = __DIR__ . '/secciones/Clientes/img/' . $img;
    $exists = ($img !== '' && file_exists($path)) ? 'YES' : 'NO';
    echo sprintf("ID:%s  %s %s  image:".($img===''?"(empty)":"%s")."  exists:%s\n", $r['customer_id'], $r['first_name'], $r['last_name'], $img, $exists);
}

?>
