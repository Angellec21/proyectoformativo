<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Requerir bd
require_once __DIR__ . '/bd.php';

// Forzar login
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Manejo de POST para actualizar perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_theme'])) {
        $_SESSION['theme'] = $_SESSION['theme'] === 'light' ? 'dark' : 'light';
        header('Location: perfil.php');
        exit;
    }
    
    $usuarioNuevo = trim($_POST['usuario'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $newRole = $_POST['rol'] ?? null;

    if ($usuarioNuevo === '' || $email === '') {
        $error = 'Usuario y email son obligatorios.';
    } else {
        try {
            // Solo actualizar rol si la sesión actual es admin
            if (!empty($_SESSION['rol']) && $_SESSION['rol'] === 'admin' && $newRole !== null) {
                $stmt = $conexion->prepare('UPDATE users SET username = ?, email = ?, role = ? WHERE user_id = ?');
                $stmt->execute([$usuarioNuevo, $email, $newRole, $userId]);
            } else {
                $stmt = $conexion->prepare('UPDATE users SET username = ?, email = ? WHERE user_id = ?');
                $stmt->execute([$usuarioNuevo, $email, $userId]);
            }
            $mensaje = 'Perfil actualizado correctamente.';
            $_SESSION['usuario'] = $usuarioNuevo;
            // Si el admin cambió su propio rol en su perfil, actualizar la sesión
            if (!empty($_SESSION['user_id']) && $_SESSION['user_id'] == $userId && !empty($newRole) && $_SESSION['rol'] === 'admin') {
                $_SESSION['rol'] = $newRole;
            }
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'El nombre de usuario ya existe. Elija otro.';
            } else {
                $error = 'Error al actualizar el perfil.';
            }
        }
    }
}

// Cargar datos actuales
$stmt = $conexion->prepare('SELECT user_id, username, email, role FROM users WHERE user_id = ?');
$stmt->execute([$userId]);
$usuario = $stmt->fetch();

include 'templates/header.php';
?>

<div class="container py-4">
    <h2>Perfil de usuario</h2>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" class="row g-3" novalidate>
        <div class="col-md-6">
            <label class="form-label">Usuario</label>
            <input type="text" name="usuario" class="form-control" value="<?php echo htmlspecialchars($usuario['username'] ?? ''); ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" required>
        </div>
        <div class="col-12">
            <label class="form-label">Rol</label>
            <?php if (!empty($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                <select name="rol" class="form-select">
                    <option value="admin" <?php echo (($usuario['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Administrador</option>
                    <option value="technician" <?php echo (($usuario['role'] ?? '') === 'technician') ? 'selected' : ''; ?>>Técnico</option>
                    <option value="frontdesk" <?php echo (($usuario['role'] ?? '') === 'frontdesk') ? 'selected' : ''; ?>>Recepción</option>
                </select>
            <?php else: ?>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($usuario['role'] ?? ''); ?>" disabled>
            <?php endif; ?>
        </div>
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary">Guardar</button>
            <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>

    <hr style="margin: 2rem 0; border-color: var(--border);">

    <div class="card" style="max-width: 500px;">
        <h3 style="margin: 0 0 12px; font-size: 16px;">Tema de la interfaz</h3>
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong><?php echo ($_SESSION['theme'] ?? 'dark') === 'dark' ? 'Modo Oscuro' : 'Modo Claro'; ?></strong>
                <div class="text-muted" style="font-size: 13px;">
                    <?php echo ($_SESSION['theme'] ?? 'dark') === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'; ?>
                </div>
            </div>
            <form method="post" class="m-0">
                <input type="hidden" name="toggle_theme" value="1">
                <button class="button" style="padding: 8px 16px;">
                    <i class="bi bi-<?php echo ($_SESSION['theme'] ?? 'dark') === 'dark' ? 'sun' : 'moon'; ?>"></i>
                    Cambiar
                </button>
            </form>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
