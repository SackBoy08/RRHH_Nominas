<?php
// view/liquidaciones/form.php
$title = "Liquidar Empleado";
require __DIR__ . '/../layout/header.php';

$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

// Helpers
function emp_id(array $e){ return (int)($e['id_empleado'] ?? $e['id'] ?? 0); }
function nombre_completo(array $e): string {
  $p = array_filter([
    $e['nombre'] ?? '', $e['nombre2'] ?? '', $e['nombre3'] ?? '',
    $e['apellido'] ?? '', $e['apellido2'] ?? '', $e['apellido_casada'] ?? ''
  ], fn($v)=>trim((string)$v)!=='');
  return trim(implode(' ', $p));
}
function estado_nombre($e): string {
  if (!empty($e['estado'])) return (string)$e['estado'];
  if (!empty($e['estado_nombre'])) return (string)$e['estado_nombre'];
  $map=[1=>'Activo',2=>'Renuncia',3=>'Despido Justificado',4=>'Despido Injustificado',5=>'Jubilado',6=>'Vacaciones',7=>'Suspendido',8=>'Suspendido IGSS'];
  return $map[(int)($e['id_estado'] ?? 0)] ?? '-';
}
function estado_badge_class($nombre): string {
  $n = mb_strtolower($nombre);
  if (str_contains($n,'activo')) return 'text-bg-success';
  if (str_contains($n,'vacacion')) return 'text-bg-info';
  if (str_contains($n,'jubil')) return 'text-bg-secondary';
  if (str_contains($n,'suspend') || str_contains($n,'renuncia')) return 'text-bg-warning';
  if (str_contains($n,'despido')) return 'text-bg-danger';
  return 'text-bg-light';
}

$empId     = emp_id($emp);
$empNombre = nombre_completo($emp);
$empEstado = estado_nombre($emp);
$empEstadoClass = estado_badge_class($empEstado);
$fechaIngreso = $emp['fecha_ingreso'] ?? '';
$salarioBase  = (float)($emp['salario_base'] ?? 0);
$today        = date('Y-m-d');

// Preselección si viene de un back/refresh
$tipoSel   = $_GET['tipo']  ?? '';
$fechaSel  = $_GET['fecha'] ?? $today;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h2 class="mb-1">Liquidar a <?= htmlspecialchars($empNombre) ?></h2>
    <div class="d-flex gap-2 align-items-center">
      <span class="badge <?= $empEstadoClass ?>"><?= htmlspecialchars($empEstado) ?></span>
      <?php if ($salarioBase>0): ?>
        <span class="badge text-bg-light">Salario base: Q<?= number_format($salarioBase,2) ?></span>
      <?php endif; ?>
    </div>
  </div>
  <a href="<?= $BASE ?>/index.php?route=liquidaciones&action=index" class="btn btn-outline-primary">
    <i class="bi bi-arrow-left"></i> Volver
  </a>
</div>

