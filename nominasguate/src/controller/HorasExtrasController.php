<?php
namespace Controller;

use Model\EmpleadoModel;
use Model\PeriodoModel;
use Model\TipoHorasExtrasModel;
use Model\HorasExtrasModel;

class HorasExtrasController {
    private EmpleadoModel $empleadoModel;
    private PeriodoModel $periodoModel;
    private TipoHorasExtrasModel $tipoHEModel;
    private HorasExtrasModel $horasExtrasModel;

    public function __construct() {
        $this->empleadoModel    = new EmpleadoModel();
        $this->periodoModel     = new PeriodoModel();
        $this->tipoHEModel      = new TipoHorasExtrasModel();
        $this->horasExtrasModel = new HorasExtrasModel();
    }

    /**
     * GET /index.php?route=horas_extras
     * Lista empleados activos con buscador.
     */
    public function index(): void {
        $q        = trim($_GET['q'] ?? '');
        $todos    = $this->empleadoModel->getAll();
        $activos  = array_filter($todos, fn($e) => $e['id_estado'] === 1);
        if ($q !== '') {
            $activos = array_filter($activos, fn($e) =>
                stripos($e['nombre'], $q) !== false ||
                stripos($e['apellido'], $q) !== false ||
                (string)$e['id'] === $q
            );
        }
        $empleados = $activos;
        require __DIR__ . '/../view/horas_extras/index.php';
    }

    /**
     * GET /index.php?route=horas_extras&action=create&id=123
     * Muestra el formulario para el empleado seleccionado.
     */
    public function create(): void {
        $empleadoId = (int)($_GET['id'] ?? 0);
        if ($empleadoId <= 0) {
            header('Location: index.php?route=horas_extras');
            exit;
        }
        $empleado  = $this->empleadoModel->getById($empleadoId);
        $periodos  = $this->periodoModel->getAll();
        $tiposHE   = $this->tipoHEModel->getAll();
        $success   = isset($_GET['success']);
        require __DIR__ . '/../view/horas_extras/create.php';
    }

    /**
     * POST /index.php?route=horas_extras&action=store
     * Procesa el alta de horas extras.
     */
    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->horasExtrasModel->create([
                'empleado_id'       => (int)$_POST['empleado_id'],
                'periodo_id'        => (int)$_POST['id_periodo'],
                'id_tipohoraextra'  => (int)$_POST['id_tipohoraextra'],
                'cantidad'          => (float)$_POST['cantidad'],
            ]);
            $eid = (int)$_POST['empleado_id'];
            header("Location: index.php?route=horas_extras&action=create&id={$eid}&success=1");
            exit;
        }
    }
}
