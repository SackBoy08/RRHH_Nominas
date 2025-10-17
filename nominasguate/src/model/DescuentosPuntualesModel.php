<?php
namespace Model;

use Config\Database;
use PDO;

class DescuentosPuntualesModel {
    private PDO $db;
    private string $table = 'descuentos_puntuales';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Inserta un descuento puntual para un empleado.
     *
     * @param array $data [
     *   'empleado_id'    => int,
     *   'fecha'          => string (YYYY-MM-DD),
     *   'tipo_descuento' => 'hora'|'día',
     *   'cantidad'       => float,
     *   'motivo'         => string
     * ]
     */
    public function create(array $data): void {
        $sql = "INSERT INTO {$this->table}
                  (empleado_id, fecha, tipo_descuento, cantidad, motivo)
                VALUES
                  (:empleado_id, :fecha, :tipo_descuento, :cantidad, :motivo)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'empleado_id'    => $data['empleado_id'],
            'fecha'          => $data['fecha'],
            'tipo_descuento' => $data['tipo_descuento'],
            'cantidad'       => $data['cantidad'],
            'motivo'         => $data['motivo'],
        ]);
    }
}
