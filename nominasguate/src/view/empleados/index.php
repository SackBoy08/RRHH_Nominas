<?php
$title = "Empleados";
require __DIR__ . '/../layout/header.php';

/**
 * Filtros opcionales: si tu controller pasa $departamentos, $jornadas, $estados,
 * los mostramos. Si no existen, el form se limita a la búsqueda.
 *
 * Estructuras esperadas (opcionales):
 * $departamentos = [ ['id'=>1,'nombre'=>'Gerencia'], ... ];
 * $jornadas      = [ ['id'=>1,'nombre'=>'Completa'], ... ];
 * $estados       = [ ['id'=>1,'nombre'=>'Activo'], ... ];
 */

// Helper: nombre completo “limpio”
function nombre_completo(array $e): string {
  $p = array_filter([
    $e['nombre'] ?? '', $e['nombre2'] ?? '', $e['nombre3'] ?? '',
    $e['apellido'] ?? '', $e['apellido2'] ?? '', $e['apellido_casada'] ?? ''
  ], fn($v)=>trim((string)$v)!=='');
  return trim(implode(' ', $p));
}

// Helper: mapa de estados (fallback si vienes solo con id_estado)
function estado_nombre($id_estado): string {
  $map = [
    1=>'Activo', 2=>'Renuncia', 3=>'Despido Justificado', 4=>'Despido Injustificado',
    5=>'Jubilado', 6=>'Vacaciones', 7=>'Suspendido', 8=>'Suspendido IGSS'
  ];
  return $map[(int)$id_estado] ?? (string)$id_estado;
}
function estado_badge_class($nombre): string {
  $n = mb_strtolower($nombre);
  if (str_contains($n,'activo')) return 'text-bg-success';
  if (str_contains($n,'vacacion')) return 'text-bg-info';
  if (str_contains($n,'jubil')) return 'text-bg-secondary';
  if (str_contains($n,'suspend')) return 'text-bg-warning';
  if (str_contains($n,'renuncia')) return 'text-bg-warning';
  if (str_contains($n,'despido')) return 'text-bg-danger';
  return 'text-bg-light';
}

