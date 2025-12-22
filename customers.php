<?php
if (!isset($_SESSION)) session_start();
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/includes/helpers.php';

// Manejo de formularios (crear cliente / registrar equipo)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'create_customer') {
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $city  = trim($_POST['city'] ?? '');
    $address = trim($_POST['address'] ?? '');
    if ($first !== '' && $last !== '' && $email !== '') {
      try {
        $stmt = $conexion->prepare('INSERT INTO customers (first_name, last_name, email, phone, city, address) VALUES (:f,:l,:e,:p,:c,:a)');
        $stmt->execute([':f'=>$first, ':l'=>$last, ':e'=>$email, ':p'=>$phone, ':c'=>$city, ':a'=>$address]);
        header('Location: customers.php?ok=1');
        exit;
      } catch (Exception $e) {
        header('Location: customers.php?error=No%20se%20pudo%20crear%20el%20cliente');
        exit;
      }
    } else {
      header('Location: customers.php?error=Faltan%20datos%20del%20cliente');
      exit;
    }
  }
  if ($action === 'create_device') {
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $type  = trim($_POST['device_type'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $serial = trim($_POST['serial_number'] ?? '');
    $notes  = trim($_POST['notes'] ?? '');
    if ($customerId > 0 && $type !== '') {
      try {
        $stmt = $conexion->prepare('INSERT INTO devices (customer_id, device_type, brand, model, serial_number, notes) VALUES (:c,:t,:b,:m,:s,:n)');
        $stmt->execute([':c'=>$customerId, ':t'=>$type, ':b'=>$brand, ':m'=>$model, ':s'=>$serial, ':n'=>$notes]);
        header('Location: customers.php?ok=1');
        exit;
      } catch (Exception $e) {
        header('Location: customers.php?error=No%20se%20pudo%20registrar%20el%20equipo');
        exit;
      }
    } else {
      header('Location: customers.php?error=Faltan%20datos%20del%20equipo');
      exit;
    }
  }
}

$customers = fetch_all($conexion, 'SELECT customer_id, first_name, last_name, phone, email, city, created_at FROM customers ORDER BY created_at DESC');
$devices = fetch_all($conexion, 'SELECT d.device_id, d.device_type, d.brand, d.model, c.first_name, c.last_name FROM devices d JOIN customers c ON d.customer_id=c.customer_id ORDER BY d.created_at DESC');

include __DIR__ . '/templates/header.php';
?>
<div class="card">
  <h2>Clientes</h2>
  <div class="d-flex justify-content-end mb-2">
    <button class="button" data-bs-toggle="modal" data-bs-target="#modalCliente">Nuevo cliente</button>
  </div>
  <table class="table">
    <thead><tr><th>ID</th><th>Nombre</th><th>Telefono</th><th>Email</th><th>Ciudad</th><th>Registro</th></tr></thead>
    <tbody>
      <?php foreach ($customers as $c): ?>
        <tr>
          <td><?= $c['customer_id'] ?></td>
          <td><?= $c['first_name'] ?> <?= $c['last_name'] ?></td>
          <td><?= $c['phone'] ?? '—' ?></td>
          <td><?= $c['email'] ?></td>
          <td><?= $c['city'] ?? '—' ?></td>
          <td><?= substr($c['created_at'],0,10) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="card" style="margin-top:18px;">
  <h2>Equipos por cliente</h2>
  <div class="d-flex justify-content-end mb-2">
    <button class="button" data-bs-toggle="modal" data-bs-target="#modalEquipo">Registrar equipo</button>
  </div>
  <table class="table">
    <thead><tr><th>ID</th><th>Tipo</th><th>Marca</th><th>Modelo</th><th>Cliente</th></tr></thead>
    <tbody>
      <?php foreach ($devices as $d): ?>
        <tr>
          <td><?= $d['device_id'] ?></td>
          <td><?= $d['device_type'] ?></td>
          <td><?= $d['brand'] ?></td>
          <td><?= $d['model'] ?? '—' ?></td>
          <td><?= $d['first_name'] ?> <?= $d['last_name'] ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal: Nuevo Cliente -->
<div class="modal fade" id="modalCliente" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="background: var(--card); border:1px solid var(--border);">
      <div class="modal-header border-0">
        <h5 class="modal-title">Nuevo cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <form method="post">
        <input type="hidden" name="action" value="create_customer">
        <div class="modal-body">
          <div class="form-grid">
            <div>
              <div class="label">Nombre</div>
              <input name="first_name" class="input" required>
            </div>
            <div>
              <div class="label">Apellido</div>
              <input name="last_name" class="input" required>
            </div>
            <div>
              <div class="label">Email</div>
              <input type="email" name="email" class="input" required>
            </div>
            <div>
              <div class="label">Teléfono</div>
              <input name="phone" class="input">
            </div>
            <div>
              <div class="label">Ciudad</div>
              <input name="city" class="input">
            </div>
            <div style="grid-column:1 / -1;">
              <div class="label">Dirección</div>
              <input name="address" class="input">
            </div>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary" type="submit">Guardar</button>
        </div>
      </form>
    </div>
  </div>
  </div>

<!-- Modal: Registrar Equipo -->
<div class="modal fade" id="modalEquipo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="background: var(--card); border:1px solid var(--border);">
      <div class="modal-header border-0">
        <h5 class="modal-title">Registrar equipo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <form method="post">
        <input type="hidden" name="action" value="create_device">
        <div class="modal-body">
          <div class="form-grid">
            <div>
              <div class="label">Cliente</div>
              <select name="customer_id" class="input" required>
                <option value="">Selecciona cliente</option>
                <?php foreach ($customers as $c): ?>
                  <option value="<?= (int)$c['customer_id'] ?>"><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <div class="label">Tipo</div>
              <input name="device_type" class="input" placeholder="Laptop, PC, Impresora" required>
            </div>
            <div>
              <div class="label">Marca</div>
              <input name="brand" class="input">
            </div>
            <div>
              <div class="label">Modelo</div>
              <input name="model" class="input">
            </div>
            <div>
              <div class="label">Serie</div>
              <input name="serial_number" class="input">
            </div>
            <div style="grid-column:1 / -1;">
              <div class="label">Notas</div>
              <textarea name="notes" class="input" placeholder="Estado físico, accesorios, etc."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary" type="submit">Guardar equipo</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
