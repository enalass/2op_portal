<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Redsysintentosmodel extends CI_Model {

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

	public function canInsert(){
		$this->ensureTableChecked();
		return $this->tableExists;
	}

	public function insertAttempt($data){
		if(!$this->canInsert()){
			return false;
		}

		$this->db->insert(self::$TABLE, $data);
		return $this->db->insert_id();
	}
}

/* End of file Redsysintentosmodel.php */
/* Location: ./application/models/Redsysintentosmodel.php */