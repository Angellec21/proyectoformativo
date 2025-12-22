<?php
if (!isset($_SESSION)) session_start();
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/payment_helpers.php';

$embed = isset($_GET['embed']) ? true : false;
$url_base = "http://localhost/ProyectoFinal_Servicio/Proyecto_Tech_Service.github.io/";
$rol = $_SESSION['rol'] ?? 'guest';
$isStaff = in_array($rol, ['admin','technician','frontdesk'], true);
$paymentCols = payment_detail_columns($conexion);

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) { echo 'Order ID inválido'; exit; }

$order = fetch_one($conexion, 'SELECT o.*, CONCAT(c.first_name, " ", c.last_name) AS customer_name FROM service_orders o JOIN customers c ON c.customer_id=o.customer_id WHERE o.order_id = :id', [':id'=>$order_id]);
if (!$order) { echo 'Orden no encontrada'; exit; }

$estTotal = (float)$order['estimated_total'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'add_payment' && $isStaff) {
    $amount = (float)($_POST['amount'] ?? 0);
    $method = $_POST['payment_method'] ?? 'cash';
    $allowed = ['cash','card','transfer','yape','plin'];
    if ($amount > 0 && in_array($method, $allowed, true)) {
      // Recalcular balance al momento del POST para evitar sobrepago
      $paidNow = (float)fetch_one($conexion, 'SELECT COALESCE(SUM(amount),0) AS t FROM payments WHERE order_id = :id', [':id'=>$order_id])['t'];
      $balanceNow = max($estTotal - $paidNow, 0);

      $charge = $balanceNow > 0 ? min($amount, $balanceNow) : 0;
      if ($charge <= 0) {
        $redir = 'payments.php?order_id=' . $order_id . ($embed ? '&embed=1' : '');
        header('Location: ' . $redir);
        exit;
      }

      $received = isset($_POST['received_amount']) ? (float)$_POST['received_amount'] : 0;
      if ($received <= 0) { $received = $charge; }
      $change = $received > $charge ? ($received - $charge) : 0;
      $reference = isset($_POST['reference']) ? substr(trim((string)$_POST['reference']), 0, 120) : '';
      $notes = isset($_POST['notes']) ? trim((string)$_POST['notes']) : '';

      $fields = ['order_id','amount','payment_method'];
      $placeholders = [':o',':a',':m'];
      $params = [':o'=>$order_id, ':a'=>$charge, ':m'=>$method];

      if (!empty($paymentCols['received_amount'])) {
        $fields[] = 'received_amount';
        $placeholders[] = ':r';
        $params[':r'] = $received;
      }
      if (!empty($paymentCols['change_amount'])) {
        $fields[] = 'change_amount';
        $placeholders[] = ':c';
        $params[':c'] = $change;
      }
      if (!empty($paymentCols['reference'])) {
        $fields[] = 'reference';
        $placeholders[] = ':ref';
        $params[':ref'] = $reference;
      }
      if (!empty($paymentCols['notes'])) {
        $fields[] = 'notes';
        $placeholders[] = ':n';
        $params[':n'] = $notes;
      }

      $sql = 'INSERT INTO payments (' . implode(',', $fields) . ') VALUES (' . implode(',', $placeholders) . ')';
      $ins = $conexion->prepare($sql);
      $ins->execute($params);
    }
    $redir = 'payments.php?order_id=' . $order_id . ($embed ? '&embed=1' : '');
    header('Location: ' . $redir);
    exit;
  }
}

$paymentSelect = payment_select_fields($paymentCols);
$payments = fetch_all($conexion, 'SELECT ' . $paymentSelect . ' FROM payments WHERE order_id = :id ORDER BY payment_date DESC, payment_id DESC', [':id'=>$order_id]);
$paidTotal = 0; $changeTotal = 0;
foreach ($payments as $p) {
  $paidTotal += (float)$p['amount'];
  if (!empty($paymentCols['change_amount']) && isset($p['change_amount'])) {
    $changeTotal += (float)$p['change_amount'];
  } elseif (!empty($paymentCols['received_amount']) && isset($p['received_amount'])) {
    $diff = (float)$p['received_amount'] - (float)$p['amount'];
    if ($diff > 0) { $changeTotal += $diff; }
  }
}
$balance = max($estTotal - $paidTotal, 0);

