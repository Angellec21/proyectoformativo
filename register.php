<?php
include_once('bd.php');
if (session_status() === PHP_SESSION_NONE) session_start();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    if ($usuario === '' || $password === '') {
        $errors[] = 'Usuario y contraseña son obligatorios.';
    } else {
        try {
            // verificar usuario único
            $s = $conexion->prepare('SELECT user_id FROM usuarios WHERE usuario = :usuario LIMIT 1');
            $s->bindParam(':usuario', $usuario);
            $s->execute();
            if ($s->fetch()) {
                $errors[] = 'El nombre de usuario ya existe.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins = $conexion->prepare('INSERT INTO usuarios (user_id, usuario, password, email, rol) VALUES (NULL, :usuario, :password, :email, :rol)');
                $ins->bindParam(':usuario', $usuario);
                $ins->bindParam(':password', $hash);
                $ins->bindParam(':email', $email);
                $roleDefault = 'user';
                $ins->bindParam(':rol', $roleDefault);
                $ins->execute();
                header('Location: login.php?mensaje=' . urlencode('Registro exitoso. Inicie sesión.'));
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Error al registrar usuario.';
        }
    }
}

include_once('templates/header.php');
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-md-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <h4 class="card-title mb-3">Registro de usuario</h4>
          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
          <?php endif; ?>
          <form method="post" class="needs-validation" novalidate>
            <div class="mb-3">
              <label class="form-label">Usuario</label>
              <input type="text" name="usuario" class="form-control" required>
              <div class="invalid-feedback">Ingrese un usuario.</div>
            </div>
            <div class="mb-3">
              <label class="form-label">Contraseña</label>
              <input type="password" name="password" class="form-control" required>
              <div class="invalid-feedback">Ingrese una contraseña.</div>
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control">
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <button class="btn btn-primary">Registrar</button>
              <a href="login.php" class="btn btn-link">Volver al login</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once('templates/footer.php'); ?>
