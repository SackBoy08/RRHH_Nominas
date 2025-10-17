<?php
namespace Model;

use Config\Database;
use PDO;
use PDOException;

class EmpleadoModel {
    private PDO $db;
    private string $table = 'empleados';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        // Aseguramos que PDO lance excepciones en errores
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function getAll(): array {
        $sql = "SELECT 
                    e.*, 
                    d.nombre AS departamento, 
                    j.nombre AS jornada
                FROM {$this->table} e
                JOIN departamentos d ON e.departamento_id = d.id
                JOIN jornadas j ON e.jornada_id = j.id";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Inserta un nuevo empleado y luego trata de ejecutar el SP
     * sp_acumular_vacaciones_mensua para asignarle las vacaciones iniciales.
     *
     * @param array $data Array asociativo con las claves:
     *   - nombre, nombre2, nombre3, apellido, apellido2, apellido_casada
     *   - dpi, puesto, departamento_id, fecha_ingreso, salario_base
     *   - id_estado, correo_electronico, nit, jornada_id
     *
     * @return bool True si el empleado se crea correctamente (aunque el SP falle).
     */
    public function create(array $data): bool {
        try {
            // 1) Iniciar transacción solo para el INSERT de empleado
            $this->db->beginTransaction();

            $sql = "INSERT INTO {$this->table}
                    (nombre, nombre2, nombre3, apellido, apellido2, apellido_casada,
                     dpi, puesto, departamento_id, fecha_ingreso, salario_base,
                     id_estado, correo_electronico, nit, jornada_id)
                    VALUES
                    (:nombre, :nombre2, :nombre3, :apellido, :apellido2, :apellido_casada,
                     :dpi, :puesto, :departamento_id, :fecha_ingreso, :salario_base,
                     :id_estado, :correo_electronico, :nit, :jornada_id)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($data);

            // 2) Obtener el ID recién insertado
            $empleadoId = (int)$this->db->lastInsertId();

            // 3) Commit de la transacción de INSERT
            $this->db->commit();

        } catch (PDOException $e) {
            // Si falla el INSERT, hacemos rollback y devolvemos false
            $this->db->rollBack();
            // Opcional: loggear $e->getMessage()
            return false;
        }

        // 4) Intentar ejecutar el stored procedure para asignar vacaciones
        try {
            $this->db->exec("CALL sp_acumular_vacaciones_mensua()");
        } catch (PDOException $e) {
            // Si este SP falla, opcionalmente loguear el error
            // pero no hacemos rollback porque ya creamos el empleado
        }

        // Si llegamos aquí, al menos el empleado ya se insertó. Devolvemos true.
        return true;
    }

    public function update(int $id, array $data): bool {
        $data['id'] = $id;
        $sql = "UPDATE {$this->table} SET
                    nombre            = :nombre,
                    nombre2           = :nombre2,
                    nombre3           = :nombre3,
                    apellido          = :apellido,
                    apellido2         = :apellido2,
                    apellido_casada   = :apellido_casada,
                    dpi               = :dpi,
                    puesto            = :puesto,
                    departamento_id   = :departamento_id,
                    fecha_ingreso     = :fecha_ingreso,
                    salario_base      = :salario_base,
                    id_estado         = :id_estado,
                    correo_electronico= :correo_electronico,
                    nit               = :nit,
                    jornada_id        = :jornada_id
                WHERE id = :id";
        return $this->db->prepare($sql)->execute($data);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table} WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }
}
