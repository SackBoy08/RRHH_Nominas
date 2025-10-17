<?php
$title = "Agregar Horas Extras";
require __DIR__ . '/../layout/header.php';

$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$nombre = trim(($empleado['nombre'] ?? '').' '.($empleado['apellido'] ?? ''));
if ($nombre === '') $nombre = 'Empleado #'.(int)($empleado['id'] ?? 0);
$dpi   = $empleado['dpi']   ?? '';
$puesto= $empleado['puesto']?? '';
$depto = $empleado['departamento'] ?? ($empleado['departamento_nombre'] ?? '');
$salarioBase = (float)($empleado['salario_base'] ?? 0);
?>

<div class="card mx-auto" style="max-width:720px;">
  <div class="card-body">
    <h4 class="card-title mb-1"><?= htmlspecialchars($nombre) ?></h4>
    <p class="text-muted-2 mb-4">
      <?= $dpi ? 'DPI '.$dpi.' · ' : '' ?><?= htmlspecialchars($puesto ?: '-') ?><?= $depto ? ' · '.htmlspecialchars($depto) : '' ?>
      <?php if ($salarioBase > 0): ?>
        <span class="badge text-bg-light ms-2">Salario base: Q<?= number_format($salarioBase,2) ?></span>
      <?php endif; ?>
    </p>

    <?php if (!empty($success)): ?>
      <?php
        // Si el controller pasó id_periodo en GET para mostrar CTA de regenerar
        $idp = (int)($_GET['id_periodo'] ?? 0);
      ?>
      <div class="alert alert-success rounded-2xl">
        <strong>¡Listo!</strong> Horas extra registradas.<br>
        <?php if ($idp): ?>
          Ahora puedes <a class="alert-link" href="<?= $BASE ?>/index.php?route=nomina&action=generate&id_periodo=<?= $idp ?>">actualizar la nómina del período</a>.
        <?php else: ?>
          Ve al módulo de nómina para recalcular el período.
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <form action="<?= $BASE ?>/index.php?route=horas_extras&action=store" method="post" class="needs-validation" novalidate>
      <input type="hidden" name="empleado_id" value="<?= (int)$empleado['id'] ?>">

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Período*</label>
          <select name="id_periodo" id="id_periodo" class="form-select" required>
            <option value="">-- Seleccione --</option>
            <?php foreach ($periodos as $p): ?>
              <?php
                $pid = (int)$p['id'];
                $label = ($p['tipo_periodo'] ?? 'Período')." — ".($p['fecha_inicio'] ?? '')." → ".($p['fecha_fin'] ?? '');
              ?>
              <option value="<?= $pid ?>"><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="invalid-feedback">Selecciona un período.</div>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Tipo de hora extra*</label>
          <select name="id_tipohoraextra" id="id_tipohoraextra" class="form-select" required>
            <option value="">-- Seleccione --</option>
            <?php foreach ($tiposHE as $t): ?>
              <option value="<?= (int)$t['id'] ?>" data-multi="<?= htmlspecialchars($t['multiplicador']) ?>">
                <?= htmlspecialchars($t['descripcion'].' (×'.$t['multiplicador'].')') ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="invalid-feedback">Selecciona un tipo de hora extra.</div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Cantidad de horas*</label>
          <input type="number" name="cantidad" id="cantidad" class="form-control"
                 step="0.25" min="0.25" placeholder="Ej. 1.00" required>
          <div class="form-text text-muted-2">Puedes usar 0.25, 0.5, 1.0…</div>
          <div class="invalid-feedback">Ingresa una cantidad válida.</div>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Monto estimado</label>
          <input type="text" class="form-control" id="monto_estimado" value="—" disabled>
          <div class="form-text text-muted-2" id="ayudaValorHora">—</div>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Observaciones (opcional)</label>
        <input type="text" name="observaciones" class="form-control" maxlength="255" placeholder="Ej. turno extendido…">
      </div>

      <?php if (!empty($csrf ?? null)): ?>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
      <?php endif; ?>

      <div class="d-flex justify-content-between mt-4">
        <a href="<?= $BASE ?>/index.php?route=horas_extras" class="btn btn-outline-primary">Cancelar</a>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-clock-history"></i> Agregar horas extra
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  // Validación Bootstrap
  (() => {
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
      form.addEventListener('submit', ev => {
        if (!form.checkValidity()) { ev.preventDefault(); ev.stopPropagation(); }
        form.classList.add('was-validated');
      }, false);
    });
  })();

  // Cálculo estimado de pago según salario base, cantidad y multiplicador
  (function(){
    const salarioBase = <?= json_encode($salarioBase) ?>; // Q/mes
    const selectTipo = document.getElementById('id_tipohoraextra');
    const inputCant  = document.getElementById('cantidad');
    const out        = document.getElementById('monto_estimado');
    const ayuda      = document.getElementById('ayudaValorHora');

    function update(){
      const cant = parseFloat(inputCant.value);
      const opt  = selectTipo.options[selectTipo.selectedIndex];
      const multi = opt ? parseFloat(opt.getAttribute('data-multi')) : NaN;

      if (!salarioBase || isNaN(cant) || cant<=0 || isNaN(multi)){
        out.value = '—';
        ayuda.textContent = '—';
        return;
      }
      const valorHora = salarioBase/30/8; // mismo criterio del SP
      const pago = Math.round(valorHora * cant * multi * 100) / 100;
      out.value = 'Q'+pago.toFixed(2);
      ayuda.textContent = '≈ Q'+(valorHora.toFixed(2))+' por hora base · multiplicador ×'+multi;
    }

    selectTipo?.addEventListener('change', update);
    inputCant?.addEventListener('input', update);
    update();
  })();
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
