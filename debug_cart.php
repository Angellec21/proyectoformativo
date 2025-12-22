<?php
include __DIR__ . '/bd.php';
session_start();
header('Content-Type: text/plain; charset=utf-8');

echo "=== DEBUG CARRITO ===\n";
echo "Session ID: " . session_id() . "\n\n";

// Buscar carrito
$sid = session_id();
$st = $conexion->prepare('SELECT cart_id FROM carts WHERE session_id = :s');
$st->execute([':s' => $sid]);
$cart = $st->fetch(PDO::FETCH_ASSOC);

if ($cart) {
    $cart_id = $cart['cart_id'];
    echo "Cart ID: " . $cart_id . "\n";
    
    // Contar items
    $q = $conexion->prepare('SELECT COUNT(*) FROM cart_items WHERE cart_id = :cid');
    $q->execute([':cid' => $cart_id]);
    $count = $q->fetchColumn();
    echo "Items en carrito: " . $count . "\n\n";
    
    // Mostrar items
    if ($count > 0) {
        $q2 = $conexion->prepare('SELECT ci.cart_item_id, ci.product_id, ci.quantity, ci.price, p.name AS product_name FROM cart_items ci LEFT JOIN products p ON p.product_id = ci.product_id WHERE ci.cart_id = :cid');
        $q2->execute([':cid' => $cart_id]);
        $items = $q2->fetchAll(PDO::FETCH_ASSOC);
        echo "Detalles:\n";
        foreach ($items as $it) {
            echo "  - ID: {$it['cart_item_id']}, Producto: {$it['product_name']} (#{$it['product_id']}), Cant: {$it['quantity']}, Precio: {$it['price']}\n";
        }
    }
} else {
    echo "No se encontró carrito para esta sesión\n";
    
    // Listar todos los carritos
    echo "\nCarritos existentes:\n";
    $all = $conexion->query('SELECT cart_id, session_id FROM carts LIMIT 10');
    foreach ($all as $c) {
        echo "  - Cart ID: {$c['cart_id']}, Session: {$c['session_id']}\n";
    }
}
?>
