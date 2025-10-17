<?php
namespace Model;

use Config\Database;
use PDO;

class HorasExtrasModel {
    private PDO $db;
    private string $table = 'horas_extras';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Crea un registro de horas extras vinculado a la nómina correcta.
     * Espera ['empleado_id','periodo_id','id_tipohoraextra','cantidad'].
     */
    public function create(array $data): void {
        // 1) localizar id_nomina
        $stmt = $this->db->prepare(
          "SELECT id 
             FROM nominas 
            WHERE empleado_id = ? 
              AND id_periodo  = ?"
        );
        $stmt->execute([$data['empleado_id'], $data['periodo_id']]);
        $id_nomina = $stmt->fetchColumn();
        if (!$id_nomina) {
            throw new \Exception("No existe nómina para empleado/periodo.");
        }

        // 2) insertar horas_extras
        $sql = "INSERT INTO {$this->table}
                  (id_nomina, id_tipohoraextra, cantidad)
                VALUES
                  (:id_nomina, :id_tipohoraextra, :cantidad)";
        $ins = $this->db->prepare($sql);
        $ins->execute([
            'id_nomina'        => $id_nomina,
            'id_tipohoraextra' => $data['id_tipohoraextra'],
            'cantidad'         => $data['cantidad']
        ]);
    }
}
