<?php
$title = "Descuento Puntual";
require __DIR__ . '/../layout/header.php';

$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$nombre = trim(($empleado['nombre'] ?? '').' '.($empleado['apellido'] ?? ''));
if ($nombre === '') $nombre = 'Empleado #'.(int)($empleado['id'] ?? 0);
$dpi   = $empleado['dpi']   ?? '';
$puesto= $empleado['puesto']?? '';
$depto = $empleado['departamento'] ?? ($empleado['departamento_nombre'] ?? '');
$salarioBase = (float)($empleado['salario_base'] ?? 0);
$hoy = date('Y-m-d');
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
      <div class="alert alert-success rounded-2xl">
        <strong>¡Listo!</strong> Descuento ingresado correctamente.<br>
        Ahora puedes <a href="<?= $BASE ?>/index.php?route=nomina&action=generate" class="alert-link">actualizar la nómina</a>.
      </div>
    <?php endif; ?>

    <?php if (!empty($errors ?? null)): ?>
      <div class="alert alert-danger rounded-2xl" role="alert" aria-live="polite">
        <strong>Revisa los campos:</strong>
        <ul class="mb-0">
          <?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="<?= $BASE ?>/index.php?route=descuentos_puntuales&action=store"
          method="post" class="needs-validation" novalidate>
      <input type="hidden" name="empleado_id" value="<?= (int)$empleado['id'] ?>">

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Fecha del descuento*</label>
          <input type="date" name="fecha" class="form-control" required
                 max="<?= $hoy ?>" value="<?= $hoy ?>">
          <div class="invalid-feedback">Selecciona una fecha válida (no futura).</div>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Tipo de descuento*</label>
          <select name="tipo_descuento" class="form-select" required id="tipo_descuento">
            <option value="">-- Seleccione --</option>
            <option value="hora">Por hora</option>
            <option value="día">Por día</option>
          </select>
          <div class="invalid-feedback">Selecciona el tipo de descuento.</div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Cantidad*</label>
          <input type="number" name="cantidad" step="0.25" min="0.25" class="form-control"
                 placeholder="Ej. 1.00" required id="cantidad">
          <div class="form-text text-muted-2">Horas o días según el tipo.</div>
          <div class="invalid-feedback">Ingresa una cantidad válida (ej. 0.5, 1, 1.5).</div>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label d-flex justify-content-between">
            <span>Monto (opcional)</span>
            <button class="btn btn-sm btn-outline-primary" type="button" id="btnUsarSugerido">
              Usar sugerido
            </button>
          </label>
          <input type="number" name="monto" step="0.01" min="0" class="form-control"
                 placeholder="Q (si lo dejas vacío, se calcula)">
          <div id="ayudaMonto" class="form-text text-muted-2">Sugerido: —</div>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Motivo*</label>
        <input type="text" name="motivo" class="form-control" maxlength="255" required
               placeholder="Ej. Llegada tarde, permiso sin goce...">
        <div class="invalid-feedback">Describe el motivo.</div>
      </div>

      <?php if (!empty($csrf ?? null)): ?>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
      <?php endif; ?>

      <div class="d-flex justify-content-between mt-4">
        <a href="<?= $BASE ?>/index.php?route=descuentos_puntuales" class="btn btn-outline-primary">Cancelar</a>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-scissors"></i> Realizar descuento
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

  // Cálculo de monto sugerido según salario base
  (function(){
    const salarioBase = <?= json_encode($salarioBase) ?>; // Q/mes
    const tipo = document.getElementById('tipo_descuento');
    const cant = document.getElementById('cantidad');
    const ayuda = document.getElementById('ayudaMonto');
    const btnSugerido = document.getElementById('btnUsarSugerido');
    const inputMonto = document.querySelector('input[name="monto"]');

    function calcularSugerido(){
      const c = parseFloat(cant.value);
      if (!salarioBase || isNaN(c) || c <= 0 || !tipo.value){
        ayuda.textContent = 'Sugerido: —';
        return null;
      }
      const valorDia  = salarioBase/30;
      const valorHora = salarioBase/30/8;
      const base = (tipo.value === 'hora') ? valorHora : valorDia;
      const sugerido = Math.round((base * c) * 100) / 100;
      ayuda.textContent = 'Sugerido: Q' + sugerido.toFixed(2) + (tipo.value==='hora' ? ' (hora)' : ' (día)');
      return sugerido;
    }

    tipo.addEventListener('change', calcularSugerido);
    cant.addEventListener('input', calcularSugerido);

    btnSugerido.addEventListener('click', () => {
      const sug = calcularSugerido();
      if (sug !== null){ inputMonto.value = sug.toFixed(2); }
    });

    // inicial
    calcularSugerido();
  })();
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
