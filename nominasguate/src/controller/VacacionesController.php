<?php
namespace Controller;

use Model\VacacionesModel;
use Model\EmpleadoModel;

class VacacionesController {
    private VacacionesModel $model;
    private EmpleadoModel $empModel;

    public function __construct() {
        $this->model    = new VacacionesModel();
        $this->empModel = new EmpleadoModel();
    }

    /**
     * Muestra la lista de todos los empleados con sus días de vacaciones.
     */
    public function index(): void {
        $vacaciones = $this->model->getAll();
        require __DIR__ . '/../view/vacaciones/index.php';
    }

    /**
     * Asignar Vacaciones (tipo = 'acumulado').
     * Si es POST, inserta el registro de tipo 'acumulado'.
     */
    public function assign(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->create([
                'empleado_id'   => $_POST['empleado_id'],
                'fecha_registro'=> $_POST['fecha_inicio'],   // Mapeo: fecha_inicio → fecha_registro
                'tipo'          => 'acumulado',
                'cantidad_dias' => $_POST['dias_asignados']
            ]);
            header('Location: index.php?route=vacaciones');
            exit;
        }

        // GET: Mostrar formulario de asignación
        $empleados = $this->empModel->getAll();
        require __DIR__ . '/../view/vacaciones/form.php';
    }

    /**
     * Quitar Vacaciones (tipo = 'tomado').
     * Si es POST, inserta el registro de tipo 'tomado'.
     */
    public function remove(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Insertar con tipo 'tomado'
            $this->model->create([
                'empleado_id'   => $_POST['empleado_id'],
                'fecha_registro'=> $_POST['fecha_inicio'],  // Mapa igual: fecha_inicio → fecha_registro
                'tipo'          => 'tomado',
                'cantidad_dias' => $_POST['dias_tomados']
            ]);
            header('Location: index.php?route=vacaciones');
            exit;
        }

        // GET: Mostrar formulario de “Quitar Vacaciones” para un empleado específico
        $empleadoId = (int)($_GET['empleado_id'] ?? 0);
        $empleado   = $this->empModel->getById($empleadoId);
        // Si el empleado no existe o id = 0, redirigir de vuelta
        if (!$empleado) {
            header('Location: index.php?route=vacaciones');
            exit;
        }
        require __DIR__ . '/../view/vacaciones/remove.php';
    }
}
