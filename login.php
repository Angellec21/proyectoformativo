<?php
include_once("bd.php");
if (session_status() === PHP_SESSION_NONE) session_start();

// Manejo de opción invitado vía querystring (antes de cualquier salida)
if (isset($_GET['guest']) && $_GET['guest'] == '1') {
  $_SESSION['guest'] = true;
  $_SESSION['usuario'] = 'Invitado';
  // No user_id set
  header('Location: index.php');
  exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($usuario === '' || $password === '') {
        $errors[] = 'Ingrese usuario y contraseña.';
    } else {
        try {
            $stmt = $conexion->prepare('SELECT user_id, username as usuario, password, role as rol FROM users WHERE username = :usuario LIMIT 1');
            $stmt->bindParam(':usuario', $usuario);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $hash = $row['password'];
                $ok = false;
                // Primero intentar password_verify (si la contraseña está hasheada)
                if (password_verify($password, $hash)) {
                    $ok = true;
                } else {
                    // Compatibilidad: si la contraseña está almacenada en texto plano
                    if ($password === $hash) $ok = true;
                }
                if ($ok) {
                  $_SESSION['user_id'] = $row['user_id'];
                  $_SESSION['usuario'] = $row['usuario'];
                  // Guardar rol si está disponible
                  if (isset($row['rol'])) $_SESSION['rol'] = $row['rol'];
                  header('Location: index.php');
                  exit;
                }
            }
            $errors[] = 'Usuario o contraseña incorrectos.';
        } catch (Exception $e) {
            $errors[] = 'Error en autenticación.';
        }
    }
}

include_once("templates/header.php");
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-md-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <h4 class="card-title mb-3">Iniciar sesión</h4>
          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
              <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
            </div>
          <?php endif; ?>
          <form method="post" class="needs-validation" novalidate>
            <div class="mb-3">
              <label class="form-label">Usuario</label>
              <input type="text" name="usuario" class="form-control" required>
              <div class="invalid-feedback">Ingrese su usuario.</div>
            </div>
            <div class="mb-3">
              <label class="form-label">Contraseña</label>
              <input type="password" name="password" class="form-control" required>
              <div class="invalid-feedback">Ingrese su contraseña.</div>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <button class="btn btn-primary">Ingresar</button>
              <div>
                <a href="register.php" class="btn btn-link">Registrarse</a>
                <a href="?guest=1" class="btn btn-outline-secondary">Continuar como invitado</a>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once("templates/footer.php"); ?>
<?php
// footer incluido arriba; el manejo de invitado fue movido al inicio del archivo para evitar 'headers already sent'
?>
