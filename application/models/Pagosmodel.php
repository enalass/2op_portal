<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pagosmodel extends CI_Model {

	private static $TABLE = 't_rpi_redsys_intentos';
	private $tableChecked = false;
	private $tableExists = false;

	private function ensureTableChecked(){
		if($this->tableChecked){
			return;
		}

		$this->tableExists = $this->db->table_exists(self::$TABLE);
		$this->tableChecked = true;
	}

	public function canList(){
		$this->ensureTableChecked();
		return $this->tableExists;
	}

	public function getList(){
		if(!$this->canList()){
			return false;
		}

		$this->db->select(
			'rpi.RPI_CO_ID, rpi.SOL_CO_ID, rpi.USR_CO_ID, rpi.RPI_DS_CANAL, rpi.RPI_DS_ESTADO, rpi.RPI_DS_ORDER, rpi.RPI_DS_RESPONSE_CODE, rpi.RPI_DT_CREATE, ' .
			'usr.USR_DS_NOMBRE, usr.USR_DS_APELLIDOS, usr.USR_DS_MAIL, ' .
			'sol.SOL_DS_NOMBRE, sol.ESO_CO_ID, eso.ESO_DS_NAME'
		);
		$this->db->from(self::$TABLE . ' rpi');
		$this->db->join('t_usr_usuarios usr', 'usr.USR_CO_ID = rpi.USR_CO_ID', 'left');
		$this->db->join('t_sol_solicitudes sol', 'sol.SOL_CO_ID = rpi.SOL_CO_ID', 'left');
		$this->db->join('m_eso_estadosolicitud eso', 'eso.ESO_CO_ID = sol.ESO_CO_ID', 'left');
		$this->db->where('rpi.RPI_BL_DELETE', 0);
		$this->db->order_by('rpi.RPI_DT_CREATE', 'DESC');

		$data = $this->db->get();

		if($data->num_rows() > 0){
			return $data;
		}

		return false;
	}

	public function getListByUserId($userId){
		if(!$this->canList()){
			return false;
		}

		$userId = (int)$userId;
		$orderCodes = array();

		$this->db->select('RPI_DS_ORDER');
		$this->db->from(self::$TABLE);
		$this->db->where('RPI_BL_DELETE', 0);
		$this->db->where('USR_CO_ID', $userId);
		$this->db->where('RPI_DS_ORDER IS NOT NULL', NULL, FALSE);
		$this->db->where('RPI_DS_ORDER <>', '');
		$ordersResult = $this->db->get();

		if($ordersResult->num_rows() > 0){
			foreach($ordersResult->result() as $orderRow){
				$order = isset($orderRow->RPI_DS_ORDER) ? (string)$orderRow->RPI_DS_ORDER : '';
				if($order !== ''){
					$orderCodes[] = $order;
				}
			}
		}
		$orderCodes = array_values(array_unique($orderCodes));

		$this->db->select(
			'rpi.RPI_CO_ID, rpi.SOL_CO_ID, rpi.USR_CO_ID, rpi.RPI_DS_CANAL, rpi.RPI_DS_ESTADO, rpi.RPI_DS_ORDER, rpi.RPI_DS_RESPONSE_CODE, rpi.RPI_DT_CREATE, ' .
			'sol.SOL_DS_NOMBRE, sol.ESO_CO_ID, eso.ESO_DS_NAME'
		);
		$this->db->from(self::$TABLE . ' rpi');
		$this->db->join('t_sol_solicitudes sol', 'sol.SOL_CO_ID = rpi.SOL_CO_ID', 'left');
		$this->db->join('m_eso_estadosolicitud eso', 'eso.ESO_CO_ID = sol.ESO_CO_ID', 'left');
		$this->db->where('rpi.RPI_BL_DELETE', 0);
		$this->db->group_start();
		$this->db->where('rpi.USR_CO_ID', $userId);
		if(!empty($orderCodes)){
			$this->db->or_where_in('rpi.RPI_DS_ORDER', $orderCodes);
		}
		$this->db->group_end();
		$this->db->order_by('rpi.RPI_DT_CREATE', 'DESC');

		$data = $this->db->get();

		if($data->num_rows() > 0){
			return $data;
		}

		return false;
	}
}

/* End of file pagosmodel.php */
/* Location: ./application/models/pagosmodel.php */
