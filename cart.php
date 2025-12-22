<?php
if (!isset($_SESSION)) session_start();
require_once __DIR__ . '/bd.php';

// Helpers
function getCartCount() {
    return isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
}

function loadProducts(PDO $db, array $ids): array {
    if (!$ids) return [];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id=c.category_id WHERE p.product_id IN ($placeholders)");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll();
    $map = [];
    foreach ($rows as $r) $map[$r['product_id']] = $r;
    return $map;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
$message = null;
$error = null;

if ($action === 'add') {
    $pid = (int)($_POST['product_id'] ?? 0);
      $qty = max(1, (int)($_POST['quantity'] ?? 1));
    if ($pid > 0) {
        // Validar stock disponible
        $stmt = $conexion->prepare('SELECT stock_quantity FROM products WHERE product_id = ?');
        $stmt->execute([$pid]);
        $product = $stmt->fetch();
        if ($product && $product['stock_quantity'] >= $qty) {
          $_SESSION['cart'][$pid] = ($_SESSION['cart'][$pid] ?? 0) + $qty;
        }
        header('Location: cart.php');
        exit;
    }
}

if ($action === 'remove') {
    $pid = (int)($_GET['product_id'] ?? 0);
    if ($pid > 0 && isset($_SESSION['cart'][$pid])) {
        unset($_SESSION['cart'][$pid]);
        $message = 'Producto removido del carrito.';
    }
}

if ($action === 'clear') {
    $_SESSION['cart'] = [];
    $message = 'Carrito vaciado.';
}

// Checkout
if ($action === 'checkout' && !empty($_SESSION['cart'])) {
  $customerId = $_POST['customer_id'] !== '' ? (int)$_POST['customer_id'] : null;
  $cartIds = array_keys($_SESSION['cart']);
  $products = loadProducts($conexion, $cartIds);
  // Validar stock
  foreach ($_SESSION['cart'] as $pid => $qty) {
    if (!isset($products[$pid])) { $error = 'Producto no encontrado.'; break; }
    if ($products[$pid]['stock_quantity'] < $qty) { $error = 'Stock insuficiente para ' . $products[$pid]['name']; break; }
  }
  if (!$error) {
    try {
      $conexion->beginTransaction();
      $total = 0;
      foreach ($_SESSION['cart'] as $pid => $qty) {
        $total += $products[$pid]['price'] * $qty;
      }
      // Crear pedido en 'orders' con estado y pago pendientes
      $stmt = $conexion->prepare("INSERT INTO orders (order_id, customer_id, order_date, user_id, estado, total_amount, discount, payment_method, payment_status, cancellation_reason) VALUES (NULL, :customer_id, :order_date, :user_id, :estado, :total_amount, :discount, :payment_method, :payment_status, :cancellation_reason)");
      $order_date = date('Y-m-d H:i:s');
      $user_id = !empty($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
      $discount = 0.0;
      $payment_method = null;
      $payment_status = 'Pendiente';
      $estado = 'Pendiente';
      $stmt->execute([
        ':customer_id' => $customerId,
        ':order_date' => $order_date,
        ':user_id' => $user_id,
        ':estado' => $estado,
        ':total_amount' => $total,
        ':discount' => $discount,
        ':payment_method' => $payment_method,
        ':payment_status' => $payment_status,
        ':cancellation_reason' => null,
      ]);
      $orderId = (int)$conexion->lastInsertId();

      // Insertar ítems del pedido
      $itemStmt = $conexion->prepare('INSERT INTO order_items (order_items_id, order_id, product_id, quantity, price, discount) VALUES (NULL, :order_id, :product_id, :quantity, :price, 0.00)');
      $stockStmt = $conexion->prepare('UPDATE products SET stock_quantity = stock_quantity - :q WHERE product_id = :p');
      foreach ($_SESSION['cart'] as $pid => $qty) {
        $itemStmt->execute([':order_id' => $orderId, ':product_id' => $pid, ':quantity' => $qty, ':price' => $products[$pid]['price']]);
        $stockStmt->execute([':q' => $qty, ':p' => $pid]);
      }

      $conexion->commit();
      // Redirigir para abrir modal de cobro
      header('Location: cart.php?order_id=' . $orderId);
      exit;
    } catch (Exception $e) {
      if ($conexion->inTransaction()) $conexion->rollBack();
      $error = 'Error al crear el pedido: ' . $e->getMessage();
    }
  }
}

$cartIds = array_keys($_SESSION['cart']);
$products = loadProducts($conexion, $cartIds);
$total = 0;
foreach ($_SESSION['cart'] as $pid => $qty) {
    if (isset($products[$pid])) {
        $total += $products[$pid]['price'] * $qty;
    }
}
// Obtener todos los productos disponibles
$allProducts = $conexion->query('SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id=c.category_id WHERE p.stock_quantity > 0 ORDER BY c.name, p.name')->fetchAll();
$customers = $conexion->query('SELECT customer_id, first_name, last_name FROM customers ORDER BY first_name')->fetchAll();
include __DIR__ . '/templates/header.php';
?>

<div class="card d-flex flex-column flex-md-row align-items-md-center justify-content-between">
  <div>
    <h2 class="mb-1">Carrito</h2>
    <div class="text-muted">Productos para venta retail</div>
  </div>
  <div class="d-flex gap-2 mt-3 mt-md-0">
    <a class="button" href="cart.php?action=clear">Vaciar carrito</a>
  </div>
</div>

<?php if ($message): ?>
  <div class="alert success" style="margin-top:12px;"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert error" style="margin-top:12px;"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card" style="margin-top:18px;">
  <h2>Resumen</h2>
  <?php if (!$cartIds): ?>
    <div class="text-muted">No hay productos en el carrito.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table">
        <thead><tr><th>Producto</th><th>Categoría</th><th>Precio</th><th>Cantidad</th><th>Subtotal</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($_SESSION['cart'] as $pid => $qty): if (!isset($products[$pid])) continue; $p = $products[$pid]; ?>
            <tr>
              <td><?= htmlspecialchars($p['name']) ?></td>
              <td><?= htmlspecialchars($p['category_name']) ?></td>
              <td>Bs <?= number_format($p['price'],2) ?></td>
              <td><?= (int)$qty ?></td>
              <td>Bs <?= number_format($p['price'] * $qty, 2) ?></td>
              <td><a class="button" href="cart.php?action=remove&product_id=<?= (int)$pid ?>" style="padding:6px 10px;">Quitar</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="d-flex justify-content-between align-items-center" style="margin-top:12px;">
      <div style="font-size:18px;">Total: <strong>S/ <?= number_format($total,2) ?></strong></div>
      <form method="post" action="cart.php" class="d-flex gap-2 align-items-center m-0">
        <input type="hidden" name="action" value="checkout">
        <select name="customer_id" class="input" id="cartCustomerSelect" style="max-width:220px;">
          <option value="">Cliente opcional</option>
          <?php foreach ($customers as $c): ?>
            <option value="<?= (int)$c['customer_id'] ?>"><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="button" class="button" id="btnNuevoClienteCart" style="padding:10px 14px;">Nuevo cliente</button>
        <button class="button" style="padding:10px 14px;">Confirmar y cobrar</button>
      </form>
    </div>
  <?php endif; ?>
</div>

<!-- Sección de productos disponibles para agregar al carrito -->
<div class="card" style="margin-top:18px;">
  <h2>Agregar más productos</h2>
  <div class="text-muted mb-3">Selecciona productos disponibles en stock para agregar al carrito</div>
  
  <?php if (!$allProducts): ?>
    <div class="text-muted">No hay productos disponibles en stock.</div>
  <?php else: ?>
    <div class="grid cols-3">
      <?php foreach ($allProducts as $p): ?>
        <div class="card" style="border:1px solid var(--border);">
          <?php if (!empty($p['image_url'])): ?>
            <div style="text-align:center; margin-bottom:12px;">
              <img src="secciones/Productos/img/<?= htmlspecialchars($p['image_url']) ?>" 
                   alt="<?= htmlspecialchars($p['name']) ?>" 
                   style="max-width:100%; height:200px; object-fit:cover; border-radius:8px;">
            </div>
          <?php endif; ?>
          <div class="d-flex justify-content-between align-items-start mb-2">
            <h3 style="margin:0; font-size:16px;"><?= htmlspecialchars($p['name']) ?></h3>
            <?php if (!empty($p['is_bundle'])): ?>
              <span class="badge ready" style="text-transform:uppercase; font-size:11px;">PC Armada</span>
            <?php endif; ?>
          </div>
          <div class="text-muted" style="font-size:13px;">Categoría: <?= htmlspecialchars($p['category_name']) ?></div>
          <div class="text-muted" style="font-size:13px;">Modelo: <?= htmlspecialchars($p['model'] ?? 'N/D') ?></div>
          <p style="margin:8px 0 12px; color:var(--muted); font-size:13px;"><?= htmlspecialchars($p['description'] ?? '') ?></p>
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div style="font-size:20px; font-weight:700; color:var(--text);">Bs <?= number_format($p['price'],2) ?></div>
              <div class="text-muted" style="font-size:13px;">Stock: <?= (int)$p['stock_quantity'] ?></div>
            </div>
            <form method="post" action="cart.php" class="m-0">
              <input type="hidden" name="action" value="add">
              <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
              <div class="d-flex flex-column gap-2" style="width:100%;">
                <input type="number" name="quantity" class="form-control" style="width:100%; padding:6px; font-size:13px;" min="1" max="<?= (int)$p['stock_quantity'] ?>" value="1">
                <button class="button" style="padding:8px 12px; font-size:13px; white-space:nowrap; width:100%;">
                  <i class="bi bi-cart"></i> Agregar
                </button>
              </div>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>

<script>
// Modal rápido para crear clientes desde carrito
document.addEventListener('DOMContentLoaded', function(){
  const btn = document.getElementById('btnNuevoClienteCart');
  if (btn && !document.getElementById('modalNuevoClienteCart')) {
    const html = `
    <div class="modal fade" id="modalNuevoClienteCart" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog"><div class="modal-content" style="background: var(--card); border:1px solid var(--border);">
        <div class="modal-header border-0"><h5 class="modal-title">Nuevo cliente</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body">
          <form id="formNuevoClienteCart">
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
    const modalEl = document.getElementById('modalNuevoClienteCart');
    const modal = new bootstrap.Modal(modalEl);
    btn.addEventListener('click', ()=> modal.show());
    document.getElementById('formNuevoClienteCart').addEventListener('submit', function(e){
      e.preventDefault();
      const fd = new FormData(e.target);
      fetch('secciones/Clientes/quick_create.php', {method:'POST', body: fd}).then(r=>r.json()).then(j=>{
        if (j && j.ok) {
          const sel = document.getElementById('cartCustomerSelect');
          const opt = document.createElement('option');
          opt.value = j.customer_id; opt.textContent = j.name || ('Cliente #' + j.customer_id);
          sel.appendChild(opt);
          sel.value = j.customer_id;
          modal.hide();
        } else {
          alert('No se pudo crear el cliente: ' + (j && j.msg ? j.msg : '')); 
        }
      }).catch(err=> alert('Error: ' + err.message));
    });
  }
});

document.addEventListener('DOMContentLoaded', function(){
  const params = new URLSearchParams(window.location.search);
  const orderId = params.get('order_id');
  if (orderId) {
    const iframeUrl = 'sales_payments.php?embed=1&order_id=' + encodeURIComponent(orderId) + '&clear_session_cart=1';
    const modalHtml = `
    <div class="modal fade" id="modalCobroRetail" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width:900px;">
        <div class="modal-content" style="background: var(--card); border:1px solid var(--border); height:80vh; display:flex; flex-direction:column;">
          <div class="modal-header border-0">
            <h5 class="modal-title">Cobro</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body p-0" style="flex:1;">
            <iframe id="cobroRetailFrame" src="about:blank" style="border:0; width:100%; height:100%;"></iframe>
          </div>
        </div>
      </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    document.getElementById('cobroRetailFrame').src = iframeUrl;
    var modal = new bootstrap.Modal(document.getElementById('modalCobroRetail'));
    modal.show();
  }
});
</script>
