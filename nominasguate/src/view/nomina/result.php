<?php
// src/view/nomina/result.php
$title = "Nómina";
require __DIR__ . '/../layout/header.php';
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

// Helpers
$q = fn($n) => number_format((float)($n ?? 0), 2);
$pid = (int)($periodo['id'] ?? ($periodo['id_periodo'] ?? 0));
$fi  = htmlspecialchars($periodo['fecha_inicio'] ?? '');
$ff  = htmlspecialchars($periodo['fecha_fin'] ?? '');

// Totales del período
$tot = [
  'bruto' => 0,
  'igss' => 0,
  'isr' => 0,
  'otros' => 0,
  'he_cant' => 0,
  'he_pago' => 0,
  'bono14' => 0,
  'neto' => 0,
  'devengar' => 0
];
if (!empty($nominas)) {
  foreach ($nominas as $n) {
    $tot['bruto']   += (float)($n['salario_bruto'] ?? 0);
    $tot['igss']    += (float)($n['descuento_igss'] ?? 0);
    $tot['isr']     += (float)($n['descuento_isr'] ?? 0);
    $tot['otros']   += (float)($n['otros_descuentos'] ?? 0);
    $tot['he_cant'] += (float)($n['horas_extras'] ?? 0);
    $tot['he_pago'] += (float)($n['pago_horas_extras'] ?? 0);
    $tot['bono14']  += (float)($n['bono14'] ?? 0);
    $tot['neto']    += (float)($n['salario_neto'] ?? 0);
    $tot['devengar'] += (float)($n['salario_a_devengar'] ?? 0);
  }
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Nómina: <?= $fi ?> &rarr; <?= $ff ?></h3>
  <div class="d-flex gap-2">
    <a href="<?= $BASE ?>/index.php?route=nomina&action=report&id_periodo=<?= urlencode($pid) ?>"
      class="btn btn-primary">
      <i class="bi bi-file-earmark-text-fill"></i> Generar reporte
    </a>
    <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
      <i class="bi bi-printer"></i> Imprimir
    </button>
    <a href="<?= $BASE ?>/index.php?route=nomina&action=create" class="btn btn-outline-primary">
      <i class="bi bi-arrow-left"></i> Volver
    </a>
  </div>
</div>

<!-- KPIs -->
<div class="row g-3 mb-3">
  <div class="col-12 col-md-6 col-lg-3">
    <div class="card kpi-card">
      <div class="card-body">
        <div class="text-muted-2">Empleados en nómina</div>
        <div class="fs-4 fw-bold"><?= !empty($nominas) ? count($nominas) : 0 ?></div>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-6 col-lg-3">
    <div class="card kpi-card">
      <div class="card-body">
        <div class="text-muted-2">Total bruto</div>
        <div class="fs-4 fw-bold">Q<?= $q($tot['bruto']) ?></div>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-6 col-lg-3">
    <div class="card kpi-card">
      <div class="card-body">
        <div class="text-muted-2">Descuentos (IGSS/ISR/otros)</div>
        <div class="fs-6 fw-bold">
          IGSS Q<?= $q($tot['igss']) ?> · ISR Q<?= $q($tot['isr']) ?> · Otros Q<?= $q($tot['otros']) ?>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-6 col-lg-3">
    <div class="card kpi-card">
      <div class="card-body">
        <div class="text-muted-2">Neto a pagar</div>
        <div class="fs-4 fw-bold">Q<?= $q($tot['devengar']) ?></div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">Detalle por empleado</div>
  <div class="table-responsive">
    <table class="table table-striped table-bordered align-middle mb-0">
      <thead class="table-dark">
        <tr>
          <th>#</th>
          <th>Empleado</th>
          <th>Bruto (Q)</th>
          <th>IGSS</th>
          <th>ISR</th>
          <th>Otros desc.</th>
          <th>Horas Ext.</th>
          <th>Pago H.E.</th>
          <th>Bono 14</th>
          <th>Neto (Q)</th>
          <th>Devengar (Q)</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($nominas)): ?>
          <tr>
            <td colspan="11" class="text-center py-5 text-muted-2">No hay registros para este período.</td>
          </tr>
          <?php else: foreach ($nominas as $n): ?>
            <tr>
              <td><?= (int)$n['id'] ?></td>
              <td><?= htmlspecialchars($n['nombre_completo'] ?? '-') ?></td>
              <td><?= $q($n['salario_bruto']) ?></td>
              <td><?= $q($n['descuento_igss']) ?></td>
              <td><?= $q($n['descuento_isr']) ?></td>
              <td><?= $q($n['otros_descuentos']) ?></td>
              <td><?= $q($n['horas_extras']) ?></td>
              <td><?= $q($n['pago_horas_extras']) ?></td>
              <td><?= $q($n['bono14']) ?></td>
              <td><?= $q($n['salario_neto']) ?></td>
              <td class="fw-bold"><?= $q($n['salario_a_devengar']) ?></td>
            </tr>
        <?php endforeach;
        endif; ?>
      </tbody>
      <?php if (!empty($nominas)): ?>
        <tfoot>
          <tr class="table-light">
            <th colspan="2" class="text-end">Totales</th>
            <th>Q<?= $q($tot['bruto']) ?></th>
            <th>Q<?= $q($tot['igss']) ?></th>
            <th>Q<?= $q($tot['isr']) ?></th>
            <th>Q<?= $q($tot['otros']) ?></th>
            <th><?= $q($tot['he_cant']) ?></th>
            <th>Q<?= $q($tot['he_pago']) ?></th>
            <th>Q<?= $q($tot['bono14']) ?></th>
            <th>Q<?= $q($tot['neto']) ?></th>
            <th class="fw-bold">Q<?= $q($tot['devengar']) ?></th>
          </tr>
        </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>

<style>
  .kpi-card {
    border: 0;
    border-radius: 1rem;
    box-shadow: 0 10px 30px rgba(2, 6, 23, .08);
  }

  @media print {

    nav.navbar,
    .app-sidebar,
    .btn,
    a[href]:after {
      display: none !important;
    }
  }
</style>

<?php require __DIR__ . '/../layout/footer.php'; ?>