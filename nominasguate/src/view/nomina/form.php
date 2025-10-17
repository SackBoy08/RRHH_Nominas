<?php
$title = "Generar Nómina (Individual)";
require __DIR__ . '/../layout/header.php';
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$monthDefault = date('Y-m');
?>
<div class="card mx-auto" style="max-width:700px;">
  <div class="card-body">
    <h4 class="card-title mb-3">Generar nómina</h4>
    <p class="text-muted-2">Calcula y guarda la nómina para un empleado y período específico.</p>

    <form action="<?= $BASE ?>/index.php?route=nomina&action=generate" method="post" class="needs-validation" novalidate>
      <div class="mb-3">
        <label class="form-label">Empleado*</label>
        <select name="empleado_id" class="form-select" required>
          <option value="">-- Seleccione --</option>
          <?php foreach ($empleados as $e): ?>
            <option value="<?= (int)$e['id'] ?>"><?= htmlspecialchars(($e['nombre'] ?? '').' '.($e['apellido'] ?? '')) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="invalid-feedback">Selecciona un empleado.</div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Tipo de período*</label>
          <select name="tipo_periodo" class="form-select" required>
            <option value="semanal">Semanal</option>
            <option value="quincenal">Quincenal</option>
            <option value="mensual" selected>Mensual</option>
          </select>
          <div class="invalid-feedback">Selecciona un tipo de período.</div>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Mes / Año*</label>
          <input type="month" name="periodo" class="form-control" required value="<?= $monthDefault ?>">
          <div class="invalid-feedback">Selecciona el mes/año.</div>
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2">
        <a href="<?= $BASE ?>/index.php?route=nomina" class="btn btn-outline-primary">Cancelar</a>
        <button class="btn btn-primary"><i class="bi bi-check2-circle"></i> Calcular y guardar</button>
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
