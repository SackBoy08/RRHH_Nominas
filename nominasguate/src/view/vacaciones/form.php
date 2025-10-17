<?php
$title = "Asignar Vacaciones";
require __DIR__ . '/../layout/header.php';
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$today = date('Y-m-d');

$empleadoPre = isset($_GET['empleado_id']) ? (int)$_GET['empleado_id'] : 0; // permite venir precargado
?>

<div class="card mx-auto" style="max-width:680px;">
  <div class="card-body">
    <h4 class="card-title mb-2"><i class="bi bi-calendar-plus me-2"></i>Asignar vacaciones</h4>
    <p class="text-muted-2 mb-4">Selecciona el empleado, la fecha de inicio y los días a asignar.</p>

    <form action="<?= $BASE ?>/index.php?route=vacaciones&action=assign" method="post" class="needs-validation" novalidate>
      <div class="mb-3">
        <label for="empleadoSelect" class="form-label">Empleado*</label>
        <select id="empleadoSelect" name="empleado_id" class="form-select" required>
          <option value="">-- Seleccione --</option>
          <?php foreach ($empleados as $e): ?>
            <option value="<?= (int)$e['id'] ?>" <?= $empleadoPre===(int)$e['id']?'selected':'' ?>>
              <?= htmlspecialchars(($e['nombre'] ?? '').' '.($e['apellido'] ?? '')) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="invalid-feedback">Selecciona un empleado.</div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="fechaInicio" class="form-label">Fecha de inicio*</label>
          <input type="date" id="fechaInicio" name="fecha_inicio" class="form-control" required value="<?= $today ?>">
          <div class="invalid-feedback">Indica una fecha válida.</div>
        </div>
        <div class="col-md-6 mb-3">
          <label for="diasAsignados" class="form-label">Días a asignar*</label>
          <input type="number" id="diasAsignados" name="dias_asignados" class="form-control" min="0.5" step="0.5" required placeholder="Ej. 15">
          <div class="form-text text-muted-2">Puedes usar fracciones (0.5, 1.0, 1.5…)</div>
          <div class="invalid-feedback">Ingresa una cantidad válida.</div>
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-1">
        <a href="<?= $BASE ?>/index.php?route=vacaciones" class="btn btn-outline-primary">Cancelar</a>
        <button class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
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
