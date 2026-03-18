<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CU001_logs extends CI_Controller
{

	public function index()
	{
		return;

		$inicio = new DateTime('2022-11-23');
		$fin = new DateTime('2024-02-26');

		$fin = $fin->modify('+1 day');

		$intervalo = DateInterval::createFromDateString('1 day');
		$periodo = new DatePeriod($inicio, $intervalo, $fin);

		foreach ($periodo as $dt) {
			$diaSemana = $dt->format("w"); //0:Domingo; 6: Sábado

			echo $dt->format("Y-m-d") . ' - ' . $diaSemana . ' - ';

			if ($diaSemana >= 1 && $diaSemana <= 5) {
				if (rand(1, 3) == 1) {
					$fecha = $dt->format("Y-m-d") . ' ' . $this->getTimeLog();
					echo "Trabaja: " . $fecha;
					$this->insertAcceso(array("USR_CO_ID" => 1, "PER_CO_ID" => 1, "LOG_DT_DATE" => $fecha));

				} else {
					echo "Libra";
				}
			} else {
				if (rand(1, 10) == 1) {
					$fecha = $dt->format("Y-m-d") . ' ' . $this->getTimeLog();
					echo "Trabaja: " . $fecha;
					$this->insertAcceso(array("USR_CO_ID" => 1, "PER_CO_ID" => 1, "LOG_DT_DATE" => $fecha));
				} else {
					echo "Libra";
				}
			}
			echo "<br>";
		}
	}

	private function getTimeLog()
	{
		$hour = rand(10, 16);
		$min = rand(1, 60);
		$sec = rand(1, 60);

		return "{$hour}:{$min}:{$sec}";
	}

	private function insertAcceso($data)
	{
		$this->db->insert('t_log_accesos', $data);
		return $this->db->insert_id();
	}

}

/* End of file controllername.php */
/* Location: ./application/controllers/controllername.php */