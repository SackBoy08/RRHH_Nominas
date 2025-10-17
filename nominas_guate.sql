-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-10-2025 a las 17:45:11
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `nominas_guate`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `calcular_bono14` (IN `p_empleado_id` INT, IN `p_fecha_pago` DATE, OUT `p_monto_bono14` DECIMAL(10,2))   BEGIN
  DECLARE fecha_ingreso DATE; DECLARE estado INT; DECLARE start_per DATE; DECLARE end_per DATE;
  DECLARE inicio_eff DATE; DECLARE meses_trab INT; DECLARE salario_base DECIMAL(10,2);

  SELECT fecha_ingreso, id_estado, salario_base
    INTO fecha_ingreso, estado, salario_base
  FROM empleados WHERE id = p_empleado_id;

  IF estado NOT IN (1,3,4,5) OR fecha_ingreso IS NULL THEN
    SET p_monto_bono14 = 0;
  ELSE
    SET start_per = STR_TO_DATE(CONCAT(YEAR(p_fecha_pago)-1,'-07-01'),'%Y-%m-%d');
    SET end_per   = STR_TO_DATE(CONCAT(YEAR(p_fecha_pago),  '-06-30'),'%Y-%m-%d');
    SET inicio_eff = IF(fecha_ingreso > start_per, fecha_ingreso, start_per);
    SET meses_trab = TIMESTAMPDIFF(MONTH, inicio_eff, end_per) + 1;
    SET meses_trab = GREATEST(0, LEAST(meses_trab,12));
    SET p_monto_bono14 = ROUND(salario_base * meses_trab / 12, 2);
  END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `calcular_descuentos_puntuales_por_nomina` (IN `p_id_nomina` INT)   BEGIN
  DECLARE emp INT; DECLARE sal_base DECIMAL(10,2); DECLARE fecha_i DATE; DECLARE fecha_f DATE; DECLARE total_desc DECIMAL(10,2);

  SELECT n.empleado_id, e.salario_base, p.fecha_inicio, p.fecha_fin
    INTO emp, sal_base, fecha_i, fecha_f
  FROM nominas n JOIN empleados e ON n.empleado_id = e.id
  JOIN periodos p ON n.id_periodo = p.id_periodo
  WHERE n.id = p_id_nomina;

  SELECT IFNULL(SUM(
    CASE dp.tipo_descuento
      WHEN 'hora' THEN (sal_base/30/8)*dp.cantidad
      WHEN 'día'  THEN (sal_base/30)*dp.cantidad
      ELSE 0 END
  ),0) INTO total_desc
  FROM descuentos_puntuales dp
  WHERE dp.empleado_id = emp AND dp.fecha BETWEEN fecha_i AND fecha_f;

  UPDATE nominas SET otros_descuentos = ROUND(total_desc,2) WHERE id = p_id_nomina;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `calcular_horas_extras_por_nomina` (IN `p_id_nomina` INT)   BEGIN
  DECLARE sum_horas DECIMAL(10,2) DEFAULT 0;
  DECLARE weighted_horas DECIMAL(10,2) DEFAULT 0;
  DECLARE sal_base DECIMAL(10,2); DECLARE valor_hora DECIMAL(10,6); DECLARE pago_extra DECIMAL(10,2);

  SELECT IFNULL(SUM(cantidad),0) INTO sum_horas FROM horas_extras WHERE id_nomina = p_id_nomina;
  SELECT IFNULL(SUM(he.cantidad*th.multiplicador),0) INTO weighted_horas
  FROM horas_extras he JOIN tipos_horas_extras th ON he.id_tipohoraextra = th.id_tipohoraextra
  WHERE he.id_nomina = p_id_nomina;

  SELECT e.salario_base INTO sal_base
  FROM nominas n JOIN empleados e ON n.empleado_id = e.id WHERE n.id = p_id_nomina;

  SET valor_hora = sal_base/30/8;
  SET pago_extra = ROUND(valor_hora * weighted_horas, 2);

  UPDATE nominas SET horas_extras=sum_horas, pago_horas_extras=pago_extra WHERE id = p_id_nomina;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `calcular_igss_por_nomina` (IN `p_id_nomina` INT)   BEGIN
  DECLARE salario_base DECIMAL(10,2);
  SELECT e.salario_base INTO salario_base
  FROM nominas n JOIN empleados e ON n.empleado_id = e.id
  WHERE n.id = p_id_nomina;
  UPDATE nominas SET descuento_igss = ROUND(salario_base*0.0483,2) WHERE id = p_id_nomina;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `calcular_isr_por_nomina` (IN `p_id_nomina` INT)   BEGIN
  DECLARE salario_base DECIMAL(10,2); DECLARE isr DECIMAL(10,2);
  SELECT e.salario_base INTO salario_base
  FROM nominas n JOIN empleados e ON n.empleado_id = e.id
  WHERE n.id = p_id_nomina;

  -- Regla simple ejemplo: 5% hasta 30,000; excedente al 7%
  IF salario_base <= 30000 THEN
    SET isr = salario_base * 0.05;
  ELSE
    SET isr = 1500 + ((salario_base - 30000) * 0.07);
  END IF;

  UPDATE nominas SET descuento_isr = ROUND(isr,2) WHERE id = p_id_nomina;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `calcular_salario_neto_por_nomina` (IN `p_id_nomina` INT)   BEGIN
  DECLARE sb DECIMAL(10,2); DECLARE igss DECIMAL(10,2); DECLARE isr DECIMAL(10,2);
  DECLARE otros DECIMAL(10,2); DECLARE pago_extras DECIMAL(10,2);
  DECLARE neto DECIMAL(10,2); DECLARE devengar DECIMAL(10,2);

  SELECT e.salario_base, n.descuento_igss, n.descuento_isr, n.otros_descuentos, n.pago_horas_extras
  INTO sb, igss, isr, otros, pago_extras
  FROM nominas n JOIN empleados e ON n.empleado_id=e.id
  WHERE n.id = p_id_nomina;

  SET neto = ROUND(sb - igss - isr - otros, 2);
  SET devengar = ROUND(neto + pago_extras, 2);

  UPDATE nominas SET salario_neto=neto, salario_a_devengar=devengar WHERE id = p_id_nomina;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_acumular_vacaciones_mensual` ()   BEGIN
  DECLARE fin INT DEFAULT 0; DECLARE emp_id INT; DECLARE ingreso DATE;
  DECLARE meses_total INT; DECLARE acumulados_existentes INT; DECLARE meses_faltantes INT; DECLARE i INT;

  DECLARE cur CURSOR FOR SELECT id, fecha_ingreso FROM empleados;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET fin=1;

  OPEN cur;
  bucle: LOOP
    FETCH cur INTO emp_id, ingreso;
    IF fin=1 THEN LEAVE bucle; END IF;

    SET meses_total = TIMESTAMPDIFF(MONTH, ingreso, CURDATE());
    SELECT COUNT(*) INTO acumulados_existentes FROM vacaciones WHERE empleado_id=emp_id AND tipo='acumulado';
    SET meses_faltantes = meses_total - acumulados_existentes;

    SET i=1;
    WHILE i <= meses_faltantes DO
      INSERT INTO vacaciones(empleado_id, fecha_registro, tipo, cantidad_dias)
      VALUES (emp_id, CURDATE(), 'acumulado', 1.25);
      SET i = i + 1;
    END WHILE;
  END LOOP;
  CLOSE cur;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_calcular_pago_anual` (IN `p_tipo_pago` ENUM('aguinaldo','bono14'), IN `p_anio` INT)   BEGIN
  DECLARE v_inicio DATE; DECLARE v_fin DATE; DECLARE v_fechaPago DATE;
  IF p_tipo_pago='bono14' THEN
    SET v_inicio=STR_TO_DATE(CONCAT(p_anio-1,'-07-01'),'%Y-%m-%d');
    SET v_fin   =STR_TO_DATE(CONCAT(p_anio  ,'-06-30'),'%Y-%m-%d');
    SET v_fechaPago=STR_TO_DATE(CONCAT(p_anio,'-07-10'),'%Y-%m-%d');
  ELSEIF p_tipo_pago='aguinaldo' THEN
    SET v_inicio=STR_TO_DATE(CONCAT(p_anio-1,'-12-01'),'%Y-%m-%d');
    SET v_fin   =STR_TO_DATE(CONCAT(p_anio  ,'-11-30'),'%Y-%m-%d');
    SET v_fechaPago=STR_TO_DATE(CONCAT(p_anio,'-12-10'),'%Y-%m-%d');
  ELSE
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Tipo de pago inválido';
  END IF;

  DELETE FROM pagos_anuales WHERE tipo_pago=p_tipo_pago AND anio=p_anio;

  INSERT INTO pagos_anuales(empleado_id,tipo_pago,fecha_pago,monto,anio)
  SELECT e.id,
         p_tipo_pago,
         v_fechaPago,
         ROUND(CASE
           WHEN e.fecha_ingreso <= v_inicio THEN e.salario_base
           WHEN e.fecha_ingreso BETWEEN v_inicio AND v_fin THEN e.salario_base * (DATEDIFF(v_fin, e.fecha_ingreso)+1)/365
           ELSE 0 END, 2),
         p_anio
  FROM empleados e WHERE e.fecha_ingreso <= v_fin AND e.salario_base > 0;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_calc_aguinaldo` (IN `p_empleado_id` INT, IN `p_fecha_calc` DATE, OUT `p_monto` DECIMAL(10,2))   BEGIN
  DECLARE v_sbase DECIMAL(10,2); DECLARE v_fingreso DATE; DECLARE v_inicio DATE;
  DECLARE v_dias INT; DECLARE v_res DECIMAL(10,2);

  SELECT salario_base, fecha_ingreso INTO v_sbase, v_fingreso FROM empleados WHERE id = p_empleado_id;

  IF p_fecha_calc >= STR_TO_DATE(CONCAT(YEAR(p_fecha_calc),'-12-01'),'%Y-%m-%d') THEN
    SET v_inicio = STR_TO_DATE(CONCAT(YEAR(p_fecha_calc),'-12-01'),'%Y-%m-%d');
  ELSE
    SET v_inicio = STR_TO_DATE(CONCAT(YEAR(p_fecha_calc)-1,'-12-01'),'%Y-%m-%d');
  END IF;

  IF v_fingreso > v_inicio THEN SET v_inicio = v_fingreso; END IF;

  SET v_dias = LEAST(365, GREATEST(0, DATEDIFF(p_fecha_calc, v_inicio)+1));
  SET v_res = ROUND((v_sbase / 365) * v_dias, 2);
  IF v_fingreso > p_fecha_calc THEN SET v_res = 0; END IF;

  SET p_monto = v_res;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_calc_bono14` (IN `p_empleado_id` INT, IN `p_fecha_calc` DATE, OUT `p_monto` DECIMAL(10,2))   BEGIN
  DECLARE v_sbase DECIMAL(10,2); DECLARE v_fingreso DATE; DECLARE v_inicio DATE;
  DECLARE v_dias INT; DECLARE v_res DECIMAL(10,2);

  SELECT salario_base, fecha_ingreso INTO v_sbase, v_fingreso FROM empleados WHERE id = p_empleado_id;

  IF p_fecha_calc >= STR_TO_DATE(CONCAT(YEAR(p_fecha_calc),'-07-01'),'%Y-%m-%d') THEN
    SET v_inicio = STR_TO_DATE(CONCAT(YEAR(p_fecha_calc),'-07-01'),'%Y-%m-%d');
  ELSE
    SET v_inicio = STR_TO_DATE(CONCAT(YEAR(p_fecha_calc)-1,'-07-01'),'%Y-%m-%d');
  END IF;

  IF v_fingreso > v_inicio THEN SET v_inicio = v_fingreso; END IF;

  SET v_dias = LEAST(365, GREATEST(0, DATEDIFF(p_fecha_calc, v_inicio)+1));
  SET v_res = ROUND((v_sbase / 365) * v_dias, 2);
  IF v_fingreso > p_fecha_calc THEN SET v_res = 0; END IF;

  SET p_monto = v_res;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_calc_vacaciones` (IN `p_empleado_id` INT, OUT `p_valor_vac` DECIMAL(10,2))   BEGIN
  DECLARE v_sbase DECIMAL(10,2); DECLARE v_dias_disp DECIMAL(10,2);
  SELECT e.salario_base, COALESCE(vv.dias_disponibles,0) INTO v_sbase, v_dias_disp
  FROM empleados e LEFT JOIN vista_estado_vacaciones vv ON vv.empleado_id = e.id
  WHERE e.id = p_empleado_id;
  SET p_valor_vac = ROUND((v_sbase / 30) * v_dias_disp, 2);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_calc_valor_dias_laborados` (IN `p_empleado_id` INT, IN `p_fecha_calc` DATE, IN `p_tipo_liquidacion` VARCHAR(20), OUT `p_valor_dias` DECIMAL(10,2))   BEGIN
  DECLARE v_sbase DECIMAL(10,2); DECLARE v_fingreso DATE; DECLARE v_total_days INT; DECLARE v_res DECIMAL(10,2);
  SELECT salario_base, fecha_ingreso INTO v_sbase, v_fingreso FROM empleados WHERE id = p_empleado_id;
  IF p_tipo_liquidacion='Renuncia' OR v_fingreso > p_fecha_calc THEN
    SET v_res = 0;
  ELSE
    SET v_total_days = GREATEST(0, DATEDIFF(p_fecha_calc, v_fingreso)+1);
    SET v_res = ROUND((v_sbase / 365) * v_total_days, 2);
  END IF;
  SET p_valor_dias = v_res;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_generar_nomina` (IN `p_id_periodo` INT)   BEGIN
  /* -------------------------*
   *  Variables de trabajo
   * -------------------------*/
  DECLARE v_tipo VARCHAR(50);
  DECLARE v_inicio DATE;
  DECLARE v_fin DATE;
  DECLARE semana INT;

  DECLARE done INT DEFAULT 0;
  DECLARE v_emp_id INT;
  DECLARE v_sal_base DECIMAL(12,2);

  DECLARE v_bruto DECIMAL(12,2);
  DECLARE v_igss  DECIMAL(12,2);
  DECLARE v_isr   DECIMAL(12,2);
  DECLARE v_bono  DECIMAL(12,2);
  DECLARE v_fecha_pago DATE;

  /* Cursor de empleados activos */
  DECLARE cur CURSOR FOR
    SELECT id, salario_base
    FROM empleados
    WHERE id_estado = 1;

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

  /* Handler de errores: rollback y propagar error */
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  /* -------------------------*
   *  Validaciones de período
   * -------------------------*/
  SELECT tipo_periodo, fecha_inicio, fecha_fin
    INTO v_tipo, v_inicio, v_fin
  FROM periodos
  WHERE id_periodo = p_id_periodo
  LIMIT 1;

  IF v_inicio IS NULL OR v_fin IS NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'sp_generar_nomina: Periodo no existente o inválido';
  END IF;

  /* Semana dentro del mes del período (para tu lógica semanal) */
  SET semana = CEIL(DAY(v_inicio)/7);
  SET v_fecha_pago = v_fin;

  /* -------------------------*
   *  Transacción principal
   * -------------------------*/
  START TRANSACTION;

    /* No borramos nóminas: hacemos UPSERT por (empleado, período) */
    OPEN cur;
      emp_loop: LOOP
        FETCH cur INTO v_emp_id, v_sal_base;
        IF done = 1 THEN
          LEAVE emp_loop;
        END IF;

        /* Defaults */
        SET v_bono = 0; SET v_bruto = 0; SET v_igss = 0; SET v_isr = 0;

        /* Reglas de cálculo base (usa tu lógica original) */
        IF v_tipo IN ('Bono 14', 'Aguinaldo') THEN
          SET v_bono = ROUND(v_sal_base, 2);
        ELSEIF v_tipo LIKE 'Semanal%' OR v_tipo = 'Semanal' THEN
          SET v_bruto = ROUND(v_sal_base/4, 2);
          IF semana < 4 THEN
            SET v_igss = 0; SET v_isr = 0;
          ELSE
            SET v_igss = ROUND(v_sal_base*0.0483, 2);
            SET v_isr  = ROUND(v_sal_base*0.05,  2);
          END IF;
        ELSEIF v_tipo LIKE 'Quincenal%' OR v_tipo = 'Quincenal' THEN
          SET v_bruto = ROUND(v_sal_base/2, 2);
          IF DAY(v_inicio) <= 15 THEN
            SET v_igss = 0; SET v_isr = 0;
          ELSE
            SET v_igss = ROUND(v_sal_base*0.0483, 2);
            SET v_isr  = ROUND(v_sal_base*0.05,  2);
          END IF;
        ELSE
          /* Mensual (u otro → mensual por defecto) */
          SET v_bruto = ROUND(v_sal_base, 2);
          SET v_igss  = ROUND(v_sal_base*0.0483, 2);
          SET v_isr   = ROUND(v_sal_base*0.05,  2);
        END IF;

        /* UPSERT de nómina base (HE/Descuentos se recalculan después) */
        INSERT INTO nominas (
          empleado_id, id_periodo, salario_bruto, descuento_igss, descuento_isr,
          otros_descuentos, salario_neto, fecha_pago, horas_extras, pago_horas_extras,
          bono14, salario_a_devengar
        ) VALUES (
          v_emp_id, p_id_periodo, v_bruto, v_igss, v_isr,
          0, (v_bruto - v_igss - v_isr), v_fecha_pago, 0, 0,
          v_bono, (v_bruto - v_igss - v_isr + v_bono)
        )
        ON DUPLICATE KEY UPDATE
          salario_bruto       = VALUES(salario_bruto),
          descuento_igss      = VALUES(descuento_igss),
          descuento_isr       = VALUES(descuento_isr),
          /* otros_descuentos/HE se recalculan abajo */
          salario_neto        = VALUES(salario_neto),
          fecha_pago          = VALUES(fecha_pago),
          bono14              = VALUES(bono14),
          salario_a_devengar  = VALUES(salario_a_devengar);
      END LOOP;
    CLOSE cur;

    /* -------------------------*
     *  Recalcular Horas Extras
     * -------------------------*/
    UPDATE nominas n
    LEFT JOIN (
      SELECT he.id_nomina,
             ROUND(SUM(he.cantidad), 2) AS horas,
             ROUND(SUM(he.cantidad * (e.salario_base/30/8) * th.multiplicador), 2) AS pago
      FROM horas_extras he
      JOIN nominas nn ON nn.id = he.id_nomina
      JOIN empleados e ON e.id = nn.empleado_id
      JOIN tipos_horas_extras th ON th.id_tipohoraextra = he.id_tipohoraextra
      WHERE nn.id_periodo = p_id_periodo
      GROUP BY he.id_nomina
    ) x ON x.id_nomina = n.id
    SET n.horas_extras       = COALESCE(x.horas, 0),
        n.pago_horas_extras  = COALESCE(x.pago,  0),
        n.salario_a_devengar = ROUND(n.salario_bruto - n.descuento_igss - n.descuento_isr + COALESCE(x.pago,0) + n.bono14, 2)
    WHERE n.id_periodo = p_id_periodo;

    /* ------------------------------------*
     *  Recalcular Descuentos Puntuales
     * ------------------------------------*/
    UPDATE nominas n
    LEFT JOIN (
      SELECT dp.empleado_id,
             ROUND(SUM(
               COALESCE(
                 dp.monto,
                 CASE
                   WHEN dp.tipo_descuento = 'hora' THEN (e.salario_base/30/8) * dp.cantidad
                   WHEN dp.tipo_descuento = 'día'  THEN (e.salario_base/30)   * dp.cantidad
                   ELSE 0
                 END
               )
             ), 2) AS desc_total
      FROM descuentos_puntuales dp
      JOIN empleados e ON e.id = dp.empleado_id
      JOIN periodos p ON p.id_periodo = p_id_periodo
      WHERE dp.fecha BETWEEN p.fecha_inicio AND p.fecha_fin
      GROUP BY dp.empleado_id
    ) d ON d.empleado_id = n.empleado_id
    SET n.otros_descuentos   = COALESCE(d.desc_total, 0),
        n.salario_neto       = ROUND(n.salario_bruto - n.descuento_igss - n.descuento_isr - COALESCE(d.desc_total,0), 2),
        n.salario_a_devengar = ROUND(n.salario_bruto - n.descuento_igss - n.descuento_isr - COALESCE(d.desc_total,0) + n.pago_horas_extras + n.bono14, 2)
    WHERE n.id_periodo = p_id_periodo;

  COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_liquidar_empleado` (IN `p_empleado_id` INT, IN `p_tipo_liquidacion` VARCHAR(20), IN `p_fecha_calc` DATE)   BEGIN
  DECLARE v_fecha_ingreso DATE; DECLARE v_salario_base DECIMAL(10,2);
  DECLARE v_dias_lab INT DEFAULT 0; DECLARE v_valor_dias DECIMAL(10,2) DEFAULT 0;
  DECLARE v_bono14 DECIMAL(10,2) DEFAULT 0; DECLARE v_aguinaldo DECIMAL(10,2) DEFAULT 0;
  DECLARE v_dias_vac INT DEFAULT 0; DECLARE v_valor_vac DECIMAL(10,2) DEFAULT 0;
  DECLARE v_desc_total DECIMAL(10,2) DEFAULT 0; DECLARE v_total DECIMAL(12,2) DEFAULT 0;

  SELECT fecha_ingreso, salario_base INTO v_fecha_ingreso, v_salario_base FROM empleados WHERE id = p_empleado_id;
  SET v_dias_lab = GREATEST(DATEDIFF(p_fecha_calc, v_fecha_ingreso)+1, 0);

  CALL sp_calc_valor_dias_laborados(p_empleado_id, p_fecha_calc, p_tipo_liquidacion, v_valor_dias);
  CALL sp_calc_bono14(p_empleado_id, p_fecha_calc, v_bono14);
  CALL sp_calc_aguinaldo(p_empleado_id, p_fecha_calc, v_aguinaldo);

  SELECT COALESCE(dias_disponibles,0) INTO v_dias_vac FROM vista_estado_vacaciones WHERE empleado_id=p_empleado_id;
  CALL sp_calc_vacaciones(p_empleado_id, v_valor_vac);

  SET v_total = ROUND(v_valor_dias + v_aguinaldo + v_bono14 + v_valor_vac - v_desc_total, 2);

  INSERT INTO liquidaciones(
    empleado_id, fecha_liquidacion, tipo_liquidacion,
    dias_laborados, valor_dias_laborados,
    monto_aguinaldo, monto_bono14,
    dias_vac_acumulados, valor_vac_acumuladas,
    descuentos_totales, total_liquidacion
  ) VALUES (
    p_empleado_id, p_fecha_calc, IF(p_tipo_liquidacion IN ('Despido','Renuncia'), p_tipo_liquidacion,'Renuncia'),
    v_dias_lab, v_valor_dias,
    v_aguinaldo, v_bono14,
    v_dias_vac, v_valor_vac,
    v_desc_total, v_total
  );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_procesar_nominas_por_periodo` (IN `p_periodo_id` INT)   BEGIN
  DECLARE done INT DEFAULT 0; DECLARE v_nomina INT;
  DECLARE c CURSOR FOR SELECT id FROM nominas WHERE id_periodo=p_periodo_id;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done=1;

  OPEN c;
  l: LOOP
    FETCH c INTO v_nomina;
    IF done=1 THEN LEAVE l; END IF;

    CALL calcular_horas_extras_por_nomina(v_nomina);
    CALL calcular_descuentos_puntuales_por_nomina(v_nomina);
    CALL calcular_igss_por_nomina(v_nomina);
    CALL calcular_isr_por_nomina(v_nomina);
    CALL calcular_salario_neto_por_nomina(v_nomina);
  END LOOP;
  CLOSE c;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_registrar_bono14` (IN `p_empleado_id` INT, IN `p_fecha_liquid` DATE)   BEGIN
  DECLARE v_monto DECIMAL(10,2);
  CALL calcular_bono14(p_empleado_id, p_fecha_liquid, v_monto);
  INSERT INTO pagos_anuales(empleado_id, tipo_pago, fecha_pago, monto, anio)
  VALUES (p_empleado_id,'bono14', p_fecha_liquid, v_monto, YEAR(p_fecha_liquid));
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_registrar_vacaciones_tomadas` (IN `p_empleado_id` INT, IN `p_cantidad_dias` DECIMAL(5,2), IN `p_fecha` DATE)   BEGIN
  DECLARE v_acum DECIMAL(5,2) DEFAULT 0; DECLARE v_tom DECIMAL(5,2) DEFAULT 0; DECLARE v_disp DECIMAL(5,2);

  SELECT IFNULL(SUM(cantidad_dias),0) INTO v_acum FROM vacaciones WHERE empleado_id=p_empleado_id AND tipo='acumulado';
  SELECT IFNULL(SUM(cantidad_dias),0) INTO v_tom  FROM vacaciones WHERE empleado_id=p_empleado_id AND tipo='tomado';
  SET v_disp = v_acum - v_tom;

  IF v_disp >= p_cantidad_dias THEN
    INSERT INTO vacaciones(empleado_id,fecha_registro,tipo,cantidad_dias) VALUES (p_empleado_id,p_fecha,'tomado',p_cantidad_dias);
  ELSE
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='No hay suficientes días de vacaciones disponibles.';
  END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `app_settings`
--

CREATE TABLE `app_settings` (
  `clave` varchar(64) NOT NULL,
  `valor` varchar(255) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `app_settings`
--

INSERT INTO `app_settings` (`clave`, `valor`, `descripcion`, `actualizado_en`) VALUES
('IGSS_PORCENTAJE', '0.0483', 'Porcentaje IGSS empleado', '2025-10-15 15:21:58'),
('ISR_PORCENTAJE', '0.05', 'Porcentaje ISR base', '2025-10-15 15:21:58'),
('NOMBRE_EMPRESA', 'Nominas Guatemala S.A.', 'Nombre legal a mostrar', '2025-10-15 15:26:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencia`
--

