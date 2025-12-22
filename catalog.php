<?php
if (!isset($_SESSION)) session_start();
require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/includes/helpers.php';

// Crear servicio desde el catálogo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $base = (float)($_POST['base_price'] ?? 0);
  $hours = $_POST['estimated_hours'] !== '' ? (float)$_POST['estimated_hours'] : null;
  if ($name !== '' && $base >= 0) {
    try {
      $stmt = $conexion->prepare('INSERT INTO service_catalog (name, description, base_price, estimated_hours) VALUES (:n,:d,:b,:h)');
      $stmt->execute([
        ':n' => $name,
        ':d' => $description,
        ':b' => $base,
        ':h' => $hours,
      ]);
      header('Location: catalog.php?ok=1');
      exit;
    } catch (Exception $e) {
      header('Location: catalog.php?error=No%20se%20pudo%20crear%20el%20servicio');
      exit;
    }
  } else {
    header('Location: catalog.php?error=Nombre%20y%20precio%20son%20obligatorios');
    exit;
  }
}

$services = fetch_all($conexion, 'SELECT service_id, name, description, base_price, estimated_hours FROM service_catalog ORDER BY name');

include __DIR__ . '/templates/header.php';
?>

<div class="card">
  <h2>Catalogo de servicios</h2>
  <div class="d-flex justify-content-end mb-3">
    <button class="button" data-bs-toggle="modal" data-bs-target="#modalServicio">
      <i class="bi bi-plus-lg"></i> Nuevo servicio
    </button>
  </div>

  <table class="table">
    <thead><tr><th>ID</th><th>Servicio</th><th>Descripcion</th><th>Base</th><th>Horas</th></tr></thead>
    <tbody>
      <?php foreach ($services as $s): ?>
        <tr>
          <td><?= $s['service_id'] ?></td>
          <td><?= $s['name'] ?></td>
          <td><?= $s['description'] ?? '—' ?></td>
          <td>Bs <?= number_format($s['base_price'],2) ?></td>
          <td><?= $s['estimated_hours'] ?? '—' ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal: Nuevo Servicio -->
<div class="modal fade" id="modalServicio" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="background: var(--card); border:1px solid var(--border);">
      <div class="modal-header border-0">
        <h5 class="modal-title">Nuevo servicio</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <form method="post">
        <div class="modal-body">
          <div class="form-grid">
            <div>
              <div class="label">Nombre</div>
              <input name="name" class="input" required>
            </div>
            <div>
              <div class="label">Precio base (Bs)</div>
              <input type="number" step="0.01" name="base_price" class="input" required>
            </div>
            <div>
              <div class="label">Horas estimadas</div>
              <input type="number" step="0.1" name="estimated_hours" class="input" placeholder="Opcional">
            </div>
            <div style="grid-column:1 / -1;">
              <div class="label">Descripción</div>
              <input name="description" class="input" placeholder="Opcional">
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

<?php include __DIR__ . '/templates/footer.php'; ?>
