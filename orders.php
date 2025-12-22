<?php
if (!isset($_SESSION)) session_start();
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/includes/helpers.php';
include __DIR__ . '/templates/header.php';

$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $customerId = (int) ($_POST['customer_id'] ?? 0);
  $deviceId = (int) ($_POST['device_id'] ?? 0);
  $branchId = $_POST['branch_id'] !== '' ? (int) $_POST['branch_id'] : null;
  $assignedId = $_POST['assigned_user_id'] !== '' ? (int) $_POST['assigned_user_id'] : null;
  $priority = $_POST['priority'] ?? 'normal';
  $issue = trim((string) ($_POST['issue_description'] ?? ''));
  $laborRate = isset($_POST['labor_rate']) ? (float)$_POST['labor_rate'] : 0.0;

    if ($customerId && $deviceId && $issue !== '') {
    $stmt = $conexion->prepare('INSERT INTO service_orders (customer_id, device_id, branch_id, assigned_user_id, status, priority, issue_description, labor_rate, estimated_total, approved) VALUES (:c,:d,:b,:a, "received", :p, :i, :lr, 0, 0)');
    $stmt->execute([
      ':c' => $customerId,
      ':d' => $deviceId,
      ':b' => $branchId,
      ':a' => $assignedId,
      ':p' => $priority,
      ':i' => $issue,
      ':lr' => $laborRate,
    ]);
    $message = ['type' => 'success', 'text' => 'Orden creada'];
  } else {
    $message = ['type' => 'error', 'text' => 'Faltan datos obligatorios'];
  }
}

$orders = fetch_all($conexion, 'SELECT o.order_id, o.status, o.priority, o.issue_description, o.created_at, o.estimated_total, c.first_name, c.last_name, d.device_type, d.brand, b.branch_name FROM service_orders o JOIN customers c ON o.customer_id=c.customer_id JOIN devices d ON o.device_id=d.device_id LEFT JOIN branches b ON o.branch_id=b.branch_id ORDER BY o.created_at DESC');
$customers = fetch_all($conexion, 'SELECT customer_id, first_name, last_name FROM customers ORDER BY first_name');
$devices = fetch_all($conexion, 'SELECT d.device_id, d.device_type, d.brand, c.first_name FROM devices d JOIN customers c ON d.customer_id=c.customer_id ORDER BY d.device_id DESC');
$branches = fetch_all($conexion, 'SELECT branch_id, branch_name FROM branches ORDER BY branch_name');
// Solo técnicos para asignación
$users = fetch_all($conexion, "SELECT user_id, username, role FROM users WHERE active=1 AND role='technician' ORDER BY username");

function badge_class(string $priority): string {
    switch ($priority) {
        case 'high': return 'badge high';
        case 'urgent': return 'badge urgent';
        default: return 'badge normal';
    }
}

function translate_priority(string $priority): string {
    $translations = [
        'normal' => 'Normal',
        'high' => 'Alta',
        'urgent' => 'Urgente'
    ];
    return $translations[$priority] ?? $priority;
}

function translate_status(string $status): string {
    $translations = [
        'received' => 'Recibida',
        'diagnosing' => 'Diagnosticando',
        'in_repair' => 'En reparación',
        'waiting_parts' => 'Esperando piezas',
        'completed' => 'Completada',
        'delivered' => 'Entregada',
        'cancelled' => 'Cancelada'
    ];
    return $translations[$status] ?? $status;
}
?>
<?php if ($message): ?>
  <div class="alert <?= $message['type'] === 'success' ? 'success' : 'error' ?>"><?= $message['text'] ?></div>
<?php endif; ?>

<div class="card d-flex flex-column flex-md-row align-items-md-center justify-content-between">
  <div>
    <h2 class="mb-1">Ordenes de servicio</h2>
    <div class="text-muted">Crea y gestiona órdenes en tiempo real</div>
  </div>
  <div class="d-flex gap-2 mt-3 mt-md-0">
    <button class="button" data-bs-toggle="modal" data-bs-target="#modalNuevaOrden"><i class="bi bi-plus-lg"></i> Nueva orden</button>
  </div>
</div>

