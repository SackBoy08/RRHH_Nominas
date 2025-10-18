<?php
// view/liquidaciones/result.php
$title = "Resultado de Liquidación";
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
function safe_date($d, $fmt='d/m/Y'){ if(empty($d)) return '-'; $ts=strtotime($d); return $ts?date($fmt,$ts):'-'; }
function q($n){ return number_format((float)($n ?? 0), 2); }
function iniciales($n){ $parts=preg_split('/\s+/', trim($n)); $i=''; foreach($parts as $p){ if($p!==''){ $i.=mb_strtoupper(mb_substr($p,0,1)); } if(mb_strlen($i)>=2) break; } return $i?:'EM'; }
function antiguedad($ingreso,$corte){ $a=strtotime($ingreso); $b=strtotime($corte); if(!$a||!$b||$b<$a) return '—'; $y=(int)date('Y',$b)-(int)date('Y',$a); $m=(int)date('n',$b)-(int)date('n',$a); $d=(int)date('j',$b)-(int)date('j',$a); if($d<0){$m-=1;$d+=30;} if($m<0){$y-=1;$m+=12;} return "{$y}a {$m}m {$d}d"; }

// Datos base
$empId        = emp_id($emp);
$nombre       = nombre_completo($emp);
$ini          = iniciales($nombre);
$fechaIngreso = $emp['fecha_ingreso'] ?? null;
$salarioBase  = (float)($emp['salario_base'] ?? 0);

// Parámetros contexto
$tipoLiquidacion = $_GET['tipo']  ?? ($tipo  ?? '—');
$fechaCorte      = $_GET['fecha'] ?? ($fecha ?? date('Y-m-d'));
$fechaIngresoFmt = safe_date($fechaIngreso);
$fechaCorteFmt   = safe_date($fechaCorte);
$antiguedadTxt   = antiguedad($fechaIngreso, $fechaCorte);

// Normaliza $liq
$L = array_merge([
  'dias_laborados'=>0,'valor_dias_laborados'=>0,
  'monto_aguinaldo'=>0,'monto_bono14'=>0,
  'dias_vac_acumulados'=>0,'valor_vac_acumuladas'=>0,
  'descuentos_totales'=>0,'total_liquidacion'=>0,
], $liq ?? []);

