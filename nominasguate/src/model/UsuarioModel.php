<?php
namespace Model;

use Config\Database;
use PDO;

class UsuarioModel {
  private PDO $db;
  private string $table = 'usuarios';

  public function __construct() {
    $this->db = Database::getInstance()->getConnection();
  }

  public function all(): array {
    $st = $this->db->query("SELECT id, username, rol, creado_en FROM {$this->table} ORDER BY username");
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public function findByUsername(string $username): ?array {
    $st = $this->db->prepare("SELECT * FROM {$this->table} WHERE username=? LIMIT 1");
    $st->execute([$username]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
  }

  public function create(string $username, string $password, string $rol='usuario'): int {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $st = $this->db->prepare("INSERT INTO {$this->table} (username, password_hash, rol) VALUES (?,?,?)");
    $st->execute([$username, $hash, $rol]);
    return (int)$this->db->lastInsertId();
  }

  public function updateRole(int $id, string $rol): void {
    $st = $this->db->prepare("UPDATE {$this->table} SET rol=? WHERE id=?");
    $st->execute([$rol, $id]);
  }

  public function resetPassword(int $id, string $newPassword): void {
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $st = $this->db->prepare("UPDATE {$this->table} SET password_hash=? WHERE id=?");
    $st->execute([$hash, $id]);
  }
}