<div class="card">
  <div class="card-body">
    <form id="formLiquidar" action="<?= $BASE ?>/index.php" method="get" class="needs-validation" novalidate>
      <input type="hidden" name="route"  value="liquidaciones">
      <input type="hidden" name="action" value="process">
      <input type="hidden" name="id"     value="<?= htmlspecialchars($empId) ?>">

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">ID Empleado</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($empId) ?>" disabled>
        </div>
        <div class="col-md-4">
          <label class="form-label">Fecha de ingreso</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($fechaIngreso) ?>" disabled id="fecha_ingreso">
        </div>
        <div class="col-md-4">
          <label class="form-label">Antigüedad (aprox.)</label>
          <input type="text" class="form-control" value="—" disabled id="antiguedad">
        </div>

        <div class="col-md-6">
          <label for="tipo" class="form-label">Tipo de liquidación*</label>
          <select name="tipo" id="tipo" class="form-select" required>
            <option value="" <?= $tipoSel===''?'selected':'' ?> disabled>Selecciona un tipo…</option>
            <option value="Renuncia" <?= $tipoSel==='Renuncia'?'selected':'' ?>>Renuncia</option>
            <option value="Despido"  <?= $tipoSel==='Despido'?'selected':''  ?>>Despido</option>
          </select>
          <div class="invalid-feedback">Selecciona un tipo.</div>
        </div>

        <div class="col-md-6">
          <label for="fecha" class="form-label">Fecha de liquidación*</label>
          <input type="date" name="fecha" id="fecha" class="form-control" required
                 max="<?= $today ?>" value="<?= htmlspecialchars($fechaSel) ?>">
          <div class="form-text text-muted-2">Usaremos esta fecha para el cálculo (aguinaldo, bono 14, días laborados, vacaciones).</div>
          <div class="invalid-feedback">Selecciona una fecha válida (no futura).</div>
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center mt-4">
        <div class="text-muted-2">
          <small>Se abrirá un resumen con los cálculos antes de guardar definitivamente.</small>
        </div>
        <div class="d-flex gap-2">
          <a href="<?= $BASE ?>/index.php?route=liquidaciones&action=index" class="btn btn-outline-primary">Cancelar</a>
          <button type="button" class="btn btn-primary" id="btnAbrirModal">
            <i class="bi bi-calculator"></i> Calcular
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Modal de confirmación -->
<div class="modal fade" id="modalConfirm" tabindex="-1" aria-hidden="true" aria-labelledby="modalConfirmLabel">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-2xl">
      <div class="modal-header">
        <h5 class="modal-title" id="modalConfirmLabel">Confirmar liquidación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" id="modalConfirmBody">
        <!-- Se llena con JS -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Volver</button>
        <button type="button" class="btn btn-primary" id="btnConfirmar">
          <i class="bi bi-play-circle"></i> Calcular ahora
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  // Validación Bootstrap
  (() => {
    const form = document.getElementById('formLiquidar');
    function validate(){ return form.checkValidity(); }
    document.getElementById('btnAbrirModal').addEventListener('click', ()=>{
      if (!validate()){
        form.classList.add('was-validated');
        return;
      }
      // Rellenar modal
      const tipo  = document.getElementById('tipo').value;
      const fecha = document.getElementById('fecha').value;
      const nombre= <?= json_encode($empNombre) ?>;
      const body  = document.getElementById('modalConfirmBody');
      body.innerHTML = `
        <p>Vas a iniciar el cálculo de liquidación para <strong>${nombre}</strong>.</p>
        <ul class="mb-2">
          <li><strong>Tipo:</strong> ${tipo}</li>
          <li><strong>Fecha de liquidación:</strong> ${fecha}</li>
        </ul>
        <p class="text-muted-2 mb-0">En el siguiente paso verás el detalle (días laborados, aguinaldo, bono 14, vacaciones y total) antes de confirmar.</p>
      `;
      new bootstrap.Modal(document.getElementById('modalConfirm')).show();
    });

    document.getElementById('btnConfirmar').addEventListener('click', ()=>{
      form.submit(); // GET → route=liquidaciones&action=process&id=&tipo=&fecha=
    });
  })();

  // Antigüedad aproximada (INGRESO → FECHA)
  (function(){
    const fIngreso = document.getElementById('fecha_ingreso')?.value;
    const fSel     = document.getElementById('fecha');
    const out      = document.getElementById('antiguedad');

    function calcAntiguedad(){
      if (!fIngreso || !fSel.value){ out.value = '—'; return; }
      const a = new Date(fIngreso), b = new Date(fSel.value);
      if (isNaN(a.getTime()) || isNaN(b.getTime())) { out.value='—'; return; }
      let y = b.getFullYear() - a.getFullYear();
      let m = b.getMonth() - a.getMonth();
      let d = b.getDate()  - a.getDate();
      if (d < 0) { m -= 1; d += 30; }
      if (m < 0) { y -= 1; m += 12; }
      if (y<0){ out.value='—'; return; }
      out.value = `${y}a ${m}m ${d}d`;
    }
    fSel?.addEventListener('change', calcAntiguedad);
    calcAntiguedad();
  })();
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
