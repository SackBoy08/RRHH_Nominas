<?php
namespace Model;

use Config\Database;
use PDO;

class JornadaModel {
    private PDO $db;
    private string $table = 'jornadas';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Recupera todas las jornadas disponibles.
     *
     * @return array Lista de jornadas con id y nombre.
     */
    public function getAll(): array {
        $stmt = $this->db->query("SELECT id, nombre FROM {$this->table}");
        return $stmt->fetchAll();
    }
}