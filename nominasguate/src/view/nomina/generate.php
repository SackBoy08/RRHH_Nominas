<?php
$title = "Generar Nómina";
require __DIR__ . '/../layout/header.php';
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
?>
<div class="card mx-auto" style="max-width:600px;">
  <div class="card-body">
    <h4 class="card-title mb-3 text-center">Generar nómina por período</h4>
    <p class="text-muted-2 text-center">Selecciona el período que deseas calcular.</p>

    <form action="<?= $BASE ?>/index.php?route=nomina&action=generate" method="post" class="needs-validation" novalidate>
      <div class="mb-3">
        <label class="form-label">Período*</label>
        <select name="id_periodo" class="form-select" required>
          <option value="">-- Seleccione un período --</option>
          <?php foreach ($periodos as $p): ?>
            <option value="<?= (int)$p['id'] ?>">
              <?= htmlspecialchars(($p['fecha_inicio'] ?? '').' → '.($p['fecha_fin'] ?? '')) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="invalid-feedback">Selecciona un período.</div>
      </div>
      <button class="btn btn-primary w-100">
        <i class="bi bi-check-circle"></i> Confirmar
      </button>
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
