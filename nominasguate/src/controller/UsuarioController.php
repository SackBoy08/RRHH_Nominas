<?php

namespace Controller;

use Model\UsuarioModel;

class UsuarioController
{
  private UsuarioModel $model;

  public function __construct()
  {
    $this->model = new UsuarioModel();
    $this->requireAdmin();
  }

  private function requireAdmin(): void
  {
    if (session_status() !== PHP_SESSION_ACTIVE) {
      session_start();
    }
    $rol = $_SESSION['user']['rol'] ?? '';
    if ($rol !== 'admin') {
      header('Location: index.php?route=menu');
      exit;
    }
  }


  public function index(): void
  {
    $usuarios = $this->model->all();
    require __DIR__ . '/../view/usuarios/index.php';
  }

  public function create(): void
  {
    require __DIR__ . '/../view/usuarios/create.php';
  }

  public function store(): void
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      header('Location: index.php?route=usuarios');
      exit;
    }
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['password_confirm'] ?? '');
    $rol      = ($_POST['rol'] ?? 'usuario') === 'admin' ? 'admin' : 'usuario';

    if ($username === '' || $password === '' || $password !== $confirm) {
      $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Datos inválidos o contraseñas no coinciden.'];
      header('Location: index.php?route=usuarios&action=create');
      exit;
    }

    // unique username
    if ($this->model->findByUsername($username)) {
      $_SESSION['flash'] = ['type' => 'warning', 'msg' => 'El usuario ya existe.'];
      header('Location: index.php?route=usuarios&action=create');
      exit;
    }

    $this->model->create($username, $password, $rol);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Usuario creado correctamente.'];
    header('Location: index.php?route=usuarios&action=index');
    exit;
  }

  public function updateRole(): void
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      header('Location: index.php?route=usuarios');
      exit;
    }
    $id  = (int)($_POST['id'] ?? 0);
    $rol = ($_POST['rol'] ?? 'usuario') === 'admin' ? 'admin' : 'usuario';
    if ($id > 0) $this->model->updateRole($id, $rol);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Rol actualizado.'];
    header('Location: index.php?route=usuarios');
    exit;
  }

  public function resetPassword(): void
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      header('Location: index.php?route=usuarios');
      exit;
    }
    $id   = (int)($_POST['id'] ?? 0);
    $pass = (string)($_POST['new_password'] ?? '');
    $conf = (string)($_POST['new_password_confirm'] ?? '');
    if ($id > 0 && $pass !== '' && $pass === $conf) {
      $this->model->resetPassword($id, $pass);
      $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Contraseña actualizada.'];
    } else {
      $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'No se pudo actualizar la contraseña.'];
    }
    header('Location: index.php?route=usuarios');
    exit;
  }
}
