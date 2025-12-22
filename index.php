<?php
if (!isset($_SESSION)) {
    session_start();
}

// Forzar inicio de sesión si no hay user_id ni guest
if (!isset($_SESSION)) {
    session_start();
}
if (!isset($_SESSION['user_id']) && !isset($_SESSION['guest'])) {
    header('Location: login.php');
    exit;
}

include_once __DIR__ . '/bd.php';
include_once __DIR__ . '/includes/helpers.php';
include_once __DIR__ . '/templates/header.php';

$counts = [
    'orders' => (int) ($conexion->query('SELECT COUNT(*) FROM service_orders')->fetchColumn() ?? 0),
    'customers' => (int) ($conexion->query('SELECT COUNT(*) FROM customers')->fetchColumn() ?? 0),
    'devices' => (int) ($conexion->query('SELECT COUNT(*) FROM devices')->fetchColumn() ?? 0),
    'pending' => (int) ($conexion->query("SELECT COUNT(*) FROM service_orders WHERE status IN ('received','diagnosing','in_repair','waiting_parts')")->fetchColumn() ?? 0),
];

$orders = fetch_all($conexion, 'SELECT o.order_id, o.status, o.priority, o.issue_description, o.created_at, c.first_name, c.last_name, d.device_type, d.brand, b.branch_name FROM service_orders o JOIN customers c ON o.customer_id=c.customer_id JOIN devices d ON o.device_id=d.device_id LEFT JOIN branches b ON o.branch_id=b.branch_id ORDER BY o.created_at DESC LIMIT 8');
$services = fetch_all($conexion, 'SELECT name, base_price, estimated_hours FROM service_catalog ORDER BY name LIMIT 8');
$statusData = fetch_all($conexion, 'SELECT status, COUNT(*) as total FROM service_orders GROUP BY status');
$priorityData = fetch_all($conexion, 'SELECT priority, COUNT(*) as total FROM service_orders GROUP BY priority');

// Datos para gráfico de órdenes por semana (últimas 4 semanas)
$weeklyOrders = fetch_all($conexion, "
    SELECT DATE_FORMAT(created_at, '%m/%d') as week, COUNT(*) as total 
    FROM service_orders 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 4 WEEK)
    GROUP BY DATE(created_at)
    ORDER BY created_at ASC
    LIMIT 10
");

