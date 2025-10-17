<?php
$title = "Crear Usuario";
require __DIR__ . '/../layout/header.php';
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
?>
<div class="card mx-auto" style="max-width:640px;">
  <div class="card-body">
    <h4 class="card-title mb-2"><i class="bi bi-person-plus me-2"></i>Nuevo usuario</h4>
    <p class="text-muted-2 mb-4">Crea una cuenta y asigna un rol de acceso.</p>

    <?php if ($flash): ?>
      <div class="alert alert-<?= htmlspecialchars($flash['type'] ?? 'info') ?> rounded-2xl">
        <?= htmlspecialchars($flash['msg'] ?? '') ?>
      </div>
    <?php endif; ?>

    <form action="<?= $BASE ?>/index.php?route=usuarios&action=create" method="post" class="needs-validation" novalidate>
      <div class="mb-3">
        <label class="form-label">Usuario*</label>
        <input type="text" name="username" class="form-control" required minlength="3" maxlength="50" placeholder="Ej. jlopez">
        <div class="invalid-feedback">Ingresa un usuario válido (3-50 caracteres).</div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Contraseña*</label>
          <input type="password" name="password" class="form-control" required minlength="6">
          <div class="invalid-feedback">Mínimo 6 caracteres.</div>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Confirmar contraseña*</label>
          <input type="password" name="password_confirm" class="form-control" required minlength="6">
          <div class="invalid-feedback">Debe coincidir con la contraseña.</div>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Rol*</label>
        <select name="rol" class="form-select" required>
          <option value="usuario">Usuario</option>
          <option value="admin">Admin</option>
        </select>
      </div>

      <div class="d-flex justify-content-end gap-2">
        <a href="<?= $BASE ?>/index.php?route=usuarios" class="btn btn-outline-primary">Cancelar</a>
        <button class="btn btn-primary"><i class="bi bi-save"></i> Crear</button>
      </div>
    </form>
  </div>
</div>

<script>
(() => {
  const form = document.querySelector('.needs-validation');
  form.addEventListener('submit', (ev)=>{
    if (!form.checkValidity()){
      ev.preventDefault(); ev.stopPropagation();
    } else {
      const p  = form.querySelector('input[name="password"]').value;
      const p2 = form.querySelector('input[name="password_confirm"]').value;
      if (p !== p2){ ev.preventDefault(); ev.stopPropagation(); alert('Las contraseñas no coinciden.'); }
    }
    form.classList.add('was-validated');
  });
})();
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
