<?php
if (!isset($_SESSION)) session_start();
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/payment_helpers.php';

$embed = isset($_GET['embed']) ? true : false; // cuando se carga en modal
$url_base = "http://localhost/ProyectoFinal_Servicio/Proyecto_Tech_Service.github.io/"; // para estilos en modo embed
$rol = $_SESSION['rol'] ?? 'guest';
$paymentCols = payment_detail_columns($conexion);

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
  echo 'Order ID inválido';
  exit;
}

// Obtener orden
$order = fetch_one($conexion, 'SELECT o.*, CONCAT(c.first_name, " ", c.last_name) AS customer_name, d.device_type, d.brand, d.model FROM service_orders o JOIN customers c ON c.customer_id=o.customer_id JOIN devices d ON d.device_id=o.device_id WHERE o.order_id = :id', [':id'=>$order_id]);
if (!$order) { echo 'Orden no encontrada'; exit; }

function recalc_order_total(PDO $db, int $orderId): void {
  $sum = fetch_one($db, 'SELECT SUM(subtotal) AS s FROM service_order_items WHERE order_id = :id', [':id'=>$orderId]);
  $subtotal = (float)($sum['s'] ?? 0);
  // estimated_total = items + labor_rate
  $upd = $db->prepare('UPDATE service_orders SET estimated_total = :t WHERE order_id = :id');
  $upd->execute([':t' => $subtotal + (float)$GLOBALS['order']['labor_rate'], ':id' => $orderId]);
}

// Acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $isStaff = in_array($rol, ['admin','technician','frontdesk'], true);
  if ($action === 'add_service' && $isStaff) {
    $service_id = (int)($_POST['service_id'] ?? 0);
    $qty = max(1, (int)($_POST['quantity'] ?? 1));
    $unit = (float)($_POST['unit_price'] ?? 0);
    if ($service_id > 0 && $qty > 0 && $unit >= 0) {
      $ins = $conexion->prepare('INSERT INTO service_order_items (order_id, item_type, service_id, quantity, unit_price) VALUES (:o, "service", :s, :q, :u)');
      $ins->execute([':o'=>$order_id, ':s'=>$service_id, ':q'=>$qty, ':u'=>$unit]);
      recalc_order_total($conexion, $order_id);
      $redir = 'orders_items.php?order_id=' . $order_id . ($embed ? '&embed=1' : '');
      header('Location: ' . $redir);
      exit;
    }
  }
  if ($action === 'update_status' && $isStaff) {
    $newStatus = $_POST['status'] ?? '';
    $allowed = ['received','diagnosing','in_repair','waiting_parts','ready','delivered','cancelled'];
    if (in_array($newStatus, $allowed, true)) {
      $up = $conexion->prepare('UPDATE service_orders SET status = :s WHERE order_id = :id');
      $up->execute([':s'=>$newStatus, ':id'=>$order_id]);
    }
    $redir = 'orders_items.php?order_id=' . $order_id . ($embed ? '&embed=1' : '');
    header('Location: ' . $redir);
    exit;
  }
  if ($action === 'add_payment' && $isStaff) {
    $amount = (float)($_POST['amount'] ?? 0);
    $method = $_POST['payment_method'] ?? 'cash';
    $allowed = ['cash','card','transfer','yape','plin'];
    if ($amount > 0 && in_array($method, $allowed, true)) {
      $paidNow = (float)fetch_one($conexion, 'SELECT COALESCE(SUM(amount),0) AS t FROM payments WHERE order_id = :id', [':id'=>$order_id])['t'];
      $balanceNow = max((float)$order['estimated_total'] - $paidNow, 0);

      $charge = $balanceNow > 0 ? min($amount, $balanceNow) : 0;
      if ($charge <= 0) {
        $redir = 'orders_items.php?order_id=' . $order_id . ($embed ? '&embed=1' : '');
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
    $redir = 'orders_items.php?order_id=' . $order_id . ($embed ? '&embed=1' : '');
    header('Location: ' . $redir);
    exit;
  }
  if ($action === 'delete_item' && $isStaff) {
    $item_id = (int)($_POST['item_id'] ?? 0);
    if ($item_id > 0) {
      $del = $conexion->prepare('DELETE FROM service_order_items WHERE item_id = :i AND order_id = :o');
      $del->execute([':i'=>$item_id, ':o'=>$order_id]);
      recalc_order_total($conexion, $order_id);
      $redir = 'orders_items.php?order_id=' . $order_id . ($embed ? '&embed=1' : '');
      header('Location: ' . $redir);
      exit;
    }
  }
}

$services = fetch_all($conexion, 'SELECT service_id, name, base_price FROM service_catalog ORDER BY name');
$items = fetch_all($conexion, 'SELECT i.item_id, i.item_type, i.quantity, i.unit_price, i.subtotal, s.name AS service_name FROM service_order_items i LEFT JOIN service_catalog s ON s.service_id = i.service_id WHERE i.order_id = :id ORDER BY i.item_id DESC', [':id'=>$order_id]);
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
$balance = max((float)$order['estimated_total'] - $paidTotal, 0);

if (!$embed) {
  include __DIR__ . '/templates/header.php';
} else {
  $theme = $_SESSION['theme'] ?? 'dark';
  echo '<!doctype html><html lang="es"><head>';
  echo '<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
  echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">';
  echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">';
  echo '<link rel="stylesheet" href="' . $url_base . 'assets/styles.css">';
  if ($theme === 'light') {
    echo '<link rel="stylesheet" href="' . $url_base . 'assets/theme-light.css">';
  }
  echo '</head><body class="' . ($theme === 'light' ? 'light-theme' : '') . '" style="background:var(--bg);">';
  echo '<div class="container py-3" style="max-width:1100px;">';
}
?>

<div class="card d-flex flex-column flex-md-row align-items-md-center justify-content-between">
  <div>
    <h2 class="mb-1">Orden #<?= (int)$order['order_id'] ?> · <?= htmlspecialchars($order['customer_name']) ?></h2>
    <div class="text-muted">Equipo: <?= htmlspecialchars($order['device_type'] . ' ' . ($order['brand'] ?? '') . ' ' . ($order['model'] ?? '')) ?></div>
  </div>
  <div class="d-flex gap-2 mt-3 mt-md-0">
    <?php if (!$embed): ?>
      <a class="button" href="orders.php">Volver a órdenes</a>
    <?php endif; ?>
  </div>
</div>

<?php if (!$embed): ?>
<div class="card" style="margin-top:18px;">
  <h2>Estado y pagos</h2>
  <div class="row g-3">
    <div class="col-md-4">
      <form method="post" class="d-flex align-items-end gap-2">
        <input type="hidden" name="action" value="update_status">
        <div class="flex-grow-1">
          <div class="label">Estado</div>
          <select name="status" class="input">
            <?php
              $opts = [
                'received' => 'Recibida',
                'diagnosing' => 'Diagnosticando',
                'in_repair' => 'En reparación',
                'waiting_parts' => 'Esperando piezas',
                'ready' => 'Lista',
                'delivered' => 'Entregada',
                'cancelled' => 'Cancelada'
              ];
              foreach ($opts as $k=>$label) {
                $sel = $order['status'] === $k ? 'selected' : '';
                echo "<option value=\"{$k}\" {$sel}>{$label}</option>";
              }
            ?>
          </select>
        </div>
        <button class="button" style="padding:10px 14px;">Actualizar</button>
      </form>
    </div>
    <div class="col-md-8">
      <form method="post" class="d-flex flex-wrap gap-2 align-items-end">
        <input type="hidden" name="action" value="add_payment">
        <div>
          <div class="label">Monto a cobrar (Bs)</div>
          <input type="number" step="0.01" min="0.01" name="amount" class="input" required value="<?= $balance > 0 ? number_format($balance,2,'.','') : '' ?>">
        </div>
        <div>
          <div class="label">Monto recibido (Bs)</div>
          <input type="number" step="0.01" min="0" name="received_amount" class="input" value="<?= $balance > 0 ? number_format($balance,2,'.','') : '' ?>">
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
        <div style="flex:1; min-width:200px;">
          <div class="label">Notas</div>
          <input type="text" name="notes" class="input" placeholder="Observaciones (opcional)">
        </div>
        <div>
          <button class="button" style="margin-top:28px;">Registrar pago</button>
        </div>
        <div class="ms-auto" style="text-align:right; min-width:200px;">
          <div class="text-muted">Total estimado</div>
          <div style="font-weight:700;">Bs <?= number_format((float)$order['estimated_total'],2) ?></div>
          <div class="text-muted">Pagado</div>
          <div style="font-weight:700; color:var(--success, #5cb85c);">Bs <?= number_format($paidTotal,2) ?></div>
          <div class="text-muted">Saldo</div>
          <div style="font-weight:700; color:var(--danger, #dc3545);">Bs <?= number_format($balance,2) ?></div>
          <div class="text-muted" style="margin-top:4px;">Cambio estimado</div>
          <div id="changePreview" style="font-weight:700; color:var(--info,#0d6efd);">Bs 0.00</div>
        </div>
      </form>
    </div>
  </div>
  <?php if ($payments): ?>
    <div class="table-responsive" style="margin-top:12px;">
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
    </div>
    <div class="text-muted" style="margin-top:6px;">Cambio entregado: Bs <?= number_format($changeTotal,2) ?></div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if (in_array($rol, ['admin','technician','frontdesk'], true)): ?>
<div class="card" style="margin-top:18px;">
  <h2>Agregar servicio</h2>
  <form method="post" class="d-flex flex-wrap gap-2 align-items-end">
    <input type="hidden" name="action" value="add_service">
    <div>
      <div class="label">Servicio</div>
      <select name="service_id" id="service_id" class="input" required>
        <option value="">Selecciona</option>
        <?php foreach ($services as $s): ?>
          <option value="<?= (int)$s['service_id'] ?>" data-price="<?= (float)$s['base_price'] ?>"><?= htmlspecialchars($s['name']) ?> (Bs <?= number_format((float)$s['base_price'],2) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <div class="label">Cantidad</div>
      <input type="number" name="quantity" class="input" value="1" min="1" required>
    </div>
    <div>
      <div class="label">Precio unitario (Bs)</div>
      <input type="number" step="0.01" name="unit_price" id="unit_price" class="input" value="0" required>
    </div>
    <div>
      <button class="button" style="margin-top:28px;">Añadir</button>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="card" style="margin-top:18px;">
  <h2>Ítems de la orden</h2>
  <table class="table">
    <thead><tr><th>ID</th><th>Tipo</th><th>Descripción</th><th>Cant.</th><th>Unitario</th><th>Subtotal</th><?php if (in_array($rol, ['admin','technician','frontdesk'], true)): ?><th></th><?php endif; ?></tr></thead>
    <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= (int)$it['item_id'] ?></td>
          <td><?= htmlspecialchars($it['item_type']) ?></td>
          <td><?= htmlspecialchars($it['service_name'] ?? '-') ?></td>
          <td><?= (int)$it['quantity'] ?></td>
          <td>Bs <?= number_format((float)$it['unit_price'],2) ?></td>
          <td>Bs <?= number_format((float)$it['subtotal'],2) ?></td>
          <?php if (in_array($rol, ['admin','technician','frontdesk'], true)): ?>
          <td>
            <form method="post" style="display:inline">
              <input type="hidden" name="action" value="delete_item">
              <input type="hidden" name="item_id" value="<?= (int)$it['item_id'] ?>">
              <button class="button" style="padding:6px 10px;" onclick="return confirm('¿Eliminar ítem?')">Eliminar</button>
            </form>
          </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
  // Precargar precio por defecto al seleccionar servicio
  document.getElementById('service_id').addEventListener('change', function() {
    var price = this.options[this.selectedIndex].getAttribute('data-price') || '0';
    document.getElementById('unit_price').value = price;
  });

  // Calcular cambio estimado en el formulario de pago
  (function(){
    var amountInput = document.querySelector('input[name="amount"]');
    var receivedInput = document.querySelector('input[name="received_amount"]');
    var methodSelect = document.querySelector('select[name="payment_method"]');
    var changeEl = document.getElementById('changePreview');
    if (!amountInput || !receivedInput || !methodSelect || !changeEl) { return; }
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

<?php
if (!$embed) {
  include __DIR__ . '/templates/footer.php';
} else {
  echo '</div></body></html>'; // cierre container y documento embed
}
?>
