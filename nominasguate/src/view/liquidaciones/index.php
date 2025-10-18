<?php
// view/liquidaciones/index.php
$title = "Liquidaciones";
require __DIR__ . '/../layout/header.php';

$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

$q       = htmlspecialchars($_GET['q'] ?? '');
$depSel  = $_GET['departamento_id'] ?? '';
$estSel  = $_GET['estado_id'] ?? '';

function emp_id(array $e){ return (int)($e['id_empleado'] ?? $e['id'] ?? 0); }
function nombre_completo(array $e): string {
  $p = array_filter([
    $e['nombre'] ?? '', $e['nombre2'] ?? '', $e['apellido'] ?? '', $e['apellido2'] ?? '', $e['apellido_casada'] ?? ''
  ], fn($v)=>trim((string)$v)!=='');
  return trim(implode(' ', $p));
}
function estado_nombre($e): string {
  // Usa texto si ya viene; si no, mapear por id_estado
  if (!empty($e['estado'])) return (string)$e['estado'];
  if (!empty($e['estado_nombre'])) return (string)$e['estado_nombre'];
  $map = [
    1=>'Activo',2=>'Renuncia',3=>'Despido Justificado',4=>'Despido Injustificado',
    5=>'Jubilado',6=>'Vacaciones',7=>'Suspendido',8=>'Suspendido IGSS'
  ];
  $id = (int)($e['id_estado'] ?? 0);
  return $map[$id] ?? '';
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
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h2 class="mb-0">Liquidaciones de empleados</h2>
    <small class="text-muted-2">Calcula días, aguinaldo, bono 14 y vacaciones pendientes</small>
  </div>
</div>

<!-- Buscador + filtros -->
<div class="card mb-3">
  <div class="card-body">
    <form class="row g-2" action="<?= $BASE ?>/index.php" method="get" role="search" aria-label="Buscar empleados a liquidar">
      <input type="hidden" name="route"  value="liquidaciones">
      <input type="hidden" name="action" value="index">

      <div class="col-12 col-md-6 col-lg-6">
        <label class="form-label">Buscar</label>
        <input class="form-control" type="search" name="q" placeholder="Nombre, DPI o NIT…" value="<?= $q ?>">
      </div>

      <?php if (!empty($departamentos)): ?>
      <div class="col-6 col-md-3 col-lg-3">
        <label class="form-label">Departamento</label>
        <select name="departamento_id" class="form-select">
          <option value="">Todos</option>
          <?php foreach ($departamentos as $d): ?>
            <option value="<?= (int)$d['id'] ?>" <?= $depSel==$d['id']?'selected':'' ?>>
              <?= htmlspecialchars($d['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <?php if (!empty($estados)): ?>
      <div class="col-6 col-md-3 col-lg-2">
        <label class="form-label">Estado</label>
        <select name="estado_id" class="form-select">
          <option value="">Todos</option>
          <?php foreach ($estados as $es): ?>
            <option value="<?= (int)$es['id'] ?>" <?= $estSel==$es['id']?'selected':'' ?>>
              <?= htmlspecialchars($es['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <div class="col-6 col-md-3 col-lg-1 d-grid align-self-end">
        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Buscar</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Listado</span>
    <?php if (!empty($empleados)): ?>
      <span class="badge text-bg-light">Total: <?= count($empleados) ?></span>
    <?php endif; ?>
  </div>

  <div class="table-responsive">
    <table class="table table-striped align-middle mb-0">
      <thead>
        <tr>
          <th style="width:72px">#</th>
          <th>Nombre completo</th>
          <th>DPI / NIT</th>
          <th>Puesto</th>
          <th>Departamento</th>
          <th>Jornada</th>
          <th>Ingreso</th>
          <th class="text-end">Salario</th>
          <th>Estado</th>
          <th class="text-center" style="width:120px">Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($empleados)): ?>
          <tr>
            <td colspan="10" class="text-center py-5">
              <div class="text-muted-2 mb-2">No hay empleados para liquidar con los criterios actuales.</div>
              <a href="<?= $BASE ?>/index.php?route=liquidaciones&action=index" class="btn btn-outline-primary btn-sm">Limpiar filtros</a>
            </td>
          </tr>
        <?php else: foreach ($empleados as $e): ?>
          <?php
            $id   = emp_id($e);
            $nom  = nombre_completo($e);
            $dpi  = $e['dpi'] ?? '';
            $nit  = $e['nit'] ?? '';
            $pto  = $e['puesto'] ?? ($e['descripcion_puesto'] ?? '-');
            $dep  = $e['departamento'] ?? ($e['departamento_nombre'] ?? '-');
            $jor  = $e['jornada'] ?? ($e['jornada_nombre'] ?? '-');
            $ing  = !empty($e['fecha_ingreso']) ? @date('d/m/Y', strtotime($e['fecha_ingreso'])) : '-';
            $sal  = (float)($e['salario_base'] ?? 0);
            $estT = estado_nombre($e);
            $estC = estado_badge_class($estT);
          ?>
          <tr>
            <td><?= $id ?></td>
            <td><?= htmlspecialchars($nom) ?></td>
            <td><?= htmlspecialchars($dpi) ?> / <?= htmlspecialchars($nit) ?></td>
            <td><?= htmlspecialchars($pto) ?></td>
            <td><?= htmlspecialchars($dep) ?></td>
            <td><?= htmlspecialchars($jor) ?></td>
            <td><?= htmlspecialchars($ing) ?></td>
            <td class="text-end">Q<?= number_format($sal,2) ?></td>
            <td><span class="badge <?= $estC ?>"><?= htmlspecialchars($estT ?: '-') ?></span></td>
            <td class="text-center">
              <button type="button"
                      class="btn btn-sm btn-primary js-liquidar"
                      data-id="<?= $id ?>"
                      data-nombre="<?= htmlspecialchars($nom, ENT_QUOTES) ?>">
                <i class="bi bi-calculator"></i>
              </button>
              <a href="<?= $BASE ?>/index.php?route=liquidaciones&action=form&id=<?= urlencode($id) ?>"
                 class="d-none" id="link-liq-<?= $id ?>"></a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <?php if (!empty($pagination) && isset($pagination['page'],$pagination['last_page'])): ?>
    <?php
      $page = max(1, (int)$pagination['page']);
      $last = max(1, (int)$pagination['last_page']);
      $params = $_GET; unset($params['page']);
      $base = $BASE.'/index.php?'.http_build_query($params);
      $mk = fn($p)=> $base.'&page='.$p;
    ?>
    <div class="card-footer">
      <nav aria-label="Paginación liquidaciones">
        <ul class="pagination mb-0">
          <li class="page-item <?= $page<=1?'disabled':'' ?>">
            <a class="page-link" href="<?= $mk(max(1,$page-1)) ?>" aria-label="Anterior">&laquo;</a>
          </li>
          <?php for($p=max(1,$page-2); $p<=min($last,$page+2); $p++): ?>
            <li class="page-item <?= $p==$page?'active':'' ?>">
              <a class="page-link" href="<?= $mk($p) ?>"><?= $p ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?= $page>=$last?'disabled':'' ?>">
            <a class="page-link" href="<?= $mk(min($last,$page+1)) ?>" aria-label="Siguiente">&raquo;</a>
          </li>
        </ul>
      </nav>
    </div>
  <?php endif; ?>
</div>

<!-- Modal confirmación -->
<div class="modal fade" id="modalLiquidar" tabindex="-1" aria-hidden="true" aria-labelledby="modalLiquidarLabel">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-2xl">
      <div class="modal-header">
        <h5 class="modal-title" id="modalLiquidarLabel">Liquidar empleado</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" id="modalLiquidarBody">
        <!-- Se llena por JS -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancelar</button>
        <a href="#" class="btn btn-primary" id="btnIrLiquidar">
          <i class="bi bi-calculator"></i> Continuar
        </a>
      </div>
    </div>
  </div>
</div>

<script>
  // Abrir modal con nombre e ir al form al confirmar
  document.querySelectorAll('.js-liquidar').forEach(btn=>{
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-id');
      const nombre = btn.getAttribute('data-nombre');
      document.getElementById('modalLiquidarBody').innerHTML =
        `¿Deseas iniciar la liquidación de <strong>${nombre}</strong>? Podrás revisar y confirmar los cálculos.`;
      const link = document.getElementById('link-liq-' + id);
      const go = document.getElementById('btnIrLiquidar');
      go.setAttribute('href', link.getAttribute('href'));
      new bootstrap.Modal(document.getElementById('modalLiquidar')).show();
    });
  });
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
