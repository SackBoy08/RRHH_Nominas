<?php
$title = "Ajustes";
require __DIR__ . '/../layout/header.php';
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h2 class="mb-0">Ajustes de la aplicación</h2>
    <small class="text-muted-2">Parámetros generales y fiscales</small>
  </div>
  <a href="<?= $BASE ?>/index.php?route=usuarios" class="btn btn-outline-primary">
    <i class="bi bi-people"></i> Usuarios
  </a>
</div>

<?php if ($flash): ?>
  <div class="alert alert-<?= htmlspecialchars($flash['type'] ?? 'info') ?> rounded-2xl">
    <?= htmlspecialchars($flash['msg'] ?? '') ?>
  </div>
<?php endif; ?>

<div class="card mx-auto" style="max-width:760px;">
  <div class="card-body">
    <form action="<?= $BASE ?>/index.php?route=config&action=ajustes" method="post" class="needs-validation" novalidate>
      <div class="mb-3">
        <label class="form-label">Nombre de la empresa*</label>
        <input type="text" name="NOMBRE_EMPRESA" class="form-control" required value="<?= htmlspecialchars($S['NOMBRE_EMPRESA']) ?>">
      </div>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">% IGSS (empleado)*</label>
          <input type="number" step="0.0001" min="0" name="IGSS_PORCENTAJE" class="form-control" required value="<?= htmlspecialchars($S['IGSS_PORCENTAJE']) ?>">
          <div class="form-text text-muted-2">Ej. 0.0483 = 4.83%</div>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">% ISR (base)*</label>
          <input type="number" step="0.0001" min="0" name="ISR_PORCENTAJE" class="form-control" required value="<?= htmlspecialchars($S['ISR_PORCENTAJE']) ?>">
          <div class="form-text text-muted-2">Ej. 0.05 = 5%</div>
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2">
        <a href="<?= $BASE ?>/index.php?route=menu" class="btn btn-outline-primary">Volver</a>
        <button class="btn btn-primary"><i class="bi bi-save"></i> Guardar ajustes</button>
      </div>
    </form>
  </div>
</div>

<script>
(() => {
  const forms = document.querySelectorAll('.needs-validation');
  forms.forEach(f => f.addEventListener('submit', ev => {
    if (!f.checkValidity()){ ev.preventDefault(); ev.stopPropagation(); }
    f.classList.add('was-validated');
  }));
})();
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