if (!$embed) {
  include __DIR__ . '/templates/header.php';
} else {
  $theme = $_SESSION['theme'] ?? 'dark';
  echo '<!doctype html><html lang="es"><head>';
  echo '<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
  echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">';
  echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">';
  echo '<link rel="stylesheet" href="' . $url_base . 'assets/styles.css">';
  if ($theme === 'light') echo '<link rel="stylesheet" href="' . $url_base . 'assets/theme-light.css">';
  echo '</head><body class="' . ($theme === 'light' ? 'light-theme' : '') . '" style="background:var(--bg);">';
  echo '<div class="container py-3" style="max-width:900px;">';
}
?>

<div class="card d-flex flex-column flex-md-row align-items-md-center justify-content-between">
    <h2 class="mb-1">Pago · Orden #<?= (int)$order['order_id'] ?> · <?= htmlspecialchars($order['customer_name']) ?></h2>
  <?php if (!$embed): ?>
    <a class="button" href="orders.php">Volver</a>
  <?php endif; ?>
</div>

<div class="card" style="margin-top:18px;">
  <h2>Registrar pago</h2>
  <?php if (in_array($rol, ['admin','technician','frontdesk'], true)): ?>
    <form method="post" class="d-flex flex-wrap gap-2 align-items-end">
      <input type="hidden" name="action" value="add_payment">
      <div>
        <div class="label">Monto a cobrar (Bs)</div>
        <input type="number" step="0.01" min="0.01" name="amount" class="input" required value="<?= max(0, $balance) > 0 ? number_format($balance,2,'.','') : '' ?>">
      </div>
      <div>
        <div class="label">Monto recibido (Bs)</div>
        <input type="number" step="0.01" min="0" name="received_amount" class="input" value="<?= max(0, $balance) > 0 ? number_format($balance,2,'.','') : '' ?>">
      </div>
      <div>
        <div class="label">Método</div>
        <select name="payment_method" class="input">
          <option value="cash">Efectivo</option>
          <option value="card">Tarjeta</option>
          <option value="transfer">Transferencia</option>
          <option value="yape">Yape</option>
          <option value="plin">Plin</option>
        </select>
      </div>
      <div>
        <div class="label">Referencia</div>
        <input type="text" name="reference" class="input" placeholder="Nro. de operación / voucher">
      </div>
      <div style="flex:1; min-width:220px;">
        <div class="label">Notas</div>
        <input type="text" name="notes" class="input" placeholder="Observaciones (opcional)">
      </div>
      <div>
        <button class="button" style="margin-top:28px;">Registrar pago</button>
      </div>
      <div class="ms-auto" style="text-align:right; min-width:260px;">
        <div class="text-muted">Total estimado</div>
        <div style="font-weight:700;">Bs <?= number_format($estTotal,2) ?></div>
        <div class="text-muted">Pagado</div>
        <div style="font-weight:700; color:var(--success, #5cb85c);">Bs <?= number_format($paidTotal,2) ?></div>
        <div class="text-muted">Saldo</div>
        <div style="font-weight:700; color:var(--danger, #dc3545);">Bs <?= number_format($balance,2) ?></div>
        <div class="text-muted" style="margin-top:4px;">Cambio estimado</div>
        <div id="changePreview" style="font-weight:700; color:var(--info,#0d6efd);">Bs 0.00</div>
      </div>
    </form>
  <?php else: ?>
    <div class="d-flex align-items-center" style="gap:12px;">
      <div class="text-muted">Total estimado</div>
      <div style="font-weight:700;">Bs <?= number_format($estTotal,2) ?></div>
      <div class="text-muted">Pagado</div>
      <div style="font-weight:700; color:var(--success, #5cb85c);">Bs <?= number_format($paidTotal,2) ?></div>
      <div class="text-muted">Saldo</div>
      <div style="font-weight:700; color:var(--danger, #dc3545);">Bs <?= number_format($balance,2) ?></div>
    </div>
  <?php endif; ?>
