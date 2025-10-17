<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Iniciar Sesión – Nóminas Guate</title>

  <!-- 1) Bootstrap 5 desde CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-…"
        crossorigin="anonymous">

  <!-- 2) Tu CSS adicional -->
  <link rel="stylesheet" href="../public/assets/css/login.css" />
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
  <div class="card login-card shadow-lg">
    <div class="card-body p-4">
      <h3 class="card-title text-center mb-4">Iniciar Sesión</h3>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger text-center">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form action="index.php?route=login&action=authenticate" method="post">
        <div class="mb-3">
          <label class="form-label visually-hidden" for="username">Usuario</label>
          <input
            type="text"
            id="username"
            name="username"
            class="form-control form-control-lg"
            placeholder="Usuario"
            required
          />
        </div>

        <div class="mb-3">
          <label class="form-label visually-hidden" for="password">Contraseña</label>
          <input
            type="password"
            id="password"
            name="password"
            class="form-control form-control-lg"
            placeholder="Contraseña"
            required
          />
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100">
          Entrar
        </button>
      </form>
    </div>

    <div class="card-footer text-center text-muted">
      &copy; 2025 Nóminas Guate
    </div>
  </div>

  <!-- JS de Bootstrap (opcional) -->
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-q2kH3qM4rjXr+Xgf5mYjT3xXGIRh8wPDghKynYKRWpqWomG+7FpW1tNbgmHsmCvL"
    crossorigin="anonymous"
  ></script>
</body>
</html>
