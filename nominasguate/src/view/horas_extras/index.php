<?php
$title = "Horas Extras";
require __DIR__ . '/../layout/header.php';
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$q = htmlspecialchars($_GET['q'] ?? '');
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h2 class="mb-0">Horas extra</h2>
    <small class="text-muted-2">Registra horas y tipos por empleado</small>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body">
    <form class="row g-2" action="<?= $BASE ?>/index.php" method="get" role="search" aria-label="Buscar empleado">
      <input type="hidden" name="route"  value="horas_extras">
      <input type="hidden" name="action" value="index">

      <div class="col-12 col-md-9">
        <label class="form-label">Buscar</label>
        <input class="form-control" type="search" name="q" placeholder="Nombre, DPI o NIT…" value="<?= $q ?>">
      </div>
      <div class="col-12 col-md-3 d-grid align-self-end">
        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Buscar</button>
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
          <th>Nombre</th>
          <th>DPI</th>
          <th>Puesto</th>
          <th>Departamento</th>
          <th class="text-center" style="width:160px">Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($empleados)): ?>
          <tr>
            <td colspan="6" class="text-center py-5">
              <div class="text-muted-2 mb-2">No hay empleados activos que coincidan con tu búsqueda.</div>
            </td>
          </tr>
        <?php else: foreach ($empleados as $e): ?>
          <tr>
            <td><?= (int)$e['id'] ?></td>
            <td><?= htmlspecialchars(($e['nombre'] ?? '').' '.($e['apellido'] ?? '')) ?></td>
            <td><?= htmlspecialchars($e['dpi'] ?? '') ?></td>
            <td><?= htmlspecialchars($e['puesto'] ?? '-') ?></td>
            <td><?= htmlspecialchars($e['departamento'] ?? '-') ?></td>
            <td class="text-center">
              <a href="<?= $BASE ?>/index.php?route=horas_extras&action=create&id=<?= (int)$e['id'] ?>"
                 class="btn btn-sm btn-primary">
                <i class="bi bi-clock-history"></i> Agregar HE
              </a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
