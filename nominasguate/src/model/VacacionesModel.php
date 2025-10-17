<?php
namespace Model;

use Config\Database;
use PDO;

class VacacionesModel {
    private PDO $db;
    private string $table = 'vacaciones';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtiene, por cada empleado, su nombre, apellido,
     * la fecha de la última asignación de vacaciones (tipo='acumulado'),
     * el total de días asignados, el total de días tomados y los días restantes.
     */
    public function getAll(): array {
        $sql = "
            SELECT 
                e.id,
                e.nombre,
                e.apellido,
                -- Fecha de la última fila con tipo='acumulado'
                (
                  SELECT fecha_registro
                  FROM {$this->table}
                  WHERE empleado_id = e.id
                    AND tipo = 'acumulado'
                  ORDER BY fecha_registro DESC
                  LIMIT 1
                ) AS fecha_inicio,
                -- Suma de todos los 'acumulado'
                COALESCE(SUM(CASE WHEN v.tipo = 'acumulado' THEN v.cantidad_dias ELSE 0 END), 0) AS dias_asignados,
                -- Suma de todos los 'tomado'
                COALESCE(SUM(CASE WHEN v.tipo = 'tomado' THEN v.cantidad_dias ELSE 0 END), 0)     AS dias_tomados,
                -- Días que le quedan al empleado
                (
                  COALESCE(SUM(CASE WHEN v.tipo = 'acumulado' THEN v.cantidad_dias ELSE 0 END), 0)
                  -
                  COALESCE(SUM(CASE WHEN v.tipo = 'tomado' THEN v.cantidad_dias ELSE 0 END), 0)
                ) AS dias_restantes
            FROM empleados e
            LEFT JOIN {$this->table} v 
              ON v.empleado_id = e.id
            GROUP BY 
                e.id, 
                e.nombre, 
                e.apellido
            ORDER BY e.apellido, e.nombre
        ";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Inserta un nuevo registro de vacaciones.
     * La vista envía:
     *   'empleado_id'   => int,
     *   'fecha_registro'=> date (YYYY-MM-DD),
     *   'tipo'          => 'acumulado' o 'tomado',
     *   'cantidad_dias' => decimal
     */
    public function create(array $data): bool {
        $sql = "INSERT INTO {$this->table} 
                (empleado_id, fecha_registro, tipo, cantidad_dias)
                VALUES
                (:empleado_id, :fecha_registro, :tipo, :cantidad_dias)";
        return $this->db->prepare($sql)->execute($data);
    }

    /**
     * (Opcional) Si más adelante necesitas obtener sólo para un empleado,
     * puedes dejar el método getByEmpleadoId() tal como lo tenías en la sesión anterior.
     * Por ahora no es estrictamente necesario para la lista, pero lo dejo aquí
     * como referencia.
     */
    public function getByEmpleadoId(int $empId): array {
        $sql = "
            SELECT 
                COALESCE(SUM(CASE WHEN tipo = 'acumulado' THEN cantidad_dias ELSE 0 END), 0) AS dias_asignados,
                COALESCE(SUM(CASE WHEN tipo = 'tomado' THEN cantidad_dias ELSE 0 END), 0)     AS dias_tomados
            FROM {$this->table}
            WHERE empleado_id = ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$empId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return ['dias_asignados' => 0, 'dias_tomados' => 0];
        }

        return [
            'dias_asignados' => isset($row['dias_asignados']) ? (float)$row['dias_asignados'] : 0.0,
            'dias_tomados'   => isset($row['dias_tomados'])   ? (float)$row['dias_tomados']   : 0.0
        ];
    }
}
