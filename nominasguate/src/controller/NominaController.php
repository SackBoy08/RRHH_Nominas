<?php
namespace Controller;

use Model\PeriodoModel;
use Model\NominaModel;
use Model\EmpleadoModel;

class NominaController {
    private PeriodoModel $periodoModel;
    private NominaModel $nominaModel;
    private EmpleadoModel $empleadoModel;

    public function __construct() {
        $this->periodoModel  = new PeriodoModel();
        $this->nominaModel   = new NominaModel();
        $this->empleadoModel = new EmpleadoModel();
    }

    public function index(): void {
        $empleados = $this->empleadoModel->getAll();
        require __DIR__ . '/../view/nomina/index.php';
    }

    // Selección en masa
    public function create(): void {
        $periodos  = $this->periodoModel->getAll();
        $empleados = array_filter($this->empleadoModel->getAll(), fn($e) => (int)$e['id_estado'] === 1);
        require __DIR__ . '/../view/nomina/create.php';
    }

    // Inserta filas base en nominas para los empleados seleccionados
    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $periodoId = (int)($_POST['id_periodo'] ?? 0);
            $empIds    = $_POST['empleados'] ?? [];

            $this->nominaModel->createForEmployees($periodoId, $empIds);

            $ids = implode(',', array_map('intval', $empIds));
            header("Location: index.php?route=nomina&action=generate&id_periodo={$periodoId}&empleados={$ids}");
            exit;
        }
    }

    // Ejecuta SP y muestra resultado en pantalla
    public function generate(): void {
        $periodoId = (int)($_GET['id_periodo'] ?? 0);
        if ($periodoId <= 0) {
            header('Location: index.php?route=nomina&action=create'); exit;
        }

        // 1) Calcular
        $this->nominaModel->processAll($periodoId);

        // 2) Leer nóminas
        $nominas = $this->nominaModel->getByPeriodo($periodoId);

        // 3) Filtrar por empleados si viene ?empleados=1,3,5
        if (!empty($_GET['empleados'])) {
            $filterIds = array_map('intval', explode(',', $_GET['empleados']));
            $nominas   = array_filter($nominas, fn($n) => in_array((int)$n['empleado_id'], $filterIds, true));
        }

        // 4) Periodo
        $periodo = $this->findPeriodo($periodoId);
        if (!$periodo) { header('Location: index.php?route=nomina&action=create'); exit; }

        // 5) Render
        require __DIR__ . '/../view/nomina/result.php';
    }

    // === NUEVO: Reporte CSV ===
    public function report(): void {
        // Permite id_periodo o periodo (compat)
        $periodoId = (int)($_GET['id_periodo'] ?? ($_GET['periodo'] ?? 0));
        if ($periodoId <= 0) {
            header('Location: index.php?route=nomina&action=create'); exit;
        }

        $periodo = $this->findPeriodo($periodoId);
        if (!$periodo) {
            header('Location: index.php?route=nomina&action=create'); exit;
        }

        $rows = $this->nominaModel->getByPeriodo($periodoId);

        // Respeta filtro de empleados también aquí
        if (!empty($_GET['empleados'])) {
            $filterIds = array_map('intval', explode(',', $_GET['empleados']));
            $rows      = array_filter($rows, fn($n) => in_array((int)$n['empleado_id'], $filterIds, true));
        }

        // Headers CSV
        $filename = sprintf(
            'Nomina_%s_a_%s.csv',
            $periodo['fecha_inicio'] ?? 'inicio',
            $periodo['fecha_fin']    ?? 'fin'
        );
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // BOM para Excel (UTF-8)
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');

        // Encabezados
        fputcsv($out, [
            '#','Empleado','Bruto (Q)','IGSS','ISR','Otros desc.',
            'Horas Ext.','Pago H.E.','Bono 14','Neto (Q)','Devengar (Q)'
        ]);

        // Totales
        $tot = [
            'bruto'=>0,'igss'=>0,'isr'=>0,'otros'=>0,
            'he_cant'=>0,'he_pago'=>0,'bono14'=>0,'neto'=>0,'devengar'=>0
        ];

        // Filas
        foreach ($rows as $n) {
            $bruto   = (float)($n['salario_bruto'] ?? 0);
            $igss    = (float)($n['descuento_igss'] ?? 0);
            $isr     = (float)($n['descuento_isr'] ?? 0);
            $otros   = (float)($n['otros_descuentos'] ?? 0);
            $heCant  = (float)($n['horas_extras'] ?? 0);
            $hePago  = (float)($n['pago_horas_extras'] ?? 0);
            $bono14  = (float)($n['bono14'] ?? 0);
            $neto    = (float)($n['salario_neto'] ?? 0);
            $dev     = (float)($n['salario_a_devengar'] ?? 0);

            $tot['bruto']   += $bruto;
            $tot['igss']    += $igss;
            $tot['isr']     += $isr;
            $tot['otros']   += $otros;
            $tot['he_cant'] += $heCant;
            $tot['he_pago'] += $hePago;
            $tot['bono14']  += $bono14;
            $tot['neto']    += $neto;
            $tot['devengar']+= $dev;

            // Usa punto decimal y sin miles para no romper el CSV
            $fmt = fn($x) => number_format((float)$x, 2, '.', '');

            fputcsv($out, [
                $n['id'],
                $n['nombre_completo'],
                $fmt($bruto),
                $fmt($igss),
                $fmt($isr),
                $fmt($otros),
                $fmt($heCant),
                $fmt($hePago),
                $fmt($bono14),
                $fmt($neto),
                $fmt($dev),
            ]);
        }

        // Fila de totales (si hay filas)
        if (!empty($rows)) {
            $fmt = fn($x) => number_format((float)$x, 2, '.', '');
            fputcsv($out, []); // línea en blanco
            fputcsv($out, [
                'Totales','','',
                $fmt($tot['igss']), $fmt($tot['isr']), $fmt($tot['otros']),
                $fmt($tot['he_cant']), $fmt($tot['he_pago']), $fmt($tot['bono14']),
                $fmt($tot['neto']), $fmt($tot['devengar'])
            ]);
            // Si prefieres incluir total bruto, cambia el arreglo anterior por:
            // ['Totales','', $fmt($tot['bruto']), ...]
        }

        fclose($out);
        exit;
    }

    // Helper: obtener período por id con fallback a getAll()
    private function findPeriodo(int $periodoId): ?array {
        if (method_exists($this->periodoModel, 'findById')) {
            return $this->periodoModel->findById($periodoId);
        }
        $all = $this->periodoModel->getAll();
        foreach ($all as $p) {
            if ((int)$p['id'] === $periodoId || (int)($p['id_periodo'] ?? 0) === $periodoId) {
                return $p;
            }
        }
        return null;
    }
}
