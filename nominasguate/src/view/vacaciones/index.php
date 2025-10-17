<?php
$title = "Vacaciones";
require __DIR__ . '/../layout/header.php';
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$q = htmlspecialchars($_GET['q'] ?? '');

function fmtd($d){ if(empty($d)) return '—'; $ts=strtotime($d); return $ts?date('d/m/Y',$ts):'—'; }
function q2($n){ return number_format((float)$n,2); }
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h2 class="mb-0">Vacaciones asignadas</h2>
    <small class="text-muted-2">Asigna, registra tomas y consulta saldos por empleado</small>
  </div>
  <a href="<?= $BASE ?>/index.php?route=vacaciones&action=assign" class="btn btn-primary">
    <i class="bi bi-plus-lg me-1"></i> Asignar vacaciones
  </a>
</div>

<div class="card mb-3">
  <div class="card-body">
    <form class="row g-2" action="<?= $BASE ?>/index.php" method="get" role="search">
      <input type="hidden" name="route" value="vacaciones">
      <input type="hidden" name="action" value="index">
      <div class="col-12 col-md-9">
        <label class="form-label">Buscar</label>
        <input class="form-control" type="search" name="q" placeholder="Nombre, DPI o NIT…" value="<?= $q ?>">
      </div>
      <div class="col-12 col-md-3 d-grid align-self-end">
        <button class="btn btn-primary"><i class="bi bi-search"></i> Buscar</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Listado</span>
    <?php if (!empty($vacaciones)): ?>
      <span class="badge text-bg-light">Total: <?= count($vacaciones) ?></span>
    <?php endif; ?>
  </div>

  <div class="table-responsive">
    <table class="table table-striped table-hover align-middle mb-0">
      <thead class="table-dark">
        <tr>
          <th>Empleado</th>
          <th>Inicio</th>
          <th class="text-end">Asignados</th>
          <th class="text-end">Tomados</th>
          <th class="text-end">Restantes</th>
          <th style="min-width:220px">Progreso</th>
          <th class="text-center" style="width:180px">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($vacaciones)): ?>
          <tr><td colspan="7" class="text-center py-5 text-muted-2">No hay registros.</td></tr>
        <?php else: foreach ($vacaciones as $v): ?>
          <?php
            $id   = (int)($v['id'] ?? $v['id_empleado'] ?? 0);
            $nom  = trim(($v['nombre'] ?? '').' '.($v['apellido'] ?? ''));
            $fi   = fmtd($v['fecha_inicio'] ?? null);
            $asig = (float)($v['dias_asignados'] ?? 0);
            $tom  = (float)($v['dias_tomados']   ?? 0);
            $rest = (float)($v['dias_restantes'] ?? max($asig-$tom,0));
            $pct  = ($asig>0) ? max(0,min(100, round(($tom/$asig)*100))) : 0;
            $bar  = $pct>=90 ? 'bg-danger' : ($pct>=60 ? 'bg-warning' : 'bg-success');
          ?>
          <tr>
            <td><i class="bi bi-person-circle me-1 text-secondary"></i> <?= htmlspecialchars($nom ?: 'Empleado #'.$id) ?></td>
            <td><?= htmlspecialchars($fi) ?></td>
            <td class="text-end"><?= q2($asig) ?></td>
            <td class="text-end"><?= q2($tom) ?></td>
            <td class="text-end"><?= q2($rest) ?></td>
            <td>
              <div class="progress" role="progressbar" aria-label="Progreso de vacaciones" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar <?= $bar ?>" style="width: <?= $pct ?>%"><?= $pct ?>%</div>
              </div>
            </td>
            <td class="text-center">
              <div class="btn-group">
                <a href="<?= $BASE ?>/index.php?route=vacaciones&action=assign&empleado_id=<?= $id ?>" class="btn btn-sm btn-outline-primary" title="Asignar">
                  <i class="bi bi-plus-circle"></i>
                </a>
                <a href="<?= $BASE ?>/index.php?route=vacaciones&action=remove&empleado_id=<?= $id ?>" class="btn btn-sm btn-outline-danger" title="Registrar toma">
                  <i class="bi bi-dash-circle"></i>
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="card-footer bg-white text-end">
    <a href="<?= $BASE ?>/index.php?route=menu" class="text-decoration-none">
      <i class="bi bi-arrow-left me-1"></i> Volver al menú
    </a>
  </div>
</div>

<style>
  .progress{ height: 10px; }
</style>

<?php require __DIR__ . '/../layout/footer.php'; ?>
