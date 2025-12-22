<?php
include 'bd.php';
echo "=== Órdenes ===\n";
$q_orders = $conexion->query('SELECT order_id, total_amount FROM orders ORDER BY order_id DESC LIMIT 5');
foreach ($q_orders->fetchAll(PDO::FETCH_ASSOC) as $o) {
    echo "Order #" . $o['order_id'] . " - Total: Bs " . $o['total_amount'] . "\n";
    $q_pay = $conexion->prepare('SELECT payment_id, amount, received_amount, change_amount FROM payments WHERE order_id = :id');
    $q_pay->execute([':id' => $o['order_id']]);
    $pays = $q_pay->fetchAll(PDO::FETCH_ASSOC);
    $total_paid = array_sum(array_column($pays, 'amount'));
    echo "  Pagos registrados: " . count($pays) . "\n";
    foreach ($pays as $p) {
        echo "    Payment #" . $p['payment_id'] . " - Amount: Bs " . $p['amount'] . " - Received: " . $p['received_amount'] . " - Change: " . $p['change_amount'] . "\n";
    }
    echo "  Total pagado: Bs " . $total_paid . "\n";
    echo "  Saldo: Bs " . max(0, $o['total_amount'] - $total_paid) . "\n\n";
}
?>
