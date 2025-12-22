<?php
// Migration: añadir columnas de pago a la tabla `orders` si faltan.
// Usa bd.php para la conexión.
include __DIR__ . '/bd.php';

function column_exists($pdo, $table, $column) {
    $sql = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column";
    $st = $pdo->prepare($sql);
    $st->execute([':table' => $table, ':column' => $column]);
    return (int)$st->fetchColumn() > 0;
}

$table = 'orders';
$cols = [
    'payment_method' => "VARCHAR(50) DEFAULT NULL",
    'payment_status' => "VARCHAR(25) NOT NULL DEFAULT 'Pendiente'",
    'cancellation_reason' => "TEXT DEFAULT NULL",
];

$added = [];
foreach ($cols as $col => $definition) {
    try {
        if (!column_exists($conexion, $table, $col)) {
            $sql = "ALTER TABLE `" . $table . "` ADD COLUMN `" . $col . "` " . $definition;
            $conexion->exec($sql);
            $added[] = $col;
            echo "Added column: $col\n";
        } else {
            echo "Column exists: $col\n";
        }
    } catch (Exception $e) {
        echo "Error adding $col: " . $e->getMessage() . "\n";
    }
}

// Mostrar estructura resumida
try {
    $st = $conexion->query("DESCRIBE `$table`");
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    echo "\nTable structure for $table:\n";
    foreach ($rows as $r) {
        echo sprintf("%s \t %s\n", $r['Field'], $r['Type']);
    }
} catch (Exception $e) {
    echo "Cannot describe table: " . $e->getMessage() . "\n";
}

if (empty($added)) {
    echo "\nNo columns were added.\n";
} else {
    echo "\nColumns added: " . implode(',', $added) . "\n";
}

echo "Migration finished.\n";

?>
