<?php
namespace Controller;

use Model\EmpleadoModel;
use Model\VacacionesModel;
use Model\LiquidacionModel; 

class LiquidacionController
{
    private EmpleadoModel $empModel;
    private VacacionesModel $vacModel;
    private LiquidacionModel  $liqModel;

    public function __construct()
    {
        $this->empModel = new EmpleadoModel();
        $this->vacModel = new VacacionesModel();
        $this->liqModel = new LiquidacionModel();
    }

    /**
     * 1) Muestra la lista de empleados con enlace a liquidar
     */
    public function index(): void
    {
        $empleados = $this->empModel->getAll();
        require __DIR__ . '/../view/liquidaciones/index.php';
    }

    /**
     * 2) Carga el formulario para seleccionar tipo de liquidación
     */
    public function form(): void
    {
        $id  = (int) ($_GET['id'] ?? 0);
        $emp = $this->empModel->getById($id);

        if (!$emp) {
            header('Location: index.php?route=liquidaciones');
            exit;
        }

        require __DIR__ . '/../view/liquidaciones/form.php';
    }

    /**
     * 3) Procesa la liquidación y muestra el resultado
     */
    public function process(): void
    {
        $id    = (int) ($_GET['id']   ?? 0);
        $tipo  =     $_GET['tipo'] ?? '';
        $fecha =     date('Y-m-d');  

        $emp = $this->empModel->getById($id);
        $vac = $this->vacModel->getByEmpleadoId($id);

        if (!$emp) {
            header('Location: index.php?route=liquidaciones');
            exit;
        }

        $liq = $this->liqModel->sp_liquidar_empleado(
            $emp['id'],   // o ['id_empleado'] según tu modelo
            $tipo,
            $fecha
        );

        // Aquí van tus cálculos de liquidación, por ejemplo:
        // if ($tipo === 'Despido') { … } else { … }

        require __DIR__ . '/../view/liquidaciones/result.php';
    }

    /**
     * 4) (Opcional) Exporta la liquidación en CSV
     */
    public function export(): void
    {
        // Código para descargar/exportar CSV
    }
}
