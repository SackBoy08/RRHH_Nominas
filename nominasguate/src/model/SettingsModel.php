<?php
namespace Model;

use Config\Database;
use PDO;

class SettingsModel {
  private PDO $db;
  private string $table = 'app_settings';

  public function __construct(){
    $this->db = Database::getInstance()->getConnection();
  }

  public function all(): array {
    $st = $this->db->query("SELECT clave, valor, descripcion, actualizado_en FROM {$this->table} ORDER BY clave");
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    $out = [];
    foreach ($rows as $r) $out[$r['clave']] = $r;
    return $out;
  }

  public function get(string $clave, $default=null){
    $st = $this->db->prepare("SELECT valor FROM {$this->table} WHERE clave=?");
    $st->execute([$clave]);
    $v = $st->fetchColumn();
    return $v!==false ? $v : $default;
  }

  public function set(string $clave, string $valor, ?string $descripcion=null): void {
    $st = $this->db->prepare("
      INSERT INTO {$this->table} (clave, valor, descripcion)
      VALUES (:c,:v,:d)
      ON DUPLICATE KEY UPDATE valor=VALUES(valor), descripcion=VALUES(descripcion)
    ");
    $st->execute([':c'=>$clave, ':v'=>$valor, ':d'=>$descripcion]);
  }
}
