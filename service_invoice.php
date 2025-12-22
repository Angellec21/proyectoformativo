<?php
if (!isset($_SESSION)) session_start();
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/payment_helpers.php';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
  echo 'Order ID inválido';
  exit;
}

$order = fetch_one(
  $conexion,
  'SELECT o.*, CONCAT(c.first_name, " ", c.last_name) AS customer_name, c.email AS customer_email, c.phone AS customer_phone,
          d.device_type, d.brand, d.model, d.serial_number
   FROM service_orders o
   JOIN customers c ON c.customer_id = o.customer_id
   JOIN devices d ON d.device_id = o.device_id
   WHERE o.order_id = :id',
  [':id' => $order_id]
);

if (!$order) {
  echo 'Orden no encontrada';
  exit;
}

$items = fetch_all(
  $conexion,
  'SELECT i.*, s.name AS service_name FROM service_order_items i LEFT JOIN service_catalog s ON s.service_id = i.service_id WHERE i.order_id = :id ORDER BY i.item_id',
  [':id' => $order_id]
);

$paymentCols = payment_detail_columns($conexion);
$paymentSelect = payment_select_fields($paymentCols);
$payments = fetch_all(
  $conexion,
  'SELECT ' . $paymentSelect . ' FROM payments WHERE order_id = :id ORDER BY payment_date ASC, payment_id ASC',
  [':id' => $order_id]
);

$itemsSubtotal = 0.0;
foreach ($items as $it) {
  $itemsSubtotal += (float)($it['subtotal'] ?? 0);
}

$paidTotal = 0.0;
$changeTotal = 0.0;
foreach ($payments as $p) {
  $paidTotal += (float)$p['amount'];
  if (!empty($paymentCols['change_amount']) && isset($p['change_amount'])) {
    $changeTotal += (float)$p['change_amount'];
  } elseif (!empty($paymentCols['received_amount']) && isset($p['received_amount'])) {
    $diff = (float)$p['received_amount'] - (float)$p['amount'];
    if ($diff > 0) { $changeTotal += $diff; }
  }
}

$estimated = (float)($order['estimated_total'] ?? 0);
$balance = max($estimated - $paidTotal, 0);

$format = isset($_GET['format']) ? $_GET['format'] : 'html';

$company_name = 'Tech Service';
$company_address = 'Sucursal principal';
$company_phone = '+591 00000000';
$company_email = 'soporte@techservice.local';

