<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CA001_panelControl extends CI_Controller {

	function __construct(){
		parent::__construct();
	}

	public function index()
	{
		if($this->session->userdata('logged')==TRUE){
			$dashboard = $this->buildDashboardData();
			$data = array(
				"content"	=> "admin/vA001_panelControl.php"
				,"titulo" 	=> "Dashboard"
				,"javascriptMenu"=>"$('#menuDashBoard').addClass('menu-item-active');"
				,"dashboard" => $dashboard
				
			);
			$this->load->view('layout_admin',$data);
		}else{
			redirect('index.php/cerbero','refresh');
		}
	}

	private function buildDashboardData()
	{
		$hasPagoSolicitadoDate = $this->db->field_exists('SOL_DT_PAGO_SOLICITADO', 't_sol_solicitudes');
		$hasPagoRealizadoDate = $this->db->field_exists('SOL_DT_PAGO_REALIZADO', 't_sol_solicitudes');
		$hasImporte = $this->db->field_exists('SOL_NM_IMPORTE', 't_sol_solicitudes');
		$hasRedsysTable = $this->db->table_exists('t_rpi_redsys_intentos');

		$baseWhere = "s.SOL_BL_DELETE = 0";

		$totalSolicitudes = $this->singleValue("SELECT COUNT(*) FROM t_sol_solicitudes s WHERE {$baseWhere}", 0);
		$leadsPendientes = $this->singleValue(
			"SELECT COUNT(*) FROM t_sol_solicitudes s WHERE {$baseWhere} AND s.ESO_CO_ID = 1",
			0
		);
		$solicitudesMesActual = $this->singleValue(
			"SELECT COUNT(*) FROM t_sol_solicitudes s WHERE {$baseWhere} AND DATE_FORMAT(s.SOL_DT_CREATE, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')",
			0
		);
		$solicitudesEstado2 = $this->singleValue(
			"SELECT COUNT(*) FROM t_sol_solicitudes s WHERE {$baseWhere} AND s.ESO_CO_ID = 2",
			0
		);
		$solicitudesPagadas = $this->singleValue(
			"SELECT COUNT(*) FROM t_sol_solicitudes s WHERE {$baseWhere} AND s.ESO_CO_ID >= 3",
			0
		);

		$converRate = 0;
		if((int)$totalSolicitudes > 0){
			$converRate = round(((int)$solicitudesPagadas / (int)$totalSolicitudes) * 100, 2);
		}

		$hoursLeadToPagoRequest = null;
		if($hasPagoSolicitadoDate){
			$hoursLeadToPagoRequest = $this->singleValue(
				"SELECT ROUND(AVG(TIMESTAMPDIFF(MINUTE, s.SOL_DT_CREATE, s.SOL_DT_PAGO_SOLICITADO)) / 60, 2)\n"
				. "FROM t_sol_solicitudes s\n"
				. "WHERE {$baseWhere} AND s.ESO_CO_ID >= 2 AND s.SOL_DT_PAGO_SOLICITADO IS NOT NULL",
				null
			);
		}

		$hoursEstado2To3 = null;
		if($hasRedsysTable){
			$hoursEstado2To3 = $this->singleValue(
				"SELECT ROUND(AVG(TIMESTAMPDIFF(MINUTE, p.init_dt, p.ok_dt)) / 60, 2)\n"
				. "FROM (\n"
				. "  SELECT r.SOL_CO_ID,\n"
				. "         MIN(CASE WHEN UPPER(r.RPI_DS_ESTADO) = 'INIT' THEN r.RPI_DT_CREATE END) AS init_dt,\n"
				. "         MIN(CASE WHEN UPPER(r.RPI_DS_ESTADO) = 'OK' THEN r.RPI_DT_CREATE END) AS ok_dt\n"
				. "  FROM t_rpi_redsys_intentos r\n"
				. "  WHERE r.RPI_BL_DELETE = 0 AND r.SOL_CO_ID IS NOT NULL\n"
				. "  GROUP BY r.SOL_CO_ID\n"
				. ") p\n"
				. "WHERE p.init_dt IS NOT NULL AND p.ok_dt IS NOT NULL AND p.ok_dt >= p.init_dt",
				null
			);
		}else if($hasPagoSolicitadoDate && $hasPagoRealizadoDate){
			$hoursEstado2To3 = $this->singleValue(
				"SELECT ROUND(AVG(TIMESTAMPDIFF(MINUTE, s.SOL_DT_PAGO_SOLICITADO, s.SOL_DT_PAGO_REALIZADO)) / 60, 2)\n"
				. "FROM t_sol_solicitudes s\n"
				. "WHERE {$baseWhere} AND s.ESO_CO_ID >= 3 AND s.SOL_DT_PAGO_SOLICITADO IS NOT NULL AND s.SOL_DT_PAGO_REALIZADO IS NOT NULL",
				null
			);
		}

		$hoursLeadToPagado = null;
		if($hasRedsysTable){
			$hoursLeadToPagado = $this->singleValue(
				"SELECT ROUND(AVG(TIMESTAMPDIFF(MINUTE, s.SOL_DT_CREATE, p.ok_dt)) / 60, 2)\n"
				. "FROM t_sol_solicitudes s\n"
				. "INNER JOIN (\n"
				. "  SELECT r.SOL_CO_ID, MIN(CASE WHEN UPPER(r.RPI_DS_ESTADO) = 'OK' THEN r.RPI_DT_CREATE END) AS ok_dt\n"
				. "  FROM t_rpi_redsys_intentos r\n"
				. "  WHERE r.RPI_BL_DELETE = 0 AND r.SOL_CO_ID IS NOT NULL\n"
				. "  GROUP BY r.SOL_CO_ID\n"
				. ") p ON p.SOL_CO_ID = s.SOL_CO_ID\n"
				. "WHERE {$baseWhere} AND p.ok_dt IS NOT NULL AND p.ok_dt >= s.SOL_DT_CREATE",
				null
			);
		}else if($hasPagoRealizadoDate){
			$hoursLeadToPagado = $this->singleValue(
				"SELECT ROUND(AVG(TIMESTAMPDIFF(MINUTE, s.SOL_DT_CREATE, s.SOL_DT_PAGO_REALIZADO)) / 60, 2)\n"
				. "FROM t_sol_solicitudes s\n"
				. "WHERE {$baseWhere} AND s.ESO_CO_ID >= 3 AND s.SOL_DT_PAGO_REALIZADO IS NOT NULL",
				null
			);
		}

		$ingresoMensualMedio = 0;
		if($hasImporte){
			$fechaIngresoCampo = $hasPagoRealizadoDate ? 's.SOL_DT_PAGO_REALIZADO' : 's.SOL_DT_CREATE';
			$ingresoMensualMedio = $this->singleValue(
				"SELECT ROUND(AVG(mes_total), 2)\n"
				. "FROM (\n"
				. "  SELECT DATE_FORMAT({$fechaIngresoCampo}, '%Y-%m') AS ym, SUM(COALESCE(s.SOL_NM_IMPORTE, 0)) AS mes_total\n"
				. "  FROM t_sol_solicitudes s\n"
				. "  WHERE {$baseWhere} AND s.ESO_CO_ID >= 3 AND {$fechaIngresoCampo} IS NOT NULL\n"
				. "  GROUP BY DATE_FORMAT({$fechaIngresoCampo}, '%Y-%m')\n"
				. ") t",
				0
			);
		}

		$paymentStats = $this->buildPaymentStats();
		$estadoDistribution = $this->buildEstadoDistribution();
		$evolution = $this->buildMonthlyEvolution($hasImporte, $hasPagoRealizadoDate);

		return array(
			'kpis' => array(
				'total_solicitudes' => (int)$totalSolicitudes,
				'leads_pendientes' => (int)$leadsPendientes,
				'solicitudes_mes_actual' => (int)$solicitudesMesActual,
				'solicitudes_estado_2' => (int)$solicitudesEstado2,
				'solicitudes_pagadas' => (int)$solicitudesPagadas,
				'tasa_conversion_pagada' => (float)$converRate,
				'tiempo_medio_lead_a_estado2_horas' => $hoursLeadToPagoRequest !== null ? (float)$hoursLeadToPagoRequest : null,
				'tiempo_medio_estado2_a_estado3_horas' => $hoursEstado2To3 !== null ? (float)$hoursEstado2To3 : null,
				'tiempo_medio_lead_a_pagado_horas' => $hoursLeadToPagado !== null ? (float)$hoursLeadToPagado : null,
				'ingreso_mensual_medio' => (float)$ingresoMensualMedio,
			),
			'pagos' => $paymentStats,
			'estados' => $estadoDistribution,
			'evolucion' => $evolution,
			'sugerencias' => array(
				'Tiempo medio de conversion lead -> estado 2 (solicitud de pago).',
				'Tiempo medio de conversion estado 2 -> estado 3 (pagado).',
				'Tasa de conversion a solicitud pagada (estado >= 3).',
				'Ingreso medio mensual basado en solicitudes pagadas.',
				'Evolucion mensual de volumen de solicitudes e ingresos.',
				'Distribucion por estados para detectar cuellos de botella.'
			),
			'supuestos' => array(
				'Estado 2 equivale a solicitud esperando pago.',
				'Estado >= 3 se considera solicitud con pago realizado.',
				'Tiempos solicitado -> pagado y lead -> pagado se calculan desde intentos Redsys (INIT/OK por SOL_CO_ID) cuando existe la tabla.',
				'Si no existe SOL_DT_PAGO_SOLICITADO o SOL_DT_PAGO_REALIZADO se usan fallbacks seguros.'
			)
		);
	}

	private function buildPaymentStats()
	{
		if(!$this->db->table_exists('t_rpi_redsys_intentos')){
			return array(
				'has_data' => false,
				'ok' => 0,
				'ko' => 0,
				'total' => 0,
				'ratio_ok' => 0,
			);
		}

		$ok = $this->singleValue(
			"SELECT COUNT(*) FROM t_rpi_redsys_intentos r WHERE r.RPI_BL_DELETE = 0 AND UPPER(r.RPI_DS_ESTADO) = 'OK'",
			0
		);
		$ko = $this->singleValue(
			"SELECT COUNT(*) FROM t_rpi_redsys_intentos r WHERE r.RPI_BL_DELETE = 0 AND UPPER(r.RPI_DS_ESTADO) = 'KO'",
			0
		);
		$total = $this->singleValue(
			"SELECT COUNT(*) FROM t_rpi_redsys_intentos r WHERE r.RPI_BL_DELETE = 0",
			0
		);

		$ratioOk = 0;
		if((int)$total > 0){
			$ratioOk = round(((int)$ok / (int)$total) * 100, 2);
		}

		return array(
			'has_data' => true,
			'ok' => (int)$ok,
			'ko' => (int)$ko,
			'total' => (int)$total,
			'ratio_ok' => (float)$ratioOk,
		);
	}

	private function buildEstadoDistribution()
	{
		$sql = "SELECT s.ESO_CO_ID, COALESCE(eso.ESO_DS_NAME, CONCAT('Estado ', s.ESO_CO_ID)) AS estado_nombre, COUNT(*) AS total\n"
			. "FROM t_sol_solicitudes s\n"
			. "LEFT JOIN m_eso_estadosolicitud eso ON eso.ESO_CO_ID = s.ESO_CO_ID\n"
			. "WHERE s.SOL_BL_DELETE = 0\n"
			. "GROUP BY s.ESO_CO_ID, estado_nombre\n"
			. "ORDER BY total DESC";

		$query = $this->db->query($sql);
		$rows = array();
		$max = 0;
		if($query !== false && $query->num_rows() > 0){
			foreach($query->result() as $r){
				$value = (int)$r->total;
				if($value > $max){
					$max = $value;
				}
				$rows[] = array(
					'estado_id' => (int)$r->ESO_CO_ID,
					'estado_nombre' => (string)$r->estado_nombre,
					'total' => $value,
					'percent' => 0,
				);
			}
		}

		if($max > 0){
			foreach($rows as $idx => $row){
				$rows[$idx]['percent'] = round(($row['total'] / $max) * 100, 1);
			}
		}

		return $rows;
	}

	private function buildMonthlyEvolution($hasImporte, $hasPagoRealizadoDate)
	{
		$labels = array();
		$solicitudes = array();
		$ingresos = array();

		$labelMap = array();
		for($i = 5; $i >= 0; $i--){
			$key = date('Y-m', strtotime('-' . $i . ' month'));
			$labels[] = $key;
			$solicitudes[] = 0;
			$ingresos[] = 0.0;
			$labelMap[$key] = count($labels) - 1;
		}

		$sqlSolicitudes = "SELECT DATE_FORMAT(s.SOL_DT_CREATE, '%Y-%m') AS ym, COUNT(*) AS total\n"
			. "FROM t_sol_solicitudes s\n"
			. "WHERE s.SOL_BL_DELETE = 0 AND s.SOL_DT_CREATE >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)\n"
			. "GROUP BY DATE_FORMAT(s.SOL_DT_CREATE, '%Y-%m')";
		$qSolicitudes = $this->db->query($sqlSolicitudes);
		if($qSolicitudes !== false && $qSolicitudes->num_rows() > 0){
			foreach($qSolicitudes->result() as $row){
				$key = (string)$row->ym;
				if(isset($labelMap[$key])){
					$solicitudes[$labelMap[$key]] = (int)$row->total;
				}
			}
		}

		if($hasImporte){
			$fechaIngreso = $hasPagoRealizadoDate ? 's.SOL_DT_PAGO_REALIZADO' : 's.SOL_DT_CREATE';
			$sqlIngresos = "SELECT DATE_FORMAT({$fechaIngreso}, '%Y-%m') AS ym, SUM(COALESCE(s.SOL_NM_IMPORTE, 0)) AS total\n"
				. "FROM t_sol_solicitudes s\n"
				. "WHERE s.SOL_BL_DELETE = 0 AND s.ESO_CO_ID >= 3 AND {$fechaIngreso} IS NOT NULL\n"
				. "  AND {$fechaIngreso} >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)\n"
				. "GROUP BY DATE_FORMAT({$fechaIngreso}, '%Y-%m')";
			$qIngresos = $this->db->query($sqlIngresos);
			if($qIngresos !== false && $qIngresos->num_rows() > 0){
				foreach($qIngresos->result() as $row){
					$key = (string)$row->ym;
					if(isset($labelMap[$key])){
						$ingresos[$labelMap[$key]] = round((float)$row->total, 2);
					}
				}
			}
		}

		return array(
			'labels' => $labels,
			'solicitudes' => $solicitudes,
			'ingresos' => $ingresos,
		);
	}

	private function singleValue($sql, $default = 0)
	{
		$query = $this->db->query($sql);
		if($query === false || $query->num_rows() === 0){
			return $default;
		}

		$row = $query->row();
		if(!$row){
			return $default;
		}

		$values = array_values(get_object_vars($row));
		if(!isset($values[0])){
			return $default;
		}

		return $values[0];
	}

	

}

/* End of file cA01_panelControl.php */
/* Location: ./application/controllers/admin/cA01_panelControl.php */ 
?>