$q = htmlspecialchars($_GET['q'] ?? '');
$depSel = $_GET['departamento_id'] ?? '';
$jorSel = $_GET['jornada_id'] ?? '';
$estSel = $_GET['estado_id'] ?? '';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h2 class="mb-0">Empleados</h2>
    <small class="text-muted-2">Gestión de altas, bajas y actualización de datos</small>
  </div>
  <div class="d-flex gap-2">
    <a href="index.php?route=empleados&action=create" class="btn btn-primary">
      <i class="bi bi-plus-lg"></i> Nuevo empleado
    </a>
    <a href="index.php?route=empleados&action=export" class="btn btn-outline-primary" title="Exportar CSV">
      <i class="bi bi-download"></i> Exportar
    </a>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body">
    <form class="row g-2" action="index.php" method="get" role="search" aria-label="Buscar empleados">
      <input type="hidden" name="route"  value="empleados">
      <input type="hidden" name="action" value="index">

      <div class="col-12 col-md-6 col-lg-5">
        <label class="form-label">Buscar</label>
        <input type="text" name="q" class="form-control"
               placeholder="ID, nombre o DPI" value="<?= $q ?>">
      </div>

      <?php if (!empty($departamentos)): ?>
      <div class="col-6 col-md-3 col-lg-2">
        <label class="form-label">Departamento</label>
        <select name="departamento_id" class="form-select">
          <option value="">Todos</option>
          <?php foreach ($departamentos as $d): ?>
            <option value="<?= $d['id'] ?>" <?= $depSel==$d['id']?'selected':'' ?>>
              <?= htmlspecialchars($d['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <?php if (!empty($jornadas)): ?>
      <div class="col-6 col-md-3 col-lg-2">
        <label class="form-label">Jornada</label>
        <select name="jornada_id" class="form-select">
          <option value="">Todas</option>
          <?php foreach ($jornadas as $j): ?>
            <option value="<?= $j['id'] ?>" <?= $jorSel==$j['id']?'selected':'' ?>>
              <?= htmlspecialchars($j['nombre']) ?>
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
            <option value="<?= $es['id'] ?>" <?= $estSel==$es['id']?'selected':'' ?>>
              <?= htmlspecialchars($es['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <div class="col-6 col-md-3 col-lg-1 d-grid align-self-end">
        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Buscar</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Listado</span>
    <?php if (!empty($all)): ?>
      <span class="badge text-bg-light">Total: <?= count($all) ?></span>
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
          <th class="text-center" style="width:140px">Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($all)): ?>
        <tr>
          <td colspan="10" class="text-center py-5">
            <div class="text-muted-2 mb-2">No hay empleados que coincidan con tu búsqueda.</div>
            <a href="index.php?route=empleados&action=create" class="btn btn-primary btn-sm">
              <i class="bi bi-person-plus"></i> Crear el primero
            </a>
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($all as $e): ?>
          <?php
            $nombre = nombre_completo($e);
            $dep = $e['departamento'] ?? ($e['departamento_nombre'] ?? '-');
            $jor = $e['jornada'] ?? ($e['jornada_nombre'] ?? '-');
            $fing = !empty($e['fecha_ingreso']) ? @date('d/m/Y', strtotime($e['fecha_ingreso'])) : '-';
            // Si ya te llega $e['estado'] como texto, úsalo; si no, mapear desde id_estado
            $estadoTxt = $e['estado'] ?? ($e['estado_nombre'] ?? estado_nombre($e['id_estado'] ?? ''));
            $estadoClass = estado_badge_class($estadoTxt);
          ?>
          <tr>
            <td><?= (int)$e['id'] ?></td>
            <td><?= htmlspecialchars($nombre) ?></td>
            <td><?= htmlspecialchars($e['dpi'] ?? '') ?> / <?= htmlspecialchars($e['nit'] ?? '') ?></td>
            <td><?= htmlspecialchars($e['puesto'] ?? '-') ?></td>
            <td><?= htmlspecialchars($dep) ?></td>
            <td><?= htmlspecialchars($jor) ?></td>
            <td><?= htmlspecialchars($fing) ?></td>
            <td class="text-end">Q<?= number_format((float)($e['salario_base'] ?? 0), 2) ?></td>
            <td><span class="badge <?= $estadoClass ?>"><?= htmlspecialchars($estadoTxt) ?></span></td>
            <td class="text-center">
              <div class="btn-group" role="group" aria-label="Acciones">
                <a href="index.php?route=empleados&action=edit&id=<?= (int)$e['id'] ?>"
                   class="btn btn-sm btn-outline-primary" title="Editar">
                  <i class="bi bi-pencil-square"></i>
                </a>
                <a href="index.php?route=empleados&action=show&id=<?= (int)$e['id'] ?>"
                   class="btn btn-sm btn-outline-primary" title="Ver">
                  <i class="bi bi-eye"></i>
                </a>
                <button type="button"
                        class="btn btn-sm btn-outline-danger js-delete"
                        data-href="index.php?route=empleados&action=delete&id=<?= (int)$e['id'] ?>"
                        title="Eliminar">
                  <i class="bi bi-trash-fill"></i>
                </button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if (!empty($pagination) && isset($pagination['page'],$pagination['last_page'])): ?>
    <?php
      // Construcción básica de paginación (espera ?route, ?action, ?q y filtros)
      $page = max(1, (int)$pagination['page']);
      $last = max(1, (int)$pagination['last_page']);
      $params = $_GET; unset($params['page']);
      $base = 'index.php?'.http_build_query($params);
      $mk = fn($p)=> $base.'&page='.$p;
    ?>
    <div class="card-footer">
      <nav aria-label="Paginación empleados">
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

<!-- Modal de confirmación eliminar -->
<div class="modal fade" id="modalDelete" tabindex="-1" aria-hidden="true" aria-labelledby="modalDeleteLabel">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-2xl">
      <div class="modal-header">
        <h5 class="modal-title" id="modalDeleteLabel">Eliminar empleado</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        ¿Seguro que deseas eliminar este empleado? Esta acción no se puede deshacer.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancelar</button>
        <a href="#" class="btn btn-danger" id="btnDeleteConfirm">
          <i class="bi bi-trash-fill"></i> Eliminar
        </a>
      </div>
    </div>
  </div>
</div>

<script>
  // Conectar botones .js-delete al modal de confirmación
  document.querySelectorAll('.js-delete').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const href = btn.getAttribute('data-href');
      const a = document.getElementById('btnDeleteConfirm');
      a.setAttribute('href', href);
      const modal = new bootstrap.Modal(document.getElementById('modalDelete'));
      modal.show();
    });
  });
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
