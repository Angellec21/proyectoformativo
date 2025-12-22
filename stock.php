<?php
if (!isset($_SESSION)) session_start();
require_once __DIR__ . '/bd.php';
include __DIR__ . '/templates/header.php';

$message = null;
$error = null;

// Procesar agregar stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add_stock') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        
        if ($productId > 0 && $quantity > 0) {
            try {
                // Procesar imagen si se cargó
                if (isset($_FILES['product_image']) && $_FILES['product_image']['tmp_name'] && is_uploaded_file($_FILES['product_image']['tmp_name'])) {
                    $file = $_FILES['product_image'];
                    $fecha_ = new DateTime();
                    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $file['name']);
                    $filename = $fecha_->getTimestamp() . '_' . $safeName;
                    $destino = __DIR__ . '/secciones/Productos/img/' . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $destino)) {
                        $stmt = $conexion->prepare('UPDATE products SET image_url = :img WHERE product_id = :id');
                        $stmt->execute([':img' => $filename, ':id' => $productId]);
                    }
                }
                
                // Actualizar stock
                $stmt = $conexion->prepare('UPDATE products SET stock_quantity = stock_quantity + :qty WHERE product_id = :id');
                $stmt->execute([':qty' => $quantity, ':id' => $productId]);
                $message = "Se agregaron $quantity unidades al stock";
            } catch (Exception $e) {
                $error = "Error al actualizar stock: " . $e->getMessage();
            }
        } else {
            $error = "Datos inválidos. Verifica la cantidad.";
        }
    }
    
    if ($action === 'set_stock') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        
        if ($productId > 0 && $quantity >= 0) {
            try {
                $stmt = $conexion->prepare('UPDATE products SET stock_quantity = :qty WHERE product_id = :id');
                $stmt->execute([':qty' => $quantity, ':id' => $productId]);
                $message = "Stock actualizado a $quantity unidades";
            } catch (Exception $e) {
                $error = "Error al actualizar stock: " . $e->getMessage();
            }
        } else {
            $error = "Datos inválidos.";
        }
    }
}

// Obtener todos los productos
$products = $conexion->query('SELECT p.product_id, p.name, p.price, p.stock_quantity, p.model, p.image_url, c.name AS category_name FROM products p JOIN categories c ON p.category_id=c.category_id ORDER BY c.name, p.name')->fetchAll();
?>

<div class="card d-flex flex-column flex-md-row align-items-md-center justify-content-between">
  <div>
    <h2 class="mb-1">Gestión de Stock</h2>
    <div class="text-muted">Administra el inventario de productos disponibles en tienda</div>
  </div>
</div>

<?php if ($message): ?>
  <div class="alert success" style="margin-top:12px;"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert error" style="margin-top:12px;"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Tabla de productos y stock -->
<div class="card" style="margin-top:18px;">
  <h3>Inventario actual</h3>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Producto</th>
          <th>Categoría</th>
          <th>Modelo</th>
          <th>Precio (Bs)</th>
          <th>Stock actual</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
          <tr>
            <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
            <td><?= htmlspecialchars($p['category_name']) ?></td>
            <td><?= htmlspecialchars($p['model'] ?? 'N/D') ?></td>
            <td><?= number_format($p['price'], 2) ?></td>
            <td>
              <span style="font-size:18px; font-weight:700; color:<?= $p['stock_quantity'] <= 0 ? '#dc3545' : '#28a745' ?>">
                <?= (int)$p['stock_quantity'] ?>
              </span>
              <?php if ($p['stock_quantity'] <= 5): ?>
                <span class="badge danger" style="margin-left:8px;">Bajo stock</span>
              <?php endif; ?>
            </td>
            <td>
              <button class="button" style="padding:6px 10px; font-size:13px;" data-bs-toggle="modal" data-bs-target="#modalStock<?= $p['product_id'] ?>">
                <i class="bi bi-pencil"></i> Editar
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modales para cada producto -->
<?php foreach ($products as $p): ?>
  <div class="modal fade" id="modalStock<?= $p['product_id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="background: var(--card); border:1px solid var(--border);">
        <div class="modal-header border-0">
          <h5 class="modal-title"><?= htmlspecialchars($p['name']) ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="post" enctype="multipart/form-data">
          <div class="modal-body">
            <div style="margin-bottom:12px;">
              <div class="label">Stock actual</div>
              <div style="font-size:24px; font-weight:700; color:var(--accent);"><?= (int)$p['stock_quantity'] ?> unidades</div>
            </div>
            
            <!-- Imagen actual -->
            <?php if (!empty($p['image_url'])): ?>
              <div style="margin-bottom:12px;">
                <div class="label">Imagen actual</div>
                <img id="current_img_<?= $p['product_id'] ?>" src="secciones/Productos/img/<?= htmlspecialchars($p['image_url']) ?>" style="max-width:150px; height:auto; border-radius:4px;" alt="Imagen producto">
              </div>
            <?php endif; ?>
            
            <!-- Input para nueva imagen -->
            <div style="margin-bottom:12px;">
              <div class="label">Cambiar imagen</div>
              <input type="file" name="product_image" id="product_image_<?= $p['product_id'] ?>" class="form-control" accept="image/*" onchange="previewImage(event, <?= $p['product_id'] ?>)">
              <small class="form-text text-muted">JPG, PNG o GIF (opcional)</small>
              <img id="preview_img_<?= $p['product_id'] ?>" style="display:none; max-width:150px; height:auto; border-radius:4px; margin-top:8px;" alt="Preview">
            </div>
            
            <div class="form-grid">
              <div>
                <div class="label">Agregar cantidad</div>
                <input type="number" name="quantity" class="input" min="1" value="1" required placeholder="Ej: 10">
                <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                <input type="hidden" name="action" value="add_stock">
              </div>
            </div>
          </div>
          <div class="modal-footer border-0">
            <button type="submit" class="btn btn-primary">Agregar al stock</button>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          </div>
        </form>
        <form method="post" style="padding:12px 16px; border-top:1px solid var(--border);">
          <div class="form-grid">
            <div>
              <div class="label">O establecer stock en</div>
              <input type="number" name="quantity" class="input" min="0" value="<?= $p['stock_quantity'] ?>" required placeholder="Nueva cantidad">
              <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
              <input type="hidden" name="action" value="set_stock">
            </div>
          </div>
          <button type="submit" class="button" style="width:100%; margin-top:8px;">Establecer stock exacto</button>
        </form>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<script>
function previewImage(event, productId) {
    const input = event.target;
    const preview = document.getElementById('preview_img_' + productId);
    const currentImg = document.getElementById('current_img_' + productId);
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (currentImg) currentImg.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
        if (currentImg) currentImg.style.display = 'block';
    }
}
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
