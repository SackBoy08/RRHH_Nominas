<?php
namespace Model;

use Config\Database;
use PDO;
use PDOException;

class LiquidacionModel
{
    private PDO    $db;
    private string $table = 'liquidaciones';

    public function __construct()
    {
        // Obtener la conexión PDO ya configurada
        $this->db = Database::getInstance()
                            ->getConnection();

        // Asegurar que PDO lance excepciones en errores
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Ejecuta el SP sp_liquidar_empleado para un empleado dado,
     * e inserta el registro en la tabla liquidaciones.
     * Luego recupera y retorna ese registro.
     *
     * @param int    $id     ID del empleado
     * @param string $fecha  Fecha de liquidación 'YYYY-MM-DD'
     * @param string $tipo   'Despido' o 'Renuncia'
     * @return array         Datos de la liquidación o array vacío
     */
    public function sp_liquidar_empleado(int $idEmp, string $tipo, string $fechaCalc): array
    {
        // 1) Ejecutar el SP que inserta la liquidación
        $stmt = $this->db->prepare("
            CALL sp_liquidar_empleado(:id, :tipo, :fecha)
        ");
        $stmt->execute([
            ':id'    => $idEmp,
            ':tipo'  => $tipo,
            ':fecha' => $fechaCalc,
        ]);

        // 2) Limpiar todos los result‐sets que el SP pudiera haber abierto
        while ($stmt->nextRowset()) { /* no operamos */ }
        $stmt->closeCursor();

        // 3) Obtener el último ID insertado de MySQL
        $lastId = $this->db
            ->query('SELECT LAST_INSERT_ID()')
            ->fetchColumn();

        if (! $lastId) {
            // Si por algún motivo no se obtuvo ID, devolvemos vacío
            return [];
        }

        // 4) Recuperar el registro completo recién insertado
        $fetch = $this->db->prepare("
            SELECT 
                dias_laborados,
                valor_dias_laborados,
                monto_aguinaldo,
                monto_bono14,
                dias_vac_acumulados,
                valor_vac_acumuladas,
                descuentos_totales,
                total_liquidacion
            FROM liquidaciones
            WHERE id = :id
        ");
        $fetch->execute([':id' => $lastId]);
        $result = $fetch->fetch(\PDO::FETCH_ASSOC);
        $fetch->closeCursor();

        return $result ?: [];
    }

    /**
     * (Opcional) Exporta un array de liquidación a CSV.
     *
     * @param array $liq  Array con datos de la liquidación
     * @return string     Contenido CSV listo para salida
     */
    public function exportCsv(array $liq): string
    {
        $columns = [
            'ID', 'Empleado', 'Fecha', 'Tipo',
            'Días Laborados', 'Valor Días',
            'Aguinaldo', 'Bono14',
            'Vac. Disponibles', 'Valor Vac.',
            'Descuentos', 'Total'
        ];

        $data = [
            $liq['id'],
            $liq['empleado_id'],
            $liq['fecha_liquidacion'],
            $liq['tipo_liquidacion'],
            $liq['dias_laborados'],
            $liq['valor_dias_laborados'],
            $liq['monto_aguinaldo'],
            $liq['monto_bono14'],
            $liq['dias_vac_acumulados'],
            $liq['valor_vac_acumuladas'],
            $liq['descuentos_totales'],
            $liq['total_liquidacion'],
        ];

        // Generar CSV en memoria
        $fh = fopen('php://memory', 'r+');
        fputcsv($fh, $columns);
        fputcsv($fh, $data);
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        return $csv;
    }
}
