<?php
$title = "Registrar Toma de Vacaciones";
require __DIR__ . '/../layout/header.php';
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$today = date('Y-m-d');

/* Opcional: si el controller envía $saldo_vac (restantes actuales) lo mostramos */
$saldo_vac = isset($saldo_vac) ? (float)$saldo_vac : null;
$nom = trim(($empleado['nombre'] ?? '').' '.($empleado['apellido'] ?? ''));
?>

<div class="card mx-auto" style="max-width:680px;">
  <div class="card-body">
    <h4 class="card-title mb-2"><i class="bi bi-dash-circle me-2"></i>Registrar toma de vacaciones</h4>
    <p class="text-muted-2 mb-4">Resta días al saldo de <strong><?= htmlspecialchars($nom ?: 'Empleado #'.(int)$empleado['id']) ?></strong>.</p>

    <?php if ($saldo_vac !== null): ?>
      <div class="alert alert-info rounded-2xl py-2 mb-3">
        Saldo actual: <strong><?= number_format($saldo_vac,2) ?></strong> días.
      </div>
    <?php endif; ?>

    <form action="<?= $BASE ?>/index.php?route=vacaciones&action=remove" method="post" class="needs-validation" novalidate>
      <input type="hidden" name="empleado_id" value="<?= (int)$empleado['id'] ?>">

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="fechaInicio" class="form-label">Fecha de toma*</label>
          <input type="date" id="fechaInicio" name="fecha_inicio" class="form-control" required value="<?= $today ?>">
          <div class="invalid-feedback">Indica una fecha válida.</div>
        </div>
        <div class="col-md-6 mb-3">
          <label for="diasTomados" class="form-label">Días a restar*</label>
          <input type="number" id="diasTomados" name="dias_tomados" class="form-control" min="0.5" step="0.5" required placeholder="Ej. 1.5">
          <div class="form-text text-muted-2">Puedes usar fracciones (0.5, 1.0, 1.5…)</div>
          <div class="invalid-feedback">Ingresa una cantidad válida.</div>
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2">
        <a href="<?= $BASE ?>/index.php?route=vacaciones" class="btn btn-outline-primary">Cancelar</a>
        <button class="btn btn-danger"><i class="bi bi-check2-circle"></i> Confirmar</button>
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
