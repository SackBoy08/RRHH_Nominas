<?php
namespace Model;

use Config\Database;
use PDO;

class DepartamentoModel {
    private PDO $db;
    private string $table = 'departamentos';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY nombre");
        return $stmt->fetchAll();
    }
}
