<?php
if (!isset($_SESSION)) session_start();
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/includes/helpers.php';
include __DIR__ . '/templates/header.php';

$rol = $_SESSION['rol'] ?? 'guest';
$isStaff = in_array($rol, ['admin','technician','frontdesk'], true);

$orders = fetch_all($conexion, 'SELECT o.order_id, o.order_date, o.total_amount, o.estado, CONCAT(c.first_name, " ", c.last_name) AS customer_name FROM orders o LEFT JOIN customers c ON c.customer_id=o.customer_id ORDER BY o.order_date DESC LIMIT 200');
?>
<div class="card d-flex flex-column flex-md-row align-items-md-center justify-content-between">
  <div>
    <h2 class="mb-1">Ventas</h2>
    <div class="text-muted">Listado de pedidos registrados</div>
  </div>
</div>

<div class="card" style="margin-top:18px;">
  <h2>Pedidos</h2>
  <table class="table">
    <thead><tr><th>ID</th><th>Cliente</th><th>Total</th><th>Estado</th><th>Fecha</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td>#<?= (int)$o['order_id'] ?></td>
        <td><?= htmlspecialchars($o['customer_name'] ?? '—') ?></td>
        <td>Bs <?= number_format((float)$o['total_amount'],2) ?></td>
        <td><?= htmlspecialchars($o['estado'] ?? '-') ?></td>
        <td><?= htmlspecialchars($o['order_date'] ?? '-') ?></td>
        <td class="d-flex gap-2 flex-wrap">
          <?php if ($isStaff): ?>
            <button class="button btn-cobrar" data-order-id="<?= (int)$o['order_id'] ?>" data-order-label="#<?= (int)$o['order_id'] ?> - <?= htmlspecialchars($o['customer_name'] ?? '') ?>" style="padding:6px 10px;">Cobrar</button>
          <?php endif; ?>
          <a class="button" target="_blank" href="secciones/Pedidos/invoice.php?order_id=<?= (int)$o['order_id'] ?>" style="padding:6px 10px;">Ver factura</a>
          <a class="button" target="_blank" href="secciones/Pedidos/generar_factura.php?order_id=<?= (int)$o['order_id'] ?>" style="padding:6px 10px;">Descargar PDF</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal para cobro -->
<div class="modal fade" id="modalCobro" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width:900px;">
    <div class="modal-content" style="background: var(--card); border:1px solid var(--border); height:80vh; display:flex; flex-direction:column;">
      <div class="modal-header border-0">
        <h5 class="modal-title" id="cobroTitle">Cobro</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-0" style="flex:1;">
        <iframe id="cobroFrame" src="about:blank" style="border:0; width:100%; height:100%;"></iframe>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
<script>
  document.querySelectorAll('.btn-cobrar').forEach(function(btn){
    btn.addEventListener('click', function(){
      var oid = this.getAttribute('data-order-id');
      var label = this.getAttribute('data-order-label') || 'Cobro';
      document.getElementById('cobroTitle').textContent = label;
      document.getElementById('cobroFrame').src = 'sales_payments.php?embed=1&order_id=' + encodeURIComponent(oid);
      var modal = new bootstrap.Modal(document.getElementById('modalCobro'));
      modal.show();
    });
  });
</script>
