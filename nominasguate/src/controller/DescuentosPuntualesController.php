<?php
namespace Controller;

use Model\EmpleadoModel;
use Model\DescuentosPuntualesModel;

class DescuentosPuntualesController {
    private EmpleadoModel $empleadoModel;
    private DescuentosPuntualesModel $descuentoModel;

    public function __construct() {
        $this->empleadoModel   = new EmpleadoModel();
        $this->descuentoModel  = new DescuentosPuntualesModel();
    }

    /** Listado de empleados activos con buscador */
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
        require __DIR__ . '/../view/descuentos_puntuales/index.php';
    }

    /** Formulario para un empleado determinado */
    public function create(): void {
        $empleadoId = (int)($_GET['id'] ?? 0);
        if ($empleadoId <= 0) {
            header('Location: index.php?route=descuentos_puntuales');
            exit;
        }

        $empleado = $this->empleadoModel->getById($empleadoId);
        $success  = isset($_GET['success']);
        require __DIR__ . '/../view/descuentos_puntuales/create.php';
    }

    /** Procesar el POST y guardar el descuento */
    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->descuentoModel->create([
                'empleado_id'    => (int)$_POST['empleado_id'],
                'fecha'          => $_POST['fecha'],
                'tipo_descuento' => $_POST['tipo_descuento'],
                'cantidad'       => (float)$_POST['cantidad'],
                'motivo'         => $_POST['motivo'],
            ]);
            $eid = (int)$_POST['empleado_id'];
            header("Location: index.php?route=descuentos_puntuales&action=create&id={$eid}&success=1");
            exit;
        }
    }
}