ob_start();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Factura Orden #<?php echo (int)$order_id; ?></title>
<style>
  body { font-family: Arial, Helvetica, sans-serif; color:#1f2933; margin:0; padding:18px; }
  .invoice { max-width: 900px; margin: 0 auto; border:1px solid #e5e7eb; padding:20px; border-radius:6px; }
  h1 { margin:0 0 6px 0; }
  table { width:100%; border-collapse: collapse; margin-top:12px; }
  th, td { border:1px solid #e5e7eb; padding:8px; text-align:left; }
  thead th { background:#f3f4f6; }
  .meta, .flex { display:flex; gap:12px; flex-wrap:wrap; }
  .badge { padding:4px 8px; border-radius:4px; background:#eef2ff; color:#4338ca; font-size:12px; }
  .right { text-align:right; }
  .totals { max-width:320px; margin-left:auto; border:1px solid #e5e7eb; border-radius:6px; padding:12px; }
  .totals div { display:flex; justify-content:space-between; margin-bottom:6px; }
  .small { color:#6b7280; font-size:12px; }
  @media print { body { padding:0; } .no-print { display:none; } }
</style>
</head>
<body>
  <div class="invoice">
    <div class="flex" style="justify-content:space-between; align-items:flex-start;">
      <div>
        <h1>Factura de servicio</h1>
        <div class="small">Orden #<?php echo (int)$order_id; ?> · Creada: <?php echo htmlspecialchars(substr($order['created_at'],0,19)); ?></div>
        <div class="small">Estado: <span class="badge"><?php echo htmlspecialchars($order['status']); ?></span></div>
      </div>
      <div style="text-align:right;">
        <div style="font-weight:700;"><?php echo htmlspecialchars($company_name); ?></div>
        <div class="small"><?php echo htmlspecialchars($company_address); ?></div>
        <div class="small"><?php echo htmlspecialchars($company_phone); ?></div>
        <div class="small"><?php echo htmlspecialchars($company_email); ?></div>
      </div>
    </div>

    <div class="flex" style="margin-top:12px;">
      <div style="flex:1; min-width:240px;">
        <div style="font-weight:700;">Cliente</div>
        <div><?php echo htmlspecialchars($order['customer_name']); ?></div>
        <div class="small"><?php echo htmlspecialchars($order['customer_email'] ?? ''); ?></div>
        <div class="small"><?php echo htmlspecialchars($order['customer_phone'] ?? ''); ?></div>
      </div>
      <div style="flex:1; min-width:240px;">
        <div style="font-weight:700;">Equipo</div>
        <div><?php echo htmlspecialchars($order['device_type'] . ' ' . ($order['brand'] ?? '')); ?></div>
        <div class="small">Modelo: <?php echo htmlspecialchars($order['model'] ?? ''); ?></div>
        <div class="small">Serie: <?php echo htmlspecialchars($order['serial_number'] ?? ''); ?></div>
      </div>
    </div>

    <div style="margin-top:12px;">
      <div style="font-weight:700;">Detalle de servicios</div>
      <table>
        <thead>
          <tr><th>Servicio</th><th>Cant.</th><th class="right">Unitario</th><th class="right">Subtotal</th></tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td><?php echo htmlspecialchars($it['service_name'] ?? $it['item_type']); ?></td>
              <td><?php echo (int)$it['quantity']; ?></td>
              <td class="right">Bs <?php echo number_format((float)$it['unit_price'],2); ?></td>
              <td class="right">Bs <?php echo number_format((float)$it['subtotal'],2); ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($items)): ?>
            <tr><td colspan="4">Sin ítems registrados.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div style="margin-top:12px;">
      <div style="font-weight:700;">Pagos</div>
      <table>
        <thead>
          <tr>
            <th>Método</th>
            <th class="right">Monto</th>
            <?php if (!empty($paymentCols['received_amount'])): ?><th class="right">Recibido</th><?php endif; ?>
            <?php if (!empty($paymentCols['change_amount'])): ?><th class="right">Cambio</th><?php endif; ?>
            <?php if (!empty($paymentCols['reference'])): ?><th>Referencia</th><?php endif; ?>
            <th>Fecha</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($payments as $p): ?>
            <tr>
              <td><?php echo htmlspecialchars($p['payment_method']); ?></td>
              <td class="right">Bs <?php echo number_format((float)$p['amount'],2); ?></td>
              <?php if (!empty($paymentCols['received_amount'])): ?>
                <td class="right"><?php echo isset($p['received_amount']) ? 'Bs ' . number_format((float)$p['received_amount'],2) : '—'; ?></td>
              <?php endif; ?>
              <?php if (!empty($paymentCols['change_amount'])): ?>
                <td class="right"><?php echo isset($p['change_amount']) ? 'Bs ' . number_format((float)$p['change_amount'],2) : 'Bs ' . number_format(max(0, (float)($p['received_amount'] ?? 0) - (float)$p['amount']),2); ?></td>
              <?php endif; ?>
              <?php if (!empty($paymentCols['reference'])): ?>
                <td><?php echo htmlspecialchars($p['reference'] ?? ''); ?></td>
              <?php endif; ?>
              <td><?php echo htmlspecialchars($p['payment_date']); ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($payments)): ?>
            <tr><td colspan="<?php echo 3 + (!empty($paymentCols['received_amount']) ? 1 : 0) + (!empty($paymentCols['change_amount']) ? 1 : 0) + (!empty($paymentCols['reference']) ? 1 : 0); ?>">Sin pagos registrados.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="totals" style="margin-top:16px;">
      <div><span>Subtotal servicios</span><span>Bs <?php echo number_format($itemsSubtotal,2); ?></span></div>
      <div><span>Total estimado</span><span>Bs <?php echo number_format($estimated,2); ?></span></div>
      <div><span>Pagado</span><span>Bs <?php echo number_format($paidTotal,2); ?></span></div>
      <div><span>Cambio entregado</span><span>Bs <?php echo number_format($changeTotal,2); ?></span></div>
      <div style="font-weight:700;"><span>Saldo</span><span>Bs <?php echo number_format($balance,2); ?></span></div>
    </div>

    <div class="small" style="margin-top:10px;">Problema reportado: <?php echo htmlspecialchars($order['issue_description'] ?? ''); ?></div>

    <div class="no-print" style="margin-top:12px; display:flex; gap:8px;">
      <a class="button" style="padding:8px 12px; background:#111827; color:#fff; text-decoration:none; border-radius:6px;" href="service_invoice.php?order_id=<?php echo (int)$order_id; ?>&format=pdf" target="_blank">Descargar PDF</a>
      <button onclick="window.print()" style="padding:8px 12px; border:1px solid #d1d5db; background:#fff; border-radius:6px; cursor:pointer;">Imprimir</button>
    </div>
  </div>
</body>
</html>
<?php
$html = ob_get_clean();

if ($format === 'pdf') {
  $dompdfPath = __DIR__ . '/libs/dompdf/autoload.inc.php';
  if (!file_exists($dompdfPath)) {
    echo 'dompdf no encontrado';
    exit;
  }
  require_once $dompdfPath;
  $dompdf = new Dompdf\Dompdf();
  $dompdf->loadHtml($html);
  $dompdf->setPaper('A4', 'portrait');
  $dompdf->render();
  $dompdf->stream('factura_orden_' . $order_id . '.pdf', ['Attachment' => true]);
  exit;
}

echo $html;
?>
