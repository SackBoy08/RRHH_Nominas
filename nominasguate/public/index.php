<?php
session_start();

/* Autoload simple */
require_once __DIR__ . '/../config/database.php';
foreach (glob(__DIR__ . '/../src/model/*.php') as $file) {
    require_once $file;
}
foreach (glob(__DIR__ . '/../src/controller/*.php') as $file) {
    require_once $file;
}

/* Imports (opcional; también puedes usar FQN new Controller\X) */
use Controller\LoginController;
use Controller\EmpleadoController;
use Controller\NominaController;
use Controller\HorasExtrasController;
use Controller\DescuentosPuntualesController;
use Controller\VacacionesController;
use Controller\LiquidacionController;
use Controller\UsuarioController;
use Controller\ConfigController;

/* Routing params */
$route  = $_GET['route']  ?? 'login';
$action = $_GET['action'] ?? 'index';

/* Guardia de sesión: todo menos login requiere sesión */
if (empty($_SESSION['user']) && $route !== 'login') {
    header('Location: index.php?route=login');
    exit;
}

/* Enrutamiento principal */
switch ($route) {
    /* ---------- AUTH ---------- */
    case 'login': {
        $ctrl = new LoginController();
        if ($action === 'authenticate') {
            $ctrl->authenticate();      // procesa POST de login
        } else {
            $ctrl->showLoginForm();     // muestra formulario
        }
        break;
    }
    case 'logout': {
        (new LoginController())->logout();
        break;
    }

    /* ---------- HOME ---------- */
    case 'menu': {
        require __DIR__ . '/../src/view/menu.php';
        break;
    }

    /* ---------- EMPLEADOS ---------- */
    case 'empleados': {
        $ctrl = new EmpleadoController();
        switch ($action) {
            case 'create':  $ctrl->create();  break; // GET form / POST create dentro del método
            case 'edit':    $ctrl->edit();    break; // GET form / POST update dentro del método
            case 'delete':  $ctrl->delete();  break;
            case 'search':  $ctrl->search();  break;
            default:        $ctrl->index();   break;
        }
        break;
    }

    /* ---------- NOMINA ---------- */
    case 'nomina': {
        $ctrl = new NominaController();
        switch ($action) {
            case 'create':    $ctrl->create();    break;
            case 'store':     $ctrl->store();     break;
            case 'generate':  $ctrl->generate();  break;
            case 'report':    $ctrl->report();    break; // <-- añadido: coincide con link de la vista
            default:          $ctrl->index();     break;
        }
        break;
    }

    /* ---------- HORAS EXTRAS ---------- */
    case 'horas_extras': {
        $ctrl = new HorasExtrasController();
        switch ($action) {
            case 'create':  $ctrl->create();  break;
            case 'store':   $ctrl->store();   break;
            default:        $ctrl->index();   break;
        }
        break;
    }

    /* ---------- DESCUENTOS PUNTUALES ---------- */
    case 'descuentos_puntuales': {
        $ctrl = new DescuentosPuntualesController();
        switch ($action) {
            case 'create':  $ctrl->create();  break;
            case 'store':   $ctrl->store();   break;
            default:        $ctrl->index();   break;
        }
        break;
    }

    /* ---------- VACACIONES ---------- */
    case 'vacaciones': {
        $ctrl = new VacacionesController();
        switch ($action) {
            case 'assign':  $ctrl->assign();  break;  // GET+POST
            case 'remove':  $ctrl->remove();  break;  // GET+POST
            default:        $ctrl->index();   break;
        }
        break;
    }

    /* ---------- LIQUIDACIONES ---------- */
    case 'liquidaciones': {
        $ctrl = new LiquidacionController();
        switch ($action) {
            case 'form':     $ctrl->form();     break;
            case 'process':  $ctrl->process();  break;
            case 'export':   $ctrl->export();   break;
            default:         $ctrl->index();    break;
        }
        break; // <-- IMPORTANTE: evita fall-through a 'usuarios'
    }

    /* ---------- USUARIOS (solo Admin; el controller también valida) ---------- */
    case 'usuarios': {
        $ctrl = new UsuarioController();
        switch ($action) {
            case 'create':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') { $ctrl->store(); }
                else { $ctrl->create(); }
                break;
            case 'role':   $ctrl->updateRole();   break;
            case 'reset':  $ctrl->resetPassword();break;
            default:       $ctrl->index();        break;
        }
        break;
    }

    /* ---------- CONFIG (solo Admin) ---------- */
    case 'config': {
        $ctrl = new ConfigController();
        switch ($action) {
            case 'ajustes':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') { $ctrl->update(); }
                else { $ctrl->ajustes(); }
                break;
            default:
                $ctrl->ajustes(); break;
        }
        break;
    }

    /* ---------- 404 ---------- */
    default: {
        http_response_code(404);
        echo "<h1>404 - Página no encontrada</h1>";
        break;
    }
}