</div>

<?php if ($estTotal > 0 && $balance <= 0): ?>
<div class="card" style="margin-top:12px;">
  <h2>Factura</h2>
  <div class="d-flex flex-wrap align-items-center" style="gap:10px;">
    <div class="text-muted">Pago completado. Genera la factura detallada.</div>
    <a class="button" target="_blank" href="service_invoice.php?order_id=<?= (int)$order_id ?>">Ver factura</a>
    <a class="button" target="_blank" href="service_invoice.php?order_id=<?= (int)$order_id ?>&format=pdf">Descargar PDF</a>
  </div>
</div>
<?php endif; ?>

<?php if ($payments): ?>
<div class="card" style="margin-top:18px;">
  <h2>Pagos registrados</h2>
  <table class="table">
    <thead>
      <tr>
        <th>ID</th><th>Método</th><th>Monto</th>
        <?php if (!empty($paymentCols['received_amount'])): ?><th>Recibido</th><?php endif; ?>
        <?php if (!empty($paymentCols['change_amount'])): ?><th>Cambio</th><?php endif; ?>
        <?php if (!empty($paymentCols['reference'])): ?><th>Referencia</th><?php endif; ?>
        <?php if (!empty($paymentCols['notes'])): ?><th>Notas</th><?php endif; ?>
        <th>Fecha</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($payments as $p): ?>
        <tr>
          <td><?= (int)$p['payment_id'] ?></td>
          <td><?= htmlspecialchars($p['payment_method']) ?></td>
          <td>Bs <?= number_format((float)$p['amount'],2) ?></td>
          <?php if (!empty($paymentCols['received_amount'])): ?>
            <td><?= isset($p['received_amount']) ? 'Bs ' . number_format((float)$p['received_amount'],2) : '—' ?></td>
          <?php endif; ?>
          <?php if (!empty($paymentCols['change_amount'])): ?>
            <td><?= isset($p['change_amount']) ? 'Bs ' . number_format((float)$p['change_amount'],2) : 'Bs ' . number_format(max(0, (float)($p['received_amount'] ?? 0) - (float)$p['amount']),2) ?></td>
          <?php endif; ?>
          <?php if (!empty($paymentCols['reference'])): ?>
            <td><?= htmlspecialchars($p['reference'] ?? '') ?></td>
          <?php endif; ?>
          <?php if (!empty($paymentCols['notes'])): ?>
            <td><?= htmlspecialchars($p['notes'] ?? '') ?></td>
          <?php endif; ?>
          <td><?= htmlspecialchars($p['payment_date']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div class="text-muted" style="margin-top:6px;">Cambio entregado: Bs <?= number_format($changeTotal,2) ?></div>
</div>
<?php endif; ?>

<?php if ($isStaff): ?>
<script>
(function(){
  var amountInput = document.querySelector('input[name="amount"]');
  var receivedInput = document.querySelector('input[name="received_amount"]');
  var methodSelect = document.querySelector('select[name="payment_method"]');
  var changeEl = document.getElementById('changePreview');
  if (!changeEl || !amountInput || !receivedInput || !methodSelect) { return; }
  function updateChange(){
    var method = methodSelect.value;
    var amount = parseFloat(amountInput.value || '0') || 0;
    var received = parseFloat(receivedInput.value || '0') || 0;
    if (method !== 'cash') {
      changeEl.textContent = 'Bs 0.00';
      return;
    }
    var diff = received - amount;
    changeEl.textContent = 'Bs ' + (diff > 0 ? diff : 0).toFixed(2);
  }
  amountInput.addEventListener('input', updateChange);
  receivedInput.addEventListener('input', updateChange);
  methodSelect.addEventListener('change', updateChange);
  updateChange();
})();
</script>
<?php endif; ?>

<?php
if (!$embed) {
  include __DIR__ . '/templates/footer.php';
} else {
  echo '</div></body></html>';
}
?>
