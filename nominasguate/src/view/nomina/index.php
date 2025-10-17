<?php
$title = "Gestión de Nómina";
require __DIR__ . '/../layout/header.php';
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$q = htmlspecialchars($_GET['q'] ?? '');
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h2 class="mb-0">Nómina</h2>
    <small class="text-muted-2">Genera por período, crea en masa o consulta empleados</small>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= $BASE ?>/index.php?route=nomina&action=generate" class="btn btn-primary">
      <i class="bi bi-lightning-charge"></i> Generar por período
    </a>
    <a href="<?= $BASE ?>/index.php?route=nomina&action=create" class="btn btn-outline-primary">
      <i class="bi bi-file-earmark-plus"></i> Crear en masa
    </a>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body">
    <form class="row g-2" action="<?= $BASE ?>/index.php" method="get" role="search" aria-label="Buscar empleados">
      <input type="hidden" name="route" value="nomina">
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
    <span>Empleados activos</span>
    <?php if (!empty($empleados)): ?>
      <span class="badge text-bg-light">Total: <?= count($empleados) ?></span>
    <?php endif; ?>
  </div>

  <div class="table-responsive">
    <table class="table table-striped align-middle mb-0">
      <thead>
        <tr>
          <th style="width:72px">#</th>
          <th>Empleado</th>
          <th>Puesto</th>
          <th>Departamento</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($empleados)): ?>
          <tr><td colspan="4" class="text-center py-5 text-muted-2">No hay empleados activos.</td></tr>
        <?php else: foreach ($empleados as $e): ?>
          <tr>
            <td><?= (int)$e['id'] ?></td>
            <td><?= htmlspecialchars(($e['nombre'] ?? '').' '.($e['apellido'] ?? '')) ?></td>
            <td><?= htmlspecialchars($e['puesto'] ?? '-') ?></td>
            <td><?= htmlspecialchars($e['departamento'] ?? '-') ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
