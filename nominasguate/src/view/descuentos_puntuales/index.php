<?php
$title = "Descuentos Puntuales";
require __DIR__ . '/../layout/header.php';

$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$q = htmlspecialchars($_GET['q'] ?? '');
$depSel = $_GET['departamento_id'] ?? '';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h2 class="mb-0">Descuentos puntuales</h2>
    <small class="text-muted-2">Registra horas o días descontados por empleado</small>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body">
    <form class="row g-2" action="<?= $BASE ?>/index.php" method="get" role="search" aria-label="Buscar empleado">
      <input type="hidden" name="route"  value="descuentos_puntuales">
      <input type="hidden" name="action" value="index">

      <div class="col-12 col-md-6 col-lg-8">
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

      <div class="col-6 col-md-3 col-lg-1 d-grid align-self-end">
        <button class="btn btn-primary" type="submit">
          <i class="bi bi-search"></i> Buscar
        </button>
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
          <th class="text-center" style="width:180px">Acción</th>
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
          <?php
            $nombre = htmlspecialchars(($e['nombre'] ?? '').' '.($e['apellido'] ?? ''));
          ?>
          <tr>
            <td><?= (int)$e['id'] ?></td>
            <td><?= $nombre ?></td>
            <td><?= htmlspecialchars($e['dpi'] ?? '') ?></td>
            <td><?= htmlspecialchars($e['puesto'] ?? '-') ?></td>
            <td><?= htmlspecialchars($e['departamento'] ?? '-') ?></td>
            <td class="text-center">
              <div class="btn-group" role="group" aria-label="Acciones">
                <!-- Opción 1: Abrir modal y crear descuento aquí mismo -->
                <button type="button"
                        class="btn btn-sm btn-outline-primary js-open-descuento"
                        title="Registrar descuento"
                        data-id="<?= (int)$e['id'] ?>"
                        data-nombre="<?= $nombre ?>"
                        data-dpi="<?= htmlspecialchars($e['dpi'] ?? '') ?>"
                        data-puesto="<?= htmlspecialchars($e['puesto'] ?? '-') ?>"
                        data-depto="<?= htmlspecialchars($e['departamento'] ?? '-') ?>">
                  <i class="bi bi-scissors"></i> Descontar
                </button>

                <!-- Opción 2 (fallback): ir a tu formulario existente -->
                <a href="<?= $BASE ?>/index.php?route=descuentos_puntuales&action=create&id=<?= (int)$e['id'] ?>"
                   class="btn btn-sm btn-outline-secondary"
                   title="Usar formulario completo">
                  <i class="bi bi-box-arrow-up-right"></i>
                </a>
              </div>
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
      <nav aria-label="Paginación descuentos">
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

<!-- MODAL: Crear descuento puntual -->
<div class="modal fade" id="modalDescuento" tabindex="-1" aria-hidden="true" aria-labelledby="modalDescuentoLabel">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-2xl">
      <form action="<?= $BASE ?>/index.php?route=descuentos_puntuales&action=store" method="post" class="needs-validation" novalidate>
        <div class="modal-header">
          <h5 class="modal-title" id="modalDescuentoLabel">Registrar descuento</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2 small text-muted-2" id="infoEmpleado">
            <!-- Se llena por JS con nombre, DPI, puesto, depto -->
          </div>

          <input type="hidden" name="empleado_id" id="empleado_id">

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Fecha*</label>
              <input type="date" name="fecha" class="form-control" required
                     max="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
              <div class="invalid-feedback">Selecciona una fecha válida.</div>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">Tipo de descuento*</label>
              <select name="tipo_descuento" class="form-select" required>
                <option value="">-- Seleccione --</option>
                <option value="hora">Por hora</option>
                <option value="día">Por día</option>
              </select>
              <div class="invalid-feedback">Selecciona el tipo.</div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Cantidad*</label>
              <input type="number" name="cantidad" class="form-control" required step="0.25" min="0.25" placeholder="Ej. 1.00">
              <div class="form-text text-muted-2">Horas o días según el tipo.</div>
              <div class="invalid-feedback">Ingresa una cantidad válida.</div>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">Monto (opcional)</label>
              <input type="number" name="monto" class="form-control" step="0.01" min="0" placeholder="Q">
              <div class="form-text text-muted-2">Si lo dejas vacío, se calcula por salario base.</div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Motivo*</label>
            <input type="text" name="motivo" class="form-control" required placeholder="Ej. Llegada tarde, permiso sin goce...">
            <div class="invalid-feedback">Describe el motivo.</div>
          </div>

          <?php if (!empty($csrf ?? null)): ?>
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-scissors"></i> Guardar descuento
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  // Bootstrap validation en el modal
  (() => {
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
      form.addEventListener('submit', ev => {
        if (!form.checkValidity()) { ev.preventDefault(); ev.stopPropagation(); }
        form.classList.add('was-validated');
      }, false);
    });
  })();

  // Abrir modal con datos del empleado
  document.querySelectorAll('.js-open-descuento').forEach(btn => {
    btn.addEventListener('click', () => {
      const id     = btn.getAttribute('data-id');
      const nombre = btn.getAttribute('data-nombre') || '';
      const dpi    = btn.getAttribute('data-dpi') || '';
      const puesto = btn.getAttribute('data-puesto') || '-';
      const depto  = btn.getAttribute('data-depto') || '-';

      document.getElementById('empleado_id').value = id;
      const info = document.getElementById('infoEmpleado');
      info.innerHTML = `<strong>${nombre}</strong> · DPI ${dpi} · ${puesto} · ${depto}`;

      const modal = new bootstrap.Modal(document.getElementById('modalDescuento'));
      modal.show();
    });
  });
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
