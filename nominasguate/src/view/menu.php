<?php
$title = "Menú Principal";
require __DIR__ . '/layout/header.php';

/**
 * Stats opcionales (si los pasas desde el controller).
 * Si no existen, se ocultan automáticamente.
 *
 * $stats = [
 *   'empleados' => 42,
 *   'nominas_pendientes' => 3,
 *   'horas_extras_pendientes' => 5,
 *   'vacaciones_solicitudes' => 2,
 *   'descuentos_pendientes' => 1,
 *   'liquidaciones_abiertas' => 0,
 * ];
 */
$stats = $stats ?? [];
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); // definido en header también
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h1 class="h3 mb-1">Menú Principal</h1>
    <p class="text-muted-2 mb-0">Elige un módulo para continuar</p>
  </div>

  <!-- Acciones rápidas (solo desktop) -->
  <div class="d-none d-md-flex gap-2">
    <a href="<?= $BASE ?>/index.php?route=nomina&action=create" class="btn btn-primary btn-sm">
      <i class="bi bi-lightning-charge"></i> Generar nómina
    </a>
    <a href="<?= $BASE ?>/index.php?route=empleados&action=create" class="btn btn-outline-primary btn-sm">
      <i class="bi bi-person-plus"></i> Nuevo empleado
    </a>
  </div>
</div>

<!-- Búsqueda rápida -->
<div class="card mb-3">
  <div class="card-body">
    <form class="row g-2" method="get" action="<?= $BASE ?>/index.php" role="search" aria-label="Búsqueda rápida">
      <input type="hidden" name="route" value="empleados">
      <div class="col-md-8 col-lg-10">
        <label class="form-label">Buscar empleado</label>
        <input type="search" name="q" class="form-control" placeholder="Nombre, DPI o NIT…"
          aria-label="Buscar empleado por nombre, DPI o NIT">
      </div>
      <div class="col-md-4 col-lg-2 d-grid align-self-end">
        <button class="btn btn-primary"><i class="bi bi-search"></i> Buscar</button>
      </div>
    </form>
  </div>
</div>