CREATE TABLE `asistencia` (
  `id` int(11) NOT NULL,
  `empleado_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `horas_trabajadas` decimal(4,2) NOT NULL,
  `observaciones` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `departamentos`
--

CREATE TABLE `departamentos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `departamentos`
--

INSERT INTO `departamentos` (`id`, `nombre`) VALUES
(1, 'Gerencia General');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `descuentos_puntuales`
--

CREATE TABLE `descuentos_puntuales` (
  `id` int(11) NOT NULL,
  `empleado_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `tipo_descuento` enum('hora','día') NOT NULL,
  `cantidad` decimal(4,2) NOT NULL,
  `motivo` varchar(255) NOT NULL,
  `monto` decimal(10,2) DEFAULT NULL,
  `aplicado_en_nomina` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `descuentos_puntuales`
--

INSERT INTO `descuentos_puntuales` (`id`, `empleado_id`, `fecha`, `tipo_descuento`, `cantidad`, `motivo`, `monto`, `aplicado_en_nomina`) VALUES
(1, 1, '2025-10-15', 'hora', 3.00, 'permiso sin goce de sueldo', NULL, 0),
(2, 3, '2025-10-15', 'día', 1.00, 'Ausencia sin razón', NULL, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleados`
--

CREATE TABLE `empleados` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `nombre2` varchar(50) DEFAULT NULL,
  `nombre3` varchar(50) DEFAULT NULL,
  `apellido` varchar(100) NOT NULL,
  `apellido2` varchar(50) DEFAULT NULL,
  `apellido_casada` varchar(50) DEFAULT NULL,
  `dpi` varchar(20) NOT NULL,
  `nit` varchar(20) NOT NULL,
  `correo_electronico` varchar(100) NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `puesto` varchar(100) NOT NULL,
  `departamento_id` int(11) NOT NULL,
  `jornada_id` int(11) NOT NULL,
  `salario_base` decimal(10,2) NOT NULL,
  `id_estado` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `empleados`
--

INSERT INTO `empleados` (`id`, `nombre`, `nombre2`, `nombre3`, `apellido`, `apellido2`, `apellido_casada`, `dpi`, `nit`, `correo_electronico`, `fecha_ingreso`, `puesto`, `departamento_id`, `jornada_id`, `salario_base`, `id_estado`) VALUES
(1, 'Sonnie', 'Israel', NULL, 'Quiroa', 'Morales', NULL, '2997479560101', '55555678', 'sonnie.quiroa@gmail.com', '2023-06-08', 'Presidente', 1, 2, 50000.00, 1),
(2, 'Heber', 'Jaziel', NULL, 'Perla', 'Rivas', NULL, '2093123450101', '98209930', 'sackby09@gmail.com', '2020-01-15', 'Analista', 1, 2, 8000.00, 1),
(3, 'Juan', 'Pedro', NULL, 'Juan', 'Juan', NULL, '1234567891234', '101010-1', 'juan@nominasguate.com', '2025-10-14', 'DBA', 1, 1, 50000.00, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado_empleado`
--

CREATE TABLE `estado_empleado` (
  `id_estado` int(11) NOT NULL,
  `nombre_estado` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estado_empleado`
--

INSERT INTO `estado_empleado` (`id_estado`, `nombre_estado`) VALUES
(1, 'Activo'),
(2, 'Renuncia'),
(3, 'Despido Justificado'),
(4, 'Despido Injustificado'),
(5, 'Jubilado'),
(6, 'Vacaciones'),
(7, 'Suspendido'),
(8, 'Suspendido IGSS');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horas_extras`
--

CREATE TABLE `horas_extras` (
  `id_horasextras` int(11) NOT NULL,
  `id_nomina` int(11) NOT NULL,
  `id_tipohoraextra` int(11) NOT NULL,
  `cantidad` decimal(5,2) NOT NULL,
  `observaciones` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `horas_extras`
--

INSERT INTO `horas_extras` (`id_horasextras`, `id_nomina`, `id_tipohoraextra`, `cantidad`, `observaciones`) VALUES
(1, 1, 1, 3.00, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `jornadas`
--

CREATE TABLE `jornadas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `dias_laborales` tinyint(4) NOT NULL,
  `horas_semanales` tinyint(4) NOT NULL,
  `horas_diarias` decimal(4,2) NOT NULL,
  `horario_entrada` time NOT NULL,
  `horario_salida` time NOT NULL,
  `horas_extras_fijas` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `jornadas`
--

INSERT INTO `jornadas` (`id`, `nombre`, `descripcion`, `dias_laborales`, `horas_semanales`, `horas_diarias`, `horario_entrada`, `horario_salida`, `horas_extras_fijas`) VALUES
(1, 'Diurna', '40h semanales, 6 días, 7h/día, 2h extra fijas', 6, 40, 7.00, '07:00:00', '14:00:00', 2),
(2, 'Completa', '40h semanales, 5 días, 8h/día', 5, 40, 8.00, '08:00:00', '17:00:00', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `liquidaciones`
--

CREATE TABLE `liquidaciones` (
  `id` int(11) NOT NULL,
  `empleado_id` int(11) NOT NULL,
  `fecha_liquidacion` date NOT NULL,
  `tipo_liquidacion` enum('Despido','Renuncia') NOT NULL,
  `dias_laborados` int(11) NOT NULL,
  `valor_dias_laborados` decimal(10,2) DEFAULT NULL,
  `monto_aguinaldo` decimal(10,2) NOT NULL,
  `monto_bono14` decimal(10,2) NOT NULL,
  `dias_vac_acumulados` int(11) NOT NULL,
  `valor_vac_acumuladas` decimal(10,2) NOT NULL,
  `descuentos_totales` decimal(10,2) NOT NULL,
  `total_liquidacion` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `liquidaciones`
--

INSERT INTO `liquidaciones` (`id`, `empleado_id`, `fecha_liquidacion`, `tipo_liquidacion`, `dias_laborados`, `valor_dias_laborados`, `monto_aguinaldo`, `monto_bono14`, `dias_vac_acumulados`, `valor_vac_acumuladas`, `descuentos_totales`, `total_liquidacion`) VALUES
(1, 1, '2025-10-15', 'Renuncia', 861, 0.00, 43698.63, 14657.53, 35, 58333.33, 0.00, 116689.49),
(2, 1, '2025-10-15', 'Renuncia', 861, 0.00, 43698.63, 14657.53, 35, 58333.33, 0.00, 116689.49),
(3, 1, '2025-10-15', 'Renuncia', 861, 0.00, 43698.63, 14657.53, 35, 58333.33, 0.00, 116689.49),
(4, 1, '2025-10-15', 'Despido', 861, 117945.21, 43698.63, 14657.53, 35, 58333.33, 0.00, 234634.70),
(5, 3, '2025-10-15', 'Despido', 2, 273.97, 273.97, 273.97, 0, 0.00, 0.00, 821.91);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nominas`
--

CREATE TABLE `nominas` (
  `id` int(11) NOT NULL,
  `empleado_id` int(11) NOT NULL,
  `id_periodo` int(11) NOT NULL,
  `salario_bruto` decimal(10,2) NOT NULL,
  `descuento_igss` decimal(10,2) NOT NULL,
  `descuento_isr` decimal(10,2) NOT NULL,
  `otros_descuentos` decimal(10,2) DEFAULT 0.00,
  `salario_neto` decimal(10,2) NOT NULL,
  `fecha_pago` date NOT NULL,
  `horas_extras` decimal(5,2) DEFAULT 0.00,
  `pago_horas_extras` decimal(10,2) DEFAULT 0.00,
  `bono14` decimal(10,2) DEFAULT 0.00,
  `salario_a_devengar` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `nominas`
--

INSERT INTO `nominas` (`id`, `empleado_id`, `id_periodo`, `salario_bruto`, `descuento_igss`, `descuento_isr`, `otros_descuentos`, `salario_neto`, `fecha_pago`, `horas_extras`, `pago_horas_extras`, `bono14`, `salario_a_devengar`) VALUES
(1, 1, 2, 12500.00, 0.00, 0.00, 0.00, 12500.00, '2025-10-07', 3.00, 937.50, 0.00, 13437.50),
(2, 2, 2, 2000.00, 0.00, 0.00, 0.00, 2000.00, '2025-10-07', 0.00, 0.00, 0.00, 2000.00),
(9, 1, 1, 50000.00, 2415.00, 2500.00, 625.00, 44460.00, '2025-10-31', 0.00, 0.00, 0.00, 44460.00),
(10, 2, 1, 8000.00, 386.40, 400.00, 0.00, 7213.60, '2025-10-31', 0.00, 0.00, 0.00, 7213.60),
(11, 3, 1, 50000.00, 2415.00, 2500.00, 1666.67, 43418.33, '2025-10-31', 0.00, 0.00, 0.00, 43418.33),
(14, 3, 2, 12500.00, 0.00, 0.00, 0.00, 12500.00, '2025-10-07', 0.00, 0.00, 0.00, 12500.00),
(33, 1, 3, 25000.00, 0.00, 0.00, 625.00, 24375.00, '2025-10-15', 0.00, 0.00, 0.00, 24375.00),
(34, 2, 3, 4000.00, 0.00, 0.00, 0.00, 4000.00, '2025-10-15', 0.00, 0.00, 0.00, 4000.00),
(35, 3, 3, 25000.00, 0.00, 0.00, 1666.67, 23333.33, '2025-10-15', 0.00, 0.00, 0.00, 23333.33);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_anuales`
--

CREATE TABLE `pagos_anuales` (
  `id` int(11) NOT NULL,
  `empleado_id` int(11) NOT NULL,
  `tipo_pago` enum('aguinaldo','bono14') NOT NULL,
  `fecha_pago` date NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `anio` int(11) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `periodos`
--

CREATE TABLE `periodos` (
  `id_periodo` int(11) NOT NULL,
  `tipo_periodo` varchar(50) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `periodos`
--

INSERT INTO `periodos` (`id_periodo`, `tipo_periodo`, `fecha_inicio`, `fecha_fin`) VALUES
(1, 'Mensual Octubre', '2025-10-01', '2025-10-31'),
(2, 'Semanal', '2025-10-01', '2025-10-07'),
(3, 'Quincenal', '2025-10-01', '2025-10-15'),
(4, 'Bono 14', '2025-07-01', '2025-07-15'),
(5, 'Aguinaldo', '2025-12-01', '2025-12-15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_horas_extras`
--

CREATE TABLE `tipos_horas_extras` (
  `id_tipohoraextra` int(11) NOT NULL,
  `descripcion` varchar(50) NOT NULL,
  `multiplicador` decimal(3,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tipos_horas_extras`
--

INSERT INTO `tipos_horas_extras` (`id_tipohoraextra`, `descripcion`, `multiplicador`) VALUES
(1, 'Hora extra al 1.5', 1.50),
(2, 'Hora extra al doble', 2.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('admin','usuario') NOT NULL DEFAULT 'usuario',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password_hash`, `rol`, `creado_en`) VALUES
(2, 'admin', '$2y$10$WVjLMnSbL6mU2b1oWFuZWOz0/nU8LZ4yUKVfCI5XFkrPxXzwFuGmK', 'admin', '2025-10-15 04:13:05'),
(3, 'Heber', '$2y$10$.jJ.QaIwNbrRwpJ3oOLxPeyuh6UChvE33dRkjzqOBQ/1Y5i40q0IW', 'usuario', '2025-10-15 15:27:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vacaciones`
--

CREATE TABLE `vacaciones` (
  `id` int(11) NOT NULL,
  `empleado_id` int(11) NOT NULL,
  `fecha_registro` date NOT NULL,
  `tipo` enum('acumulado','tomado') NOT NULL,
  `cantidad_dias` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `vacaciones`
--

INSERT INTO `vacaciones` (`id`, `empleado_id`, `fecha_registro`, `tipo`, `cantidad_dias`) VALUES
(1, 1, '2025-10-14', 'acumulado', 1.25),
(2, 1, '2025-10-14', 'acumulado', 1.25),
(3, 1, '2025-10-14', 'acumulado', 1.25),
(4, 1, '2025-10-14', 'acumulado', 1.25),
(5, 1, '2025-10-14', 'acumulado', 1.25),
(6, 1, '2025-10-14', 'acumulado', 1.25),
(7, 1, '2025-10-14', 'acumulado', 1.25),
(8, 1, '2025-10-14', 'acumulado', 1.25),
(9, 1, '2025-10-14', 'acumulado', 1.25),
(10, 1, '2025-10-14', 'acumulado', 1.25),
(11, 1, '2025-10-14', 'acumulado', 1.25),
(12, 1, '2025-10-14', 'acumulado', 1.25),
(13, 1, '2025-10-14', 'acumulado', 1.25),
(14, 1, '2025-10-14', 'acumulado', 1.25),
(15, 1, '2025-10-14', 'acumulado', 1.25),
(16, 1, '2025-10-14', 'acumulado', 1.25),
(17, 1, '2025-10-14', 'acumulado', 1.25),
(18, 1, '2025-10-14', 'acumulado', 1.25),
(19, 1, '2025-10-14', 'acumulado', 1.25),
(20, 1, '2025-10-14', 'acumulado', 1.25),
(21, 1, '2025-10-14', 'acumulado', 1.25),
(22, 1, '2025-10-14', 'acumulado', 1.25),
(23, 1, '2025-10-14', 'acumulado', 1.25),
(24, 1, '2025-10-14', 'acumulado', 1.25),
(25, 1, '2025-10-14', 'acumulado', 1.25),
(26, 1, '2025-10-14', 'acumulado', 1.25),
(27, 1, '2025-10-14', 'acumulado', 1.25),
(28, 1, '2025-10-14', 'acumulado', 1.25),
(29, 2, '2025-10-14', 'acumulado', 1.25),
(30, 2, '2025-10-14', 'acumulado', 1.25),
(31, 2, '2025-10-14', 'acumulado', 1.25),
(32, 2, '2025-10-14', 'acumulado', 1.25),
(33, 2, '2025-10-14', 'acumulado', 1.25),
(34, 2, '2025-10-14', 'acumulado', 1.25),
(35, 2, '2025-10-14', 'acumulado', 1.25),
(36, 2, '2025-10-14', 'acumulado', 1.25),
(37, 2, '2025-10-14', 'acumulado', 1.25),
(38, 2, '2025-10-14', 'acumulado', 1.25),
(39, 2, '2025-10-14', 'acumulado', 1.25),
(40, 2, '2025-10-14', 'acumulado', 1.25),
(41, 2, '2025-10-14', 'acumulado', 1.25),
(42, 2, '2025-10-14', 'acumulado', 1.25),
(43, 2, '2025-10-14', 'acumulado', 1.25),
(44, 2, '2025-10-14', 'acumulado', 1.25),
(45, 2, '2025-10-14', 'acumulado', 1.25),
(46, 2, '2025-10-14', 'acumulado', 1.25),
(47, 2, '2025-10-14', 'acumulado', 1.25),
(48, 2, '2025-10-14', 'acumulado', 1.25),
(49, 2, '2025-10-14', 'acumulado', 1.25),
(50, 2, '2025-10-14', 'acumulado', 1.25),
(51, 2, '2025-10-14', 'acumulado', 1.25),
(52, 2, '2025-10-14', 'acumulado', 1.25),
(53, 2, '2025-10-14', 'acumulado', 1.25),
(54, 2, '2025-10-14', 'acumulado', 1.25),
(55, 2, '2025-10-14', 'acumulado', 1.25),
(56, 2, '2025-10-14', 'acumulado', 1.25),
(57, 2, '2025-10-14', 'acumulado', 1.25),
(58, 2, '2025-10-14', 'acumulado', 1.25),
(59, 2, '2025-10-14', 'acumulado', 1.25),
(60, 2, '2025-10-14', 'acumulado', 1.25),
(61, 2, '2025-10-14', 'acumulado', 1.25),
(62, 2, '2025-10-14', 'acumulado', 1.25),
(63, 2, '2025-10-14', 'acumulado', 1.25),
(64, 2, '2025-10-14', 'acumulado', 1.25),
(65, 2, '2025-10-14', 'acumulado', 1.25),
(66, 2, '2025-10-14', 'acumulado', 1.25),
(67, 2, '2025-10-14', 'acumulado', 1.25),
(68, 2, '2025-10-14', 'acumulado', 1.25),
(69, 2, '2025-10-14', 'acumulado', 1.25),
(70, 2, '2025-10-14', 'acumulado', 1.25),
(71, 2, '2025-10-14', 'acumulado', 1.25),
(72, 2, '2025-10-14', 'acumulado', 1.25),
(73, 2, '2025-10-14', 'acumulado', 1.25),
(74, 2, '2025-10-14', 'acumulado', 1.25),
(75, 2, '2025-10-14', 'acumulado', 1.25),
(76, 2, '2025-10-14', 'acumulado', 1.25),
(77, 2, '2025-10-14', 'acumulado', 1.25),
(78, 2, '2025-10-14', 'acumulado', 1.25),
(79, 2, '2025-10-14', 'acumulado', 1.25),
(80, 2, '2025-10-14', 'acumulado', 1.25),
(81, 2, '2025-10-14', 'acumulado', 1.25),
(82, 2, '2025-10-14', 'acumulado', 1.25),
(83, 2, '2025-10-14', 'acumulado', 1.25),
(84, 2, '2025-10-14', 'acumulado', 1.25),
(85, 2, '2025-10-14', 'acumulado', 1.25),
(86, 2, '2025-10-14', 'acumulado', 1.25),
(87, 2, '2025-10-14', 'acumulado', 1.25),
(88, 2, '2025-10-14', 'acumulado', 1.25),
(89, 2, '2025-10-14', 'acumulado', 1.25),
(90, 2, '2025-10-14', 'acumulado', 1.25),
(91, 2, '2025-10-14', 'acumulado', 1.25),
(92, 2, '2025-10-14', 'acumulado', 1.25),
(93, 2, '2025-10-14', 'acumulado', 1.25),
(94, 2, '2025-10-14', 'acumulado', 1.25),
(95, 2, '2025-10-14', 'acumulado', 1.25),
(96, 2, '2025-10-14', 'acumulado', 1.25),
(97, 1, '2025-11-01', 'acumulado', 15.00),
(98, 2, '2025-10-15', 'tomado', 15.00),
(99, 1, '2025-10-15', 'tomado', 30.00);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_estado_vacaciones`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_estado_vacaciones` (
`empleado_id` int(11)
,`nombre_completo` varchar(303)
,`fecha_ingreso` date
,`dias_acumulados` decimal(27,2)
,`dias_tomados` decimal(27,2)
,`dias_disponibles` decimal(28,2)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_nomina`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_nomina` (
`empleado_id` int(11)
,`nombre` varchar(100)
,`nombre2` varchar(50)
,`apellido` varchar(100)
,`apellido2` varchar(50)
,`salario_base` decimal(10,2)
,`fecha_inicio` date
,`fecha_fin` date
,`descuento_igss` decimal(10,2)
,`descuento_isr` decimal(10,2)
,`salario_neto` decimal(10,2)
,`horas_extras` decimal(5,2)
,`pago_horas_extras` decimal(10,2)
,`salario_a_devengar` decimal(10,2)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_nomina_completa`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_nomina_completa` (
`empleado_id` int(11)
,`nombre` varchar(100)
,`nombre2` varchar(50)
,`apellido` varchar(100)
,`apellido2` varchar(50)
,`salario_base` decimal(10,2)
,`fecha_inicio` date
,`fecha_fin` date
,`descuento_igss` decimal(10,2)
,`descuento_isr` decimal(10,2)
,`salario_neto` decimal(10,2)
,`horas_extras` decimal(5,2)
,`pago_horas_extras` decimal(10,2)
,`salario_a_devengar` decimal(10,2)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_estado_vacaciones`
--
DROP TABLE IF EXISTS `vista_estado_vacaciones`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_estado_vacaciones`  AS SELECT `e`.`id` AS `empleado_id`, concat(`e`.`nombre`,' ',`e`.`nombre2`,' ',`e`.`apellido`,' ',`e`.`apellido2`) AS `nombre_completo`, `e`.`fecha_ingreso` AS `fecha_ingreso`, round(sum(case when `v`.`tipo` = 'acumulado' then `v`.`cantidad_dias` else 0 end),2) AS `dias_acumulados`, round(sum(case when `v`.`tipo` = 'tomado' then `v`.`cantidad_dias` else 0 end),2) AS `dias_tomados`, round(sum(case when `v`.`tipo` = 'acumulado' then `v`.`cantidad_dias` else 0 end) - sum(case when `v`.`tipo` = 'tomado' then `v`.`cantidad_dias` else 0 end),2) AS `dias_disponibles` FROM (`empleados` `e` left join `vacaciones` `v` on(`v`.`empleado_id` = `e`.`id`)) GROUP BY `e`.`id`, `e`.`nombre`, `e`.`nombre2`, `e`.`apellido`, `e`.`apellido2`, `e`.`fecha_ingreso` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_nomina`
--
DROP TABLE IF EXISTS `vista_nomina`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_nomina`  AS SELECT `e`.`id` AS `empleado_id`, `e`.`nombre` AS `nombre`, `e`.`nombre2` AS `nombre2`, `e`.`apellido` AS `apellido`, `e`.`apellido2` AS `apellido2`, `e`.`salario_base` AS `salario_base`, `p`.`fecha_inicio` AS `fecha_inicio`, `p`.`fecha_fin` AS `fecha_fin`, `n`.`descuento_igss` AS `descuento_igss`, `n`.`descuento_isr` AS `descuento_isr`, `n`.`salario_neto` AS `salario_neto`, `n`.`horas_extras` AS `horas_extras`, `n`.`pago_horas_extras` AS `pago_horas_extras`, `n`.`salario_a_devengar` AS `salario_a_devengar` FROM ((`empleados` `e` left join `nominas` `n` on(`n`.`empleado_id` = `e`.`id`)) left join `periodos` `p` on(`p`.`id_periodo` = `n`.`id_periodo`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_nomina_completa`
--
DROP TABLE IF EXISTS `vista_nomina_completa`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_nomina_completa`  AS SELECT `vista_nomina`.`empleado_id` AS `empleado_id`, `vista_nomina`.`nombre` AS `nombre`, `vista_nomina`.`nombre2` AS `nombre2`, `vista_nomina`.`apellido` AS `apellido`, `vista_nomina`.`apellido2` AS `apellido2`, `vista_nomina`.`salario_base` AS `salario_base`, `vista_nomina`.`fecha_inicio` AS `fecha_inicio`, `vista_nomina`.`fecha_fin` AS `fecha_fin`, `vista_nomina`.`descuento_igss` AS `descuento_igss`, `vista_nomina`.`descuento_isr` AS `descuento_isr`, `vista_nomina`.`salario_neto` AS `salario_neto`, `vista_nomina`.`horas_extras` AS `horas_extras`, `vista_nomina`.`pago_horas_extras` AS `pago_horas_extras`, `vista_nomina`.`salario_a_devengar` AS `salario_a_devengar` FROM `vista_nomina` ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `app_settings`
--
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`clave`);

--
-- Indices de la tabla `asistencia`
--
ALTER TABLE `asistencia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_asist_emp` (`empleado_id`);

--
-- Indices de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `descuentos_puntuales`
--
ALTER TABLE `descuentos_puntuales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_desc_emp` (`empleado_id`);

--
-- Indices de la tabla `empleados`
--
ALTER TABLE `empleados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dpi` (`dpi`),
  ADD KEY `fk_emp_depto` (`departamento_id`),
  ADD KEY `fk_emp_jornada` (`jornada_id`),
  ADD KEY `fk_emp_estado` (`id_estado`);

--
-- Indices de la tabla `estado_empleado`
--
ALTER TABLE `estado_empleado`
  ADD PRIMARY KEY (`id_estado`);

--
-- Indices de la tabla `horas_extras`
--
ALTER TABLE `horas_extras`
  ADD PRIMARY KEY (`id_horasextras`),
  ADD KEY `fk_he_nomina` (`id_nomina`),
  ADD KEY `fk_he_tipo` (`id_tipohoraextra`);

--
-- Indices de la tabla `jornadas`
--
ALTER TABLE `jornadas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `liquidaciones`
--
ALTER TABLE `liquidaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_liq_emp` (`empleado_id`);

--
-- Indices de la tabla `nominas`
--
ALTER TABLE `nominas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_nomina_periodo_empleado` (`id_periodo`,`empleado_id`),
  ADD UNIQUE KEY `uq_nomina_emp_periodo` (`empleado_id`,`id_periodo`);

--
-- Indices de la tabla `pagos_anuales`
--
ALTER TABLE `pagos_anuales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pa_emp` (`empleado_id`),
  ADD KEY `idx_pa_anio` (`anio`),
  ADD KEY `idx_pa_tipo` (`tipo_pago`);

--
-- Indices de la tabla `periodos`
--
ALTER TABLE `periodos`
  ADD PRIMARY KEY (`id_periodo`);

--
-- Indices de la tabla `tipos_horas_extras`
--
ALTER TABLE `tipos_horas_extras`
  ADD PRIMARY KEY (`id_tipohoraextra`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indices de la tabla `vacaciones`
--
ALTER TABLE `vacaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_vac_emp` (`empleado_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asistencia`
--
ALTER TABLE `asistencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `descuentos_puntuales`
--
ALTER TABLE `descuentos_puntuales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `empleados`
--
ALTER TABLE `empleados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `estado_empleado`
--
ALTER TABLE `estado_empleado`
  MODIFY `id_estado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `horas_extras`
--
ALTER TABLE `horas_extras`
  MODIFY `id_horasextras` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `jornadas`
--
ALTER TABLE `jornadas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `liquidaciones`
--
ALTER TABLE `liquidaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `nominas`
--
ALTER TABLE `nominas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT de la tabla `pagos_anuales`
--
ALTER TABLE `pagos_anuales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `periodos`
--
ALTER TABLE `periodos`
  MODIFY `id_periodo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `tipos_horas_extras`
--
ALTER TABLE `tipos_horas_extras`
  MODIFY `id_tipohoraextra` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `vacaciones`
--
ALTER TABLE `vacaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asistencia`
--
ALTER TABLE `asistencia`
  ADD CONSTRAINT `fk_asist_emp` FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`);

--
-- Filtros para la tabla `descuentos_puntuales`
--
ALTER TABLE `descuentos_puntuales`
  ADD CONSTRAINT `fk_desc_emp` FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`);

--
-- Filtros para la tabla `empleados`
--
ALTER TABLE `empleados`
  ADD CONSTRAINT `fk_emp_depto` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`),
  ADD CONSTRAINT `fk_emp_estado` FOREIGN KEY (`id_estado`) REFERENCES `estado_empleado` (`id_estado`),
  ADD CONSTRAINT `fk_emp_jornada` FOREIGN KEY (`jornada_id`) REFERENCES `jornadas` (`id`);

--
-- Filtros para la tabla `horas_extras`
--
ALTER TABLE `horas_extras`
  ADD CONSTRAINT `fk_he_nomina` FOREIGN KEY (`id_nomina`) REFERENCES `nominas` (`id`),
  ADD CONSTRAINT `fk_he_tipo` FOREIGN KEY (`id_tipohoraextra`) REFERENCES `tipos_horas_extras` (`id_tipohoraextra`);

--
-- Filtros para la tabla `liquidaciones`
--
ALTER TABLE `liquidaciones`
  ADD CONSTRAINT `fk_liq_emp` FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`);

--
-- Filtros para la tabla `nominas`
--
ALTER TABLE `nominas`
  ADD CONSTRAINT `fk_nom_emp` FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`),
  ADD CONSTRAINT `fk_nom_periodo` FOREIGN KEY (`id_periodo`) REFERENCES `periodos` (`id_periodo`);

--
-- Filtros para la tabla `pagos_anuales`
--
ALTER TABLE `pagos_anuales`
  ADD CONSTRAINT `fk_pa_emp` FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`);

--
-- Filtros para la tabla `vacaciones`
--
ALTER TABLE `vacaciones`
  ADD CONSTRAINT `fk_vac_emp` FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`);

DELIMITER $$
--
-- Eventos
--
CREATE DEFINER=`root`@`localhost` EVENT `ev_acumular_vacaciones_diario` ON SCHEDULE EVERY 1 DAY STARTS '2025-10-14 15:22:01' ON COMPLETION PRESERVE ENABLE DO CALL sp_acumular_vacaciones_mensual()$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
