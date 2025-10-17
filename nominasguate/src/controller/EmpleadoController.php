<?php
namespace Controller;

use Model\EmpleadoModel;
use Model\DepartamentoModel;
use Model\JornadaModel;

class EmpleadoController {
    private EmpleadoModel $model;
    private DepartamentoModel $deptModel;
    private JornadaModel $jornadaModel;
    // Si tienes una tabla de estados, crea un EstadoModel similar

    public function __construct() {
        $this->model        = new EmpleadoModel();
        $this->deptModel    = new DepartamentoModel();
        $this->jornadaModel = new JornadaModel();
        // $this->estadoModel  = new EstadoModel();
    }

    public function index(): void {
        $q = trim($_GET['q'] ?? '');
        $all = $this->model->getAll();

        if ($q !== '') {
            $all = array_filter($all, function($e) use ($q) {
                if (ctype_digit($q) && (int)$q === (int)$e['id']) {
                    return true;
                }
                return stripos($e['nombre'], $q) !== false
                    || stripos($e['nombre2'], $q) !== false
                    || stripos($e['apellido'], $q) !== false
                    || stripos($e['apellido2'], $q) !== false
                    || stripos($e['dpi'], $q) !== false;
            });
        }

        require __DIR__ . '/../view/empleados/index.php';
    }

    public function create(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->create([
                'nombre'           => $_POST['nombre'],
                'nombre2'          => $_POST['nombre2'] ?: null,
                'nombre3'          => $_POST['nombre3'] ?: null,
                'apellido'         => $_POST['apellido'],
                'apellido2'        => $_POST['apellido2'] ?: null,
                'apellido_casada'  => $_POST['apellido_casada'] ?: null,
                'dpi'              => $_POST['dpi'],
                'puesto'           => $_POST['puesto'],
                'departamento_id'  => $_POST['departamento_id'],
                'fecha_ingreso'    => $_POST['fecha_ingreso'],
                'salario_base'     => $_POST['salario_base'],
                'id_estado'        => $_POST['id_estado'],
                'correo_electronico'=> $_POST['correo_electronico'],
                'nit'              => $_POST['nit'],
                'jornada_id'       => $_POST['jornada_id'],
            ]);
            header('Location: index.php?route=empleados');
            exit;
        }

        $departamentos = $this->deptModel->getAll();
        $jornadas      = $this->jornadaModel->getAll();
        $estados       = [1=>'Activo',2=>'Inactivo',3=>'Renuncia',4=>'Despido']; 
        $empleado      = null;
        require __DIR__ . '/../view/empleados/form.php';
    }

    public function edit(): void {
        $id = (int)($_GET['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->update($id, [
                'nombre'           => $_POST['nombre'],
                'nombre2'          => $_POST['nombre2'] ?: null,
                'nombre3'          => $_POST['nombre3'] ?: null,
                'apellido'         => $_POST['apellido'],
                'apellido2'        => $_POST['apellido2'] ?: null,
                'apellido_casada'  => $_POST['apellido_casada'] ?: null,
                'dpi'              => $_POST['dpi'],
                'puesto'           => $_POST['puesto'],
                'departamento_id'  => $_POST['departamento_id'],
                'fecha_ingreso'    => $_POST['fecha_ingreso'],
                'salario_base'     => $_POST['salario_base'],
                'id_estado'        => $_POST['id_estado'],
                'correo_electronico'=> $_POST['correo_electronico'],
                'nit'              => $_POST['nit'],
                'jornada_id'       => $_POST['jornada_id'],
            ]);
            header('Location: index.php?route=empleados');
            exit;
        }

        $empleado      = $this->model->getById($id);
        $departamentos = $this->deptModel->getAll();
        $jornadas      = $this->jornadaModel->getAll();
        $estados       = [1=>'Activo',2=>'Inactivo',3=>'Renuncia',4=>'Despido'];
        require __DIR__ . '/../view/empleados/form.php';
    }

    public function delete(): void {
        $id = (int)($_GET['id'] ?? 0);
        $this->model->delete($id);
        header('Location: index.php?route=empleados');
        exit;
    }

    public function search(): void {
        $q = urlencode($_GET['q'] ?? '');
        header("Location: index.php?route=empleados&q={$q}");
        exit;
    }
}