<div class="modal fade" id="modalNuevaOrden" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="background: var(--card); border:1px solid var(--border);">
      <div class="modal-header border-0">
        <h5 class="modal-title">Crear orden</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post">
        <div class="modal-body">
          <div class="form-grid">
            <div>
              <div class="label">Cliente</div>
              <div class="d-flex gap-2 align-items-center">
              <select name="customer_id" class="input" id="orderCustomerSelect" required>
                <option value="">Selecciona cliente</option>
                <?php foreach ($customers as $c): ?>
                  <option value="<?= $c['customer_id'] ?>"><?= $c['first_name'] ?> <?= $c['last_name'] ?></option>
                <?php endforeach; ?>
              </select>
              <button type="button" class="button" id="btnNuevoClienteOrder" style="padding:6px 10px;">Nuevo cliente</button>
              </div>
            </div>
            <div>
              <div class="label">Equipo</div>
              <select name="device_id" class="input" required>
                <option value="">Selecciona equipo</option>
                <?php foreach ($devices as $d): ?>
                  <option value="<?= $d['device_id'] ?>"><?= $d['device_type'] ?> - <?= $d['brand'] ?> (<?= $d['first_name'] ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <div class="label">Sucursal</div>
              <select name="branch_id" class="input">
                <option value="">Sin asignar</option>
                <?php foreach ($branches as $b): ?>
                  <option value="<?= $b['branch_id'] ?>"><?= $b['branch_name'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <div class="label">Técnico asignado</div>
              <select name="assigned_user_id" class="input">
                <option value="">Sin asignar</option>
                <?php foreach ($users as $u): ?>
                  <option value="<?= $u['user_id'] ?>"><?= $u['username'] ?> (<?= $u['role'] ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <div class="label">Prioridad</div>
              <select name="priority" class="input">
                <option value="normal">Normal</option>
                <option value="high">Alta</option>
                <option value="urgent">Urgente</option>
              </select>
            </div>
            <div>
              <div class="label">Tarifa mano de obra (S/)</div>
              <input type="number" step="0.01" min="0" name="labor_rate" class="input" value="120">
            </div>
            <div style="grid-column: 1 / -1;">
              <div class="label">Problema reportado</div>
              <textarea name="issue_description" class="input" required placeholder="Describe el fallo del equipo"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary" type="submit">Crear orden</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="card" style="margin-top:18px;">
  <h2>Ordenes</h2>
  <table class="table">
    <thead><tr><th>ID</th><th>Cliente</th><th>Equipo</th><th>Sucursal</th><th>Prioridad</th><th>Estado</th><th>Creada</th><th>Acciones</th></tr></thead>
    <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td>#<?= $o['order_id'] ?></td>
          <td><?= $o['first_name'] ?> <?= $o['last_name'] ?></td>
          <td><?= $o['device_type'] ?> <?= $o['brand'] ?></td>
          <td><?= $o['branch_name'] ?? '—' ?></td>
          <td><span class="<?= badge_class($o['priority']) ?>"><?= translate_priority($o['priority']) ?></span></td>
          <td><?= translate_status($o['status']) ?></td>
          <td><?= substr($o['created_at'],0,10) ?></td>
          <td class="d-flex gap-2 flex-wrap">
            <?php $rol = $_SESSION['rol'] ?? 'guest'; ?>
            <?php if ($rol === 'admin' || $rol === 'technician' || $rol === 'frontdesk'): ?>
              <button class="button btn-items" data-order-id="<?= (int)$o['order_id'] ?>" data-order-label="#<?= (int)$o['order_id'] ?> - <?= htmlspecialchars($o['first_name'].' '.$o['last_name']) ?>" style="padding:6px 10px;">Servicios/Ítems</button>
              <button class="button btn-pay" data-order-id="<?= (int)$o['order_id'] ?>" data-order-label="#<?= (int)$o['order_id'] ?> - <?= htmlspecialchars($o['first_name'].' '.$o['last_name']) ?>" style="padding:6px 10px;">Pagar</button>
              <?php if ((float)($o['estimated_total'] ?? 0) > 0): ?>
                <a class="button" target="_blank" href="service_invoice.php?order_id=<?= (int)$o['order_id'] ?>" style="padding:6px 10px;">Ver factura</a>
                <a class="button" target="_blank" href="service_invoice.php?order_id=<?= (int)$o['order_id'] ?>&format=pdf" style="padding:6px 10px;">Descargar PDF</a>
              <?php endif; ?>
            <?php else: ?>
              <button class="button btn-pay" data-order-id="<?= (int)$o['order_id'] ?>" data-order-label="#<?= (int)$o['order_id'] ?> - <?= htmlspecialchars($o['first_name'].' '.$o['last_name']) ?>" style="padding:6px 10px;">Pagar</button>
              <?php if ((float)($o['estimated_total'] ?? 0) > 0): ?>
                <a class="button" target="_blank" href="service_invoice.php?order_id=<?= (int)$o['order_id'] ?>" style="padding:6px 10px;">Ver factura</a>
              <?php endif; ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal inline para gestionar Servicios/Ítems sin salir de la página -->
<div class="modal fade" id="modalItems" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width:1200px;">
    <div class="modal-content" style="background: var(--card); border:1px solid var(--border); height:80vh; display:flex; flex-direction:column;">
      <div class="modal-header border-0">
        <h5 class="modal-title" id="itemsTitle">Servicios/Ítems</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-0" style="flex:1;">
        <iframe id="itemsFrame" src="about:blank" style="border:0; width:100%; height:100%;"></iframe>
      </div>
    </div>
  </div>
</div>

<!-- Modal para pagos -->
<div class="modal fade" id="modalPay" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width:900px;">
    <div class="modal-content" style="background: var(--card); border:1px solid var(--border); height:80vh; display:flex; flex-direction:column;">
      <div class="modal-header border-0">
        <h5 class="modal-title" id="payTitle">Pago</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-0" style="flex:1;">
        <iframe id="payFrame" src="about:blank" style="border:0; width:100%; height:100%;"></iframe>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
<script>
  // Modal Nuevo Cliente dentro de crear orden
  (function(){
    var btn = document.getElementById('btnNuevoClienteOrder');
    if (btn && !document.getElementById('modalNuevoClienteOrder')) {
      var html = `
      <div class="modal fade" id="modalNuevoClienteOrder" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog"><div class="modal-content" style="background: var(--card); border:1px solid var(--border);">
          <div class="modal-header border-0"><h5 class="modal-title">Nuevo cliente</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
          <div class="modal-body">
            <form id="formNuevoClienteOrder">
              <div class="mb-2"><label class="form-label">Nombre</label><input class="form-control" name="first_name" required></div>
              <div class="mb-2"><label class="form-label">Apellido</label><input class="form-control" name="last_name"></div>
              <div class="mb-2"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required></div>
              <div class="mb-2"><label class="form-label">Teléfono</label><input class="form-control" name="phone"></div>
              <div class="mb-2"><label class="form-label">Ciudad</label><input class="form-control" name="city"></div>
              <div class="text-end"><button class="btn btn-primary" type="submit">Guardar</button></div>
            </form>
          </div>
        </div></div>
      </div>`;
      document.body.insertAdjacentHTML('beforeend', html);
      var modalEl = document.getElementById('modalNuevoClienteOrder');
      var modal = new bootstrap.Modal(modalEl);
      btn.addEventListener('click', function(){ modal.show(); });
      document.getElementById('formNuevoClienteOrder').addEventListener('submit', function(e){
        e.preventDefault();
        var fd = new FormData(e.target);
        fetch('secciones/Clientes/quick_create.php', {method:'POST', body: fd}).then(r=>r.json()).then(function(j){
          if (j && j.ok) {
            var sel = document.getElementById('orderCustomerSelect');
            var opt = document.createElement('option');
            opt.value = j.customer_id; opt.textContent = j.name || ('Cliente #' + j.customer_id);
            sel.appendChild(opt);
            sel.value = j.customer_id;
            modal.hide();
          } else {
            alert('No se pudo crear el cliente: ' + (j && j.msg ? j.msg : ''));
          }
        }).catch(function(err){ alert('Error: ' + err.message); });
      });
    }
  })();
  // Abrir modal y cargar iframe con servicios/ítems de la orden seleccionada
  document.querySelectorAll('.btn-items').forEach(function(btn){
    btn.addEventListener('click', function(){
      var oid = this.getAttribute('data-order-id');
      var label = this.getAttribute('data-order-label') || 'Servicios/Ítems';
      document.getElementById('itemsTitle').textContent = label;
      document.getElementById('itemsFrame').src = 'orders_items.php?embed=1&order_id=' + encodeURIComponent(oid);
      var modal = new bootstrap.Modal(document.getElementById('modalItems'));
      modal.show();
    });
  });

  document.querySelectorAll('.btn-pay').forEach(function(btn){
    btn.addEventListener('click', function(){
      var oid = this.getAttribute('data-order-id');
      var label = this.getAttribute('data-order-label') || 'Pago';
      document.getElementById('payTitle').textContent = 'Pago ' + label;
      document.getElementById('payFrame').src = 'payments.php?embed=1&order_id=' + encodeURIComponent(oid);
      var modal = new bootstrap.Modal(document.getElementById('modalPay'));
      modal.show();
    });
  });
</script>
