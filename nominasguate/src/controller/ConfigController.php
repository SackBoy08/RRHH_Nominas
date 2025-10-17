<?php

namespace Controller;

use Model\SettingsModel;

class ConfigController
{
  private SettingsModel $settings;

  public function __construct()
  {
    $this->settings = new SettingsModel();
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


  public function ajustes(): void
  {
    $s = $this->settings->all();
    // Normaliza claves esperadas
    $S = [
      'NOMBRE_EMPRESA' => $s['NOMBRE_EMPRESA']['valor'] ?? '',
      'IGSS_PORCENTAJE' => $s['IGSS_PORCENTAJE']['valor'] ?? '0.0483',
      'ISR_PORCENTAJE' => $s['ISR_PORCENTAJE']['valor'] ?? '0.05',
    ];
    require __DIR__ . '/../view/config/ajustes.php';
  }

  public function update(): void
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      header('Location: index.php?route=config&action=ajustes');
      exit;
    }
    $this->settings->set('NOMBRE_EMPRESA', trim($_POST['NOMBRE_EMPRESA'] ?? ''), 'Nombre legal a mostrar');
    $this->settings->set('IGSS_PORCENTAJE', (string)($_POST['IGSS_PORCENTAJE'] ?? '0.0483'), 'Porcentaje IGSS empleado');
    $this->settings->set('ISR_PORCENTAJE',  (string)($_POST['ISR_PORCENTAJE']  ?? '0.05'),   'Porcentaje ISR base');

    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Ajustes guardados.'];
    header('Location: index.php?route=config&action=ajustes');
    exit;
  }
}