// Datos para gráfico de ingresos por cliente (top 5)
$customerRevenue = fetch_all($conexion, "
    SELECT CONCAT(c.first_name, ' ', c.last_name) as customer, 
           SUM(o.estimated_total) as revenue
    FROM service_orders o
    JOIN customers c ON o.customer_id = c.customer_id
    GROUP BY o.customer_id
    ORDER BY revenue DESC
    LIMIT 5
");

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

<section class="grid stats-compact">
  <div class="card stat"><div class="stat-icon">O</div><div class="stat-content"><strong><?= $counts['orders'] ?></strong><span>Órdenes totales</span></div></div>
  <div class="card stat"><div class="stat-icon">P</div><div class="stat-content"><strong><?= $counts['pending'] ?></strong><span>En proceso</span></div></div>
  <div class="card stat"><div class="stat-icon">C</div><div class="stat-content"><strong><?= $counts['customers'] ?></strong><span>Clientes</span></div></div>
  <div class="card stat"><div class="stat-icon">D</div><div class="stat-content"><strong><?= $counts['devices'] ?></strong><span>Dispositivos</span></div></div>
</section>

<section class="grid cols-2" style="margin-top:18px;">
  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
      <h2>Ordenes recientes</h2>
      <a class="button" href="/proyecto_final/orders.php">Ver todo</a>
    </div>
    <table class="table">
      <thead><tr><th>ID</th><th>Cliente</th><th>Equipo</th><th>Sucursal</th><th>Prioridad</th><th>Estado</th></tr></thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td>#<?= $o['order_id'] ?></td>
            <td><?= $o['first_name'] ?> <?= $o['last_name'] ?></td>
            <td><?= $o['device_type'] ?> <?= $o['brand'] ?></td>
            <td><?= $o['branch_name'] ?? '—' ?></td>
            <td><span class="<?= badge_class($o['priority']) ?>"><?= translate_priority($o['priority']) ?></span></td>
            <td><?= translate_status($o['status']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="card">
    <h2>Servicios y precios base</h2>
    <table class="table">
      <thead><tr><th>Servicio</th><th>Precio base</th><th>Horas</th></tr></thead>
      <tbody>
        <?php foreach ($services as $s): ?>
          <tr>
            <td><?= $s['name'] ?></td>
            <td>Bs <?= number_format($s['base_price'],2) ?></td>
            <td><?= $s['estimated_hours'] ?? '—' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="grid cols-2" style="margin-top:18px;">
  <div class="card">
    <h2>Estados de orden</h2>
    <canvas id="chartStatus" height="160"></canvas>
  </div>
  <div class="card">
    <h2>Prioridad de órdenes</h2>
    <canvas id="chartPriority" height="160"></canvas>
  </div>
</section>

<section class="grid cols-2" style="margin-top:18px;">
  <div class="card">
    <h2>Órdenes por día</h2>
    <canvas id="chartWeekly" height="160"></canvas>
  </div>
  <div class="card">
    <h2>Ingresos por cliente</h2>
    <canvas id="chartRevenue" height="160"></canvas>
  </div>
</section>

<?php
include("templates/footer.php");
?>
<script>
  const statusLabels = <?php echo json_encode(array_column($statusData, 'status')); ?>;
  const statusValues = <?php echo json_encode(array_map('intval', array_column($statusData, 'total'))); ?>;
  const priorityLabels = <?php echo json_encode(array_column($priorityData, 'priority')); ?>;
  const priorityValues = <?php echo json_encode(array_map('intval', array_column($priorityData, 'total'))); ?>;
  const weeklyLabels = <?php echo json_encode(array_column($weeklyOrders, 'week')); ?>;
  const weeklyValues = <?php echo json_encode(array_map('intval', array_column($weeklyOrders, 'total'))); ?>;
  const revenueLabels = <?php echo json_encode(array_column($customerRevenue, 'customer')); ?>;
  const revenueValues = <?php echo json_encode(array_map('floatval', array_column($customerRevenue, 'revenue'))); ?>;

  const palette = ['#3ad0ff','#5bf5c9','#ffb347','#ff6b6b','#9c6bff'];

  // Translate status labels
  const statusTranslations = {
    'received': 'Recibida',
    'diagnosing': 'Diagnosticando',
    'in_repair': 'En reparación',
    'waiting_parts': 'Esperando piezas',
    'completed': 'Completada',
    'delivered': 'Entregada',
    'cancelled': 'Cancelada'
  };

  // Translate priority labels
  const priorityTranslations = {
    'normal': 'Normal',
    'high': 'Alta',
    'urgent': 'Urgente'
  };

  const translatedStatusLabels = statusLabels.map(label => statusTranslations[label] || label);
  const translatedPriorityLabels = priorityLabels.map(label => priorityTranslations[label] || label);

  const ctxStatus = document.getElementById('chartStatus').getContext('2d');
  new Chart(ctxStatus, {
    type: 'bar',
    data: {
      labels: translatedStatusLabels,
      datasets: [{
        label: 'Órdenes',
        data: statusValues,
        backgroundColor: palette,
        borderWidth: 0,
      }]
    },
    options: {
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { color: '#ffffff' }, grid: { color: 'rgba(255,255,255,0.05)' } },
        x: { ticks: { color: '#ffffff' }, grid: { display: false } }
      }
    }
  });

  const ctxPriority = document.getElementById('chartPriority').getContext('2d');
  new Chart(ctxPriority, {
    type: 'doughnut',
    data: {
      labels: translatedPriorityLabels,
      datasets: [{
        data: priorityValues,
        backgroundColor: palette,
        borderWidth: 0,
      }]
    },
    options: {
      plugins: {
        legend: { position: 'bottom', labels: { color: '#ffffff' } }
      }
    }
  });

  // Gráfico de línea - Órdenes por día
  const ctxWeekly = document.getElementById('chartWeekly').getContext('2d');
  new Chart(ctxWeekly, {
    type: 'line',
    data: {
      labels: weeklyLabels,
      datasets: [{
        label: 'Órdenes',
        data: weeklyValues,
        borderColor: '#3ad0ff',
        backgroundColor: 'rgba(58, 208, 255, 0.15)',
        tension: 0.4,
        fill: true,
        pointBackgroundColor: '#3ad0ff',
        pointBorderColor: '#0b0f1a',
        pointBorderWidth: 2,
        pointRadius: 4
      }]
    },
    options: {
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { color: '#ffffff' }, grid: { color: 'rgba(255,255,255,0.05)' } },
        x: { ticks: { color: '#ffffff' }, grid: { display: false } }
      }
    }
  });

  // Gráfico de barras - Ingresos por cliente
  const ctxRevenue = document.getElementById('chartRevenue').getContext('2d');
  new Chart(ctxRevenue, {
    type: 'bar',
    data: {
      labels: revenueLabels,
      datasets: [{
        label: 'Ingresos (S/)',
        data: revenueValues,
        backgroundColor: ['#1e3a5f', '#2a4d7c', '#3d5a80', '#5988b0', '#3ad0ff'],
        borderWidth: 0,
      }]
    },
    options: {
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { color: '#ffffff' }, grid: { color: 'rgba(255,255,255,0.05)' } },
        x: { ticks: { color: '#ffffff' }, grid: { display: false } }
      }
    }
  });
</script>
