<?php
if (!isset($_SESSION)) session_start();
require_once __DIR__ . '/bd.php';
include __DIR__ . '/templates/header.php';

$cat = isset($_GET['cat']) ? (int) $_GET['cat'] : 0;

$categories = $conexion->query('SELECT category_id, name FROM categories ORDER BY name')->fetchAll();

if ($cat > 0) {
    $stmt = $conexion->prepare('SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id=c.category_id WHERE p.category_id = ? ORDER BY p.created_at DESC');
    $stmt->execute([$cat]);
    $products = $stmt->fetchAll();
} else {
    $products = $conexion->query('SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id=c.category_id ORDER BY p.created_at DESC')->fetchAll();
}
?>

<div class="card d-flex flex-column flex-md-row align-items-md-center justify-content-between">
  <div>
    <h2 class="mb-1">Productos y equipos</h2>
    <div class="text-muted">Repuestos, periféricos y PCs armadas para venta</div>
  </div>
</div>

<div class="card" style="margin-top:18px;">
  <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <strong>Categorías:</strong>
    <a class="button" href="products.php" style="padding:6px 10px;">Todas</a>
    <?php foreach ($categories as $c): ?>
      <a class="button" href="products.php?cat=<?= $c['category_id'] ?>" style="padding:6px 10px;">
        <?= htmlspecialchars($c['name']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="grid cols-3">
    <?php if (!$products): ?>
      <div class="text-muted">No hay productos en esta categoría.</div>
    <?php endif; ?>
    <?php foreach ($products as $p): ?>
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
            <span class="badge ready" style="text-transform:uppercase;">PC Armada</span>
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
            <form method="post" action="secciones/cart/add.php" class="m-0">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
              <div class="d-flex align-items-center gap-2">
                <input type="number" name="quantity" class="form-control" style="width:70px; padding:6px;" min="1" max="<?= (int)$p['stock_quantity'] ?>" value="1" <?= ($p['stock_quantity'] <= 0 ? 'disabled' : '') ?>>
                <button class="button" style="padding:8px 12px; white-space:nowrap;" <?= ($p['stock_quantity'] <= 0 ? 'disabled' : '') ?>>
              <i class="bi bi-cart"></i> Agregar
            </button>
              </div>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
