<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Solicitudarchivosmodel extends CI_Model {

	private static $TABLE = 't_sar_solicitudarchivos';
	private $tableChecked = false;
	private $tableExists = false;

	private function ensureTableChecked(){
		if($this->tableChecked){
			return;
		}

		$this->tableExists = $this->db->table_exists(self::$TABLE);
		$this->tableChecked = true;
	}

	public function canUse(){
		$this->ensureTableChecked();
		return $this->tableExists;
	}

	public function insertArchivo($data){
		if(!$this->canUse()){
			return false;
		}

		$this->db->insert(self::$TABLE, $data);
		return $this->db->insert_id();
	}

	public function getArchivosBySolicitud($solicitudId){
		if(!$this->canUse()){
			return false;
		}

		$this->db->where('SOL_CO_ID', (int)$solicitudId);
		$this->db->where('SAR_BL_DELETE', 0);
		$this->db->from(self::$TABLE);
		$this->db->order_by('SAR_DT_CREATE', 'DESC');

		$data = $this->db->get();
		if($data->num_rows() > 0){
			return $data;
		}

		return false;
	}
}

/* End of file Solicitudarchivosmodel.php */
/* Location: ./application/models/Solicitudarchivosmodel.php */
