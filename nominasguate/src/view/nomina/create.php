<?php
$title = "Crear Nóminas en Masa";
require __DIR__ . '/../layout/header.php';
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
?>
<div class="card mx-auto" style="max-width:1000px;">
  <div class="card-body">
    <h4 class="card-title mb-3">Crear Nóminas</h4>
    <p class="text-muted-2">Selecciona un período y los empleados que deseas incluir.</p>

    <form action="<?= $BASE ?>/index.php?route=nomina&action=store" method="post" class="needs-validation" novalidate>
      <div class="mb-4">
        <label class="form-label">Período*</label>
        <select name="id_periodo" class="form-select" required>
          <option value="">-- Seleccione --</option>
          <?php foreach ($periodos as $p): ?>
            <option value="<?= (int)$p['id'] ?>">
              <?= htmlspecialchars(($p['fecha_inicio'] ?? '').' → '.($p['fecha_fin'] ?? '')) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="invalid-feedback">Selecciona un período.</div>
      </div>

      <div class="mb-2 d-flex justify-content-between align-items-center">
        <label class="form-label m-0">Empleados activos</label>
        <div class="d-flex align-items-center gap-2">
          <input type="text" class="form-control form-control-sm" id="empFilter" placeholder="Filtrar por nombre/DPI…">
          <div class="form-check m-0">
            <input class="form-check-input" type="checkbox" id="selectAll">
            <label class="form-check-label" for="selectAll">Seleccionar todos</label>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0" id="empTable">
          <thead class="table-light">
            <tr>
              <th style="width:1%"></th>
              <th style="width:72px">#</th>
              <th>Nombre</th>
              <th>DPI</th>
              <th>Puesto</th>
              <th>Departamento</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($empleados as $e): ?>
              <?php
                $nom = trim(($e['nombre'] ?? '').' '.($e['apellido'] ?? ''));
                $dpi = $e['dpi'] ?? '';
              ?>
              <tr>
                <td class="text-center">
                  <input type="checkbox" class="emp-checkbox" name="empleados[]" value="<?= (int)$e['id'] ?>">
                </td>
                <td><?= (int)$e['id'] ?></td>
                <td class="emp-name"><?= htmlspecialchars($nom) ?></td>
                <td class="emp-dpi"><?= htmlspecialchars($dpi) ?></td>
                <td><?= htmlspecialchars($e['puesto'] ?? '-') ?></td>
                <td><?= htmlspecialchars($e['departamento'] ?? '-') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-3">
        <a href="<?= $BASE ?>/index.php?route=nomina" class="btn btn-outline-primary">Cancelar</a>
        <button class="btn btn-primary">
          <i class="bi bi-file-earmark-plus"></i> Crear nóminas
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  document.getElementById('selectAll')?.addEventListener('change', function(){
    document.querySelectorAll('.emp-checkbox').forEach(chk => chk.checked = this.checked);
  });

  // Filtro rápido por nombre/DPI
  const filter = document.getElementById('empFilter');
  const rows = Array.from(document.querySelectorAll('#empTable tbody tr'));
  filter?.addEventListener('input', ()=>{
    const q = filter.value.toLowerCase();
    rows.forEach(tr=>{
      const name = tr.querySelector('.emp-name')?.textContent.toLowerCase() || '';
      const dpi  = tr.querySelector('.emp-dpi')?.textContent.toLowerCase() || '';
      tr.style.display = (name.includes(q) || dpi.includes(q)) ? '' : 'none';
    });
  });

  // Validación Bootstrap
  (() => {
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(f => f.addEventListener('submit', ev => {
      if (!f.checkValidity()){ ev.preventDefault(); ev.stopPropagation(); }
      f.classList.add('was-validated');
    }));
  })();
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
