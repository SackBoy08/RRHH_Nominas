<?php
namespace Model;

use Config\Database;
use PDO;

class NominaModel {
    private PDO $db;
    private string $table = 'nominas';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Inserta/actualiza fila base para cada empleado seleccionado
    public function createForEmployees(int $periodoId, array $empIds): void {
    // Usamos INSERT ... SELECT para traer salario_base directamente
    $sql = "
      INSERT INTO nominas
        (empleado_id, id_periodo, salario_bruto, fecha_pago)
      SELECT 
        e.id, 
        :periodo, 
        e.salario_base, 
        CURDATE()
      FROM empleados e
      WHERE e.id = :emp_id
      ON DUPLICATE KEY UPDATE
        salario_bruto = VALUES(salario_bruto),
        fecha_pago    = VALUES(fecha_pago)
    ";
    $stmt = $this->db->prepare($sql);

    foreach ($empIds as $empId) {
        $stmt->execute([
            'periodo' => $periodoId,
            'emp_id'  => $empId
        ]);
    }
}

    // Llama al SP maestro que procesa cálculos para todo el periodo
    public function processAll(int $periodoId): void {
        $stmt = $this->db->prepare("CALL sp_generar_nomina(?)");
        $stmt->execute([$periodoId]);
    }

    // Recupera las filas procesadas para mostrar
    public function getByPeriodo(int $periodoId): array {
    $sql = "
        SELECT
          n.id,
          n.empleado_id,                                -- <— lo añadimos aquí
          CONCAT(e.nombre,' ',e.apellido) AS nombre_completo,
          n.salario_bruto,
          n.descuento_igss,
          n.descuento_isr,
          n.otros_descuentos,
          n.horas_extras,
          n.pago_horas_extras,
          n.bono14,
          n.salario_neto,
          n.salario_a_devengar
        FROM {$this->table} n
        JOIN empleados e ON n.empleado_id = e.id
        WHERE n.id_periodo = ?
        ORDER BY e.apellido, e.nombre
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$periodoId]);
    return $stmt->fetchAll();
    }
}
