<?php
namespace Model;

use Config\Database;
use PDO;

class TipoHorasExtrasModel {
    private PDO $db;
    private string $table = 'tipos_horas_extras';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Recupera todos los tipos de hora extra.
     * @return array [ ['id'=>1,'descripcion'=>'1.5×','multiplicador'=>'1.50'], … ]
     */
    public function getAll(): array {
        $sql = "SELECT 
                    id_tipohoraextra AS id, 
                    descripcion, 
                    multiplicador 
                FROM {$this->table}";
        return $this->db->query($sql)->fetchAll();
    }
}
