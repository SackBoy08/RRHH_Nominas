<?php
namespace Model;

use Config\Database;
use PDO;

class PeriodoModel {
    private PDO $db;
    private string $table = 'periodos';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Trae todos los periodos (alias de id_periodo como id).
     */
    public function getAll(): array {
        $sql = "SELECT 
                    id_periodo AS id, 
                    fecha_inicio, 
                    fecha_fin
                FROM {$this->table}
                ORDER BY fecha_inicio DESC";
        return $this->db->query($sql)->fetchAll();
    }
}