$totalBruto = (float)$L['valor_dias_laborados'] + (float)$L['monto_aguinaldo'] + (float)$L['monto_bono14'] + (float)$L['valor_vac_acumuladas'];
?>
<style>
  /* Hero con gradiente y “glass” */
  .gradient-hero{
    background: linear-gradient(90deg, var(--brand-lilac) 0%, var(--brand-turquoise) 100%);
    border: 0; border-radius: 1.25rem;
  }
  .glass{
    backdrop-filter: blur(8px);
    background: rgba(255,255,255,.55);
    border-radius: 1rem;
  }
  .avatar{
    width:64px; height:64px; display:grid; place-items:center; font-weight:800;
    border-radius: 50%; background: rgba(255,255,255,.85); color:#0f172a;
    box-shadow: 0 8px 20px rgba(2,6,23,.12);
  }
  .kpi-card{
    border:0; border-radius:1rem; box-shadow: 0 10px 30px rgba(2,6,23,.08);
    transition: transform .15s ease, box-shadow .15s ease;
  }
  .kpi-card:hover{ transform: translateY(-2px); box-shadow: 0 16px 40px rgba(2,6,23,.10); }
  .kpi-badge{ font-weight:700; }
  .sticky-actions{
    position: sticky; top: .75rem; z-index: 5;
  }
  @media print{
    nav.navbar, .app-sidebar, .sticky-actions, .btn, a[href]:after { display:none !important; }
    body{ background:#fff; }
    .gradient-hero, .card{ box-shadow:none !important; }
  }
</style>

<!-- HERO -->
<div class="gradient-hero p-3 p-md-4 mb-4 shadow-soft">
  <div class="d-flex flex-wrap align-items-center gap-3">
    <div class="avatar fs-4"><?= htmlspecialchars($ini) ?></div>
    <div class="flex-grow-1">
      <h2 class="mb-1"><?= htmlspecialchars($nombre) ?></h2>
      <div class="d-flex flex-wrap gap-2">
        <span class="badge kpi-badge text-bg-light">ID: <?= htmlspecialchars($empId) ?></span>
        <span class="badge kpi-badge text-bg-light">Ingreso: <?= htmlspecialchars($fechaIngresoFmt) ?></span>
        <span class="badge kpi-badge text-bg-light">Corte: <?= htmlspecialchars($fechaCorteFmt) ?></span>
        <span class="badge kpi-badge text-bg-light">Antigüedad: <?= htmlspecialchars($antiguedadTxt) ?></span>
        <span class="badge kpi-badge text-bg-light">Salario base: Q<?= q($salarioBase) ?></span>
        <span class="badge kpi-badge text-bg-primary">Tipo: <?= htmlspecialchars($tipoLiquidacion) ?></span>
      </div>
    </div>
    <div class="sticky-actions d-flex flex-wrap gap-2 ms-auto">
      <a href="<?= $BASE ?>/index.php?route=liquidaciones&action=index" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-arrow-left"></i> Volver
      </a>
      <a href="<?= $BASE ?>/index.php?route=liquidaciones&action=export&id=<?= urlencode($empId) ?>" class="btn btn-outline-success btn-sm">
        <i class="bi bi-download"></i> Exportar CSV
      </a>
      <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
        <i class="bi bi-printer"></i> Imprimir
      </button>
      <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCopy">
        <i class="bi bi-link-45deg"></i> Copiar enlace
      </button>
    </div>
  </div>
</div>

<?php if (empty($liq)): ?>
  <div class="alert alert-warning rounded-2xl">
    No se recibieron datos de liquidación. Verifica el proceso y vuelve a calcular.
  </div>
<?php endif; ?>

<!-- KPIs -->
<div class="row g-3 mb-3">
  <div class="col-12 col-md-6 col-lg-3">
    <div class="card kpi-card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted-2">Días laborados</div>
            <div class="fs-4 fw-bold"><?= (int)$L['dias_laborados'] ?></div>
          </div>
          <i class="bi bi-calendar-check fs-4"></i>
        </div>
        <div class="mt-2 small text-muted-2">Valor: Q<?= q($L['valor_dias_laborados']) ?></div>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-6 col-lg-3">
    <div class="card kpi-card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted-2">Aguinaldo</div>
            <div class="fs-4 fw-bold">Q<?= q($L['monto_aguinaldo']) ?></div>
          </div>
          <i class="bi bi-gift fs-4"></i>
        </div>
        <div class="mt-2 small text-muted-2">Proporcional al corte</div>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-6 col-lg-3">
    <div class="card kpi-card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted-2">Bono 14</div>
            <div class="fs-4 fw-bold">Q<?= q($L['monto_bono14']) ?></div>
          </div>
          <i class="bi bi-stars fs-4"></i>
        </div>
        <div class="mt-2 small text-muted-2">Proporcional al corte</div>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-6 col-lg-3">
    <div class="card kpi-card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted-2">Vacaciones</div>
            <div class="fs-4 fw-bold"><?= (float)$L['dias_vac_acumulados'] ?> días</div>
          </div>
          <i class="bi bi-umbrella fs-4"></i>
        </div>
        <div class="mt-2 small text-muted-2">Valor: Q<?= q($L['valor_vac_acumuladas']) ?></div>
      </div>
    </div>
  </div>
</div>

<!-- Timeline ingreso → corte -->
<div class="glass p-3 mb-3">
  <div class="d-flex align-items-center gap-3">
    <div class="text-center" style="min-width:120px">
      <div class="fw-bold"><?= htmlspecialchars($fechaIngresoFmt) ?></div>
      <div class="small text-muted-2">Ingreso</div>
    </div>
    <div class="flex-grow-1 position-relative" style="height:6px; background: rgba(0,0,0,.08); border-radius:999px;">
      <div style="position:absolute; inset:0; background:linear-gradient(90deg,var(--brand-lilac),var(--brand-turquoise)); border-radius:999px;"></div>
    </div>
    <div class="text-center" style="min-width:120px">
      <div class="fw-bold"><?= htmlspecialchars($fechaCorteFmt) ?></div>
      <div class="small text-muted-2">Corte</div>
    </div>
    <span class="badge text-bg-light ms-2">Antigüedad: <?= htmlspecialchars($antiguedadTxt) ?></span>
  </div>
</div>

<!-- Desglose -->
<div class="card shadow-soft">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Resumen económico</span>
    <span class="badge text-bg-light">Cifras en Quetzales (Q)</span>
  </div>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-12 col-lg-6">
        <ul class="list-group rounded-2xl">
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span><i class="bi bi-cash-coin me-2"></i> Total bruto</span>
            <strong>Q<?= q($totalBruto) ?></strong>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span><i class="bi bi-dash-circle me-2"></i> Descuentos</span>
            <strong>Q<?= q($L['descuentos_totales']) ?></strong>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span><i class="bi bi-check2-circle me-2"></i> Total neto</span>
            <strong class="fs-5">Q<?= q($L['total_liquidacion']) ?></strong>
          </li>
        </ul>
      </div>

      <div class="col-12 col-lg-6">
        <?php if (!empty($L['desglose_descuentos']) && is_array($L['desglose_descuentos'])): ?>
          <div class="card h-100">
            <div class="card-header">Descuentos detallados</div>
            <div class="card-body">
              <ul class="list-group">
                <?php foreach ($L['desglose_descuentos'] as $item): ?>
                  <li class="list-group-item d-flex justify-content-between">
                    <span><?= htmlspecialchars($item['concepto'] ?? '-') ?></span>
                    <span>Q<?= q($item['monto'] ?? 0) ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        <?php else: ?>
          <div class="glass p-3 h-100 d-flex align-items-center justify-content-center text-muted-2">
            No hay desglose de descuentos disponible.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
  // Copiar enlace
  document.getElementById('btnCopy')?.addEventListener('click', async ()=>{
    try{
      await navigator.clipboard.writeText(location.href);
      const btn = document.getElementById('btnCopy');
      const original = btn.innerHTML;
      btn.innerHTML = '<i class="bi bi-check2"></i> Copiado';
      setTimeout(()=>btn.innerHTML = original, 1500);
    }catch(e){}
  });
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