<!-- Grid de módulos -->
<div class="row g-3">

  <!-- Empleados -->
  <div class="col-12 col-md-6 col-lg-4">
    <a href="<?= $BASE ?>/index.php?route=empleados" class="text-decoration-none">
      <div class="card shadow-soft h-100">
        <div class="card-body d-flex align-items-start gap-3">
          <div class="rounded-2xl p-3" style="background:linear-gradient(90deg,var(--brand-lilac),var(--brand-turquoise));">
            <i class="bi bi-people-fill fs-4" aria-hidden="true"></i>
          </div>
          <div class="flex-grow-1">
            <h2 class="h5 mb-1 text-dark">Empleados</h2>
            <p class="mb-2 text-muted-2">Altas, bajas, actualización de datos, jornadas y salarios base.</p>
            <?php if (isset($stats['empleados'])): ?>
              <span class="badge text-bg-light">Total: <?= (int)$stats['empleados'] ?></span>
            <?php endif; ?>
          </div>
          <i class="bi bi-chevron-right text-muted" aria-hidden="true"></i>
        </div>
      </div>
    </a>
  </div>

  <!-- Nómina -->
  <div class="col-12 col-md-6 col-lg-4">
    <a href="<?= $BASE ?>/index.php?route=nomina" class="text-decoration-none">
      <div class="card shadow-soft h-100">
        <div class="card-body d-flex align-items-start gap-3">
          <div class="rounded-2xl p-3" style="background:linear-gradient(90deg,var(--brand-turquoise),var(--brand-lilac));">
            <i class="bi bi-currency-dollar fs-4" aria-hidden="true"></i>
          </div>
          <div class="flex-grow-1">
            <h2 class="h5 mb-1 text-dark">Nómina</h2>
            <p class="mb-2 text-muted-2">Generación por período, IGSS/ISR, horas extra y netos a devengar.</p>
            <?php if (isset($stats['nominas_pendientes']) && $stats['nominas_pendientes'] > 0): ?>
              <span class="badge text-bg-light">Pendientes: <?= (int)$stats['nominas_pendientes'] ?></span>
            <?php endif; ?>
          </div>
          <i class="bi bi-chevron-right text-muted" aria-hidden="true"></i>
        </div>
      </div>
    </a>
  </div>

  <!-- Horas Extras -->
  <div class="col-12 col-md-6 col-lg-4">
    <a href="<?= $BASE ?>/index.php?route=horas_extras" class="text-decoration-none">
      <div class="card shadow-soft h-100">
        <div class="card-body d-flex align-items-start gap-3">
          <div class="rounded-2xl p-3" style="background:linear-gradient(90deg,var(--brand-lilac-600),var(--brand-turquoise-600));">
            <i class="bi bi-clock-fill fs-4" aria-hidden="true"></i>
          </div>
          <div class="flex-grow-1">
            <h2 class="h5 mb-1 text-dark">Horas extra</h2>
            <p class="mb-2 text-muted-2">Registro por tipo y cálculo automático con multiplicadores.</p>
            <?php if (isset($stats['horas_extras_pendientes']) && $stats['horas_extras_pendientes'] > 0): ?>
              <span class="badge text-bg-light">Por aprobar: <?= (int)$stats['horas_extras_pendientes'] ?></span>
            <?php endif; ?>
          </div>
          <i class="bi bi-chevron-right text-muted" aria-hidden="true"></i>
        </div>
      </div>
    </a>
  </div>

  <!-- Descuentos Puntuales -->
  <div class="col-12 col-md-6 col-lg-4">
    <a href="<?= $BASE ?>/index.php?route=descuentos_puntuales" class="text-decoration-none">
      <div class="card shadow-soft h-100">
        <div class="card-body d-flex align-items-start gap-3">
          <div class="rounded-2xl p-3" style="background:linear-gradient(90deg,var(--brand-turquoise-600),var(--brand-lilac));">
            <i class="bi bi-dash-circle fs-4" aria-hidden="true"></i>
          </div>
          <div class="flex-grow-1">
            <h2 class="h5 mb-1 text-dark">Descuentos puntuales</h2>
            <p class="mb-2 text-muted-2">Horas/días descontados y aplicación por período.</p>
            <?php if (isset($stats['descuentos_pendientes']) && $stats['descuentos_pendientes'] > 0): ?>
              <span class="badge text-bg-light">Pendientes: <?= (int)$stats['descuentos_pendientes'] ?></span>
            <?php endif; ?>
          </div>
          <i class="bi bi-chevron-right text-muted" aria-hidden="true"></i>
        </div>
      </div>
    </a>
  </div>

  <!-- Vacaciones -->
  <div class="col-12 col-md-6 col-lg-4">
    <a href="<?= $BASE ?>/index.php?route=vacaciones" class="text-decoration-none">
      <div class="card shadow-soft h-100">
        <div class="card-body d-flex align-items-start gap-3">
          <div class="rounded-2xl p-3" style="background:linear-gradient(90deg,var(--brand-lilac),var(--brand-turquoise-600));">
            <i class="bi bi-calendar-check-fill fs-4" aria-hidden="true"></i>
          </div>
          <div class="flex-grow-1">
            <h2 class="h5 mb-1 text-dark">Vacaciones</h2>
            <p class="mb-2 text-muted-2">Acumulados, tomados y disponibilidad por empleado.</p>
            <?php if (isset($stats['vacaciones_solicitudes']) && $stats['vacaciones_solicitudes'] > 0): ?>
              <span class="badge text-bg-light">Solicitudes: <?= (int)$stats['vacaciones_solicitudes'] ?></span>
            <?php endif; ?>
          </div>
          <i class="bi bi-chevron-right text-muted" aria-hidden="true"></i>
        </div>
      </div>
    </a>
  </div>

  <!-- Liquidaciones -->
  <div class="col-12 col-md-6 col-lg-4">
    <a href="<?= $BASE ?>/index.php?route=liquidaciones" class="text-decoration-none">
      <div class="card shadow-soft h-100">
        <div class="card-body d-flex align-items-start gap-3">
          <div class="rounded-2xl p-3" style="background:linear-gradient(90deg,var(--brand-turquoise),var(--brand-lilac-600));">
            <i class="bi bi-file-earmark-text-fill fs-4" aria-hidden="true"></i>
          </div>
          <div class="flex-grow-1">
            <h2 class="h5 mb-1 text-dark">Liquidaciones</h2>
            <p class="mb-2 text-muted-2">Cálculo de días, aguinaldo, bono 14 y vacaciones.</p>
            <?php if (isset($stats['liquidaciones_abiertas']) && $stats['liquidaciones_abiertas'] > 0): ?>
              <span class="badge text-bg-light">Abiertas: <?= (int)$stats['liquidaciones_abiertas'] ?></span>
            <?php endif; ?>
          </div>
          <i class="bi bi-chevron-right text-muted" aria-hidden="true"></i>
        </div>
      </div>
    </a>
  </div>

  <?php $BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); ?>
  <?php if (!empty($_SESSION['user']) && ($_SESSION['user']['rol'] ?? '') === 'admin'): ?>
    <div class="col-12 col-md-6 col-lg-4">
      <a href="<?= $BASE ?>/index.php?route=config&action=ajustes" class="text-decoration-none">
        <div class="card shadow-soft h-100">
          <div class="card-body d-flex align-items-start gap-3">
            <div class="rounded-2xl p-3" style="background:linear-gradient(90deg,var(--brand-turquoise-600),var(--brand-lilac));">
              <i class="bi bi-gear-fill fs-4" aria-hidden="true"></i>
            </div>
            <div class="flex-grow-1">
              <h2 class="h5 mb-1 text-dark">Configuración</h2>
              <p class="mb-2 text-muted-2">Usuarios, roles y ajustes de la aplicación.</p>
            </div>
            <i class="bi bi-chevron-right text-muted" aria-hidden="true"></i>
          </div>
        </div>
      </a>
    </div>
  <?php endif; ?>


</div>

<?php require __DIR__ . '/layout/footer.php'; ?>