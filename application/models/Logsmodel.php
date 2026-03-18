<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Logsmodel extends CI_Model {

	private static $TABLE = "t_log_accesos";
	private static $PREFIX = "LOG";

	function getElementsList(){
		$this->db->from(self::$TABLE);
		$this->db->join('t_usr_usuarios', 't_log_accesos.USR_CO_ID = t_usr_usuarios.USR_CO_ID');
		$this->db->join('m_per_perfiles', 't_log_accesos.PER_CO_ID = m_per_perfiles.PER_CO_ID');

		$data = $this->db->get();
		
		if ($data->num_rows() > 0){
			return $data;
		} else {
			$data = "No data";
			return false;
		}
	}

	function getElementById($id){
		$this->db->where( self::$PREFIX . "_CO_ID",$id );
		$this->db->from( self::$TABLE );

		$data = $this->db->get();
		
		if ($data->num_rows() > 0){
			foreach ($data->result() as $row) {
				return $row;
			}
		} else {
			$data = "No data";
			return false;
		}
	}

	function insertElement($data){
		$this->db->insert( self::$TABLE ,$data);
		return $this->db->insert_id();
	}

	function updateElement($data,$id){
		$this->db->where( self::$PREFIX . '_CO_ID',$id);
		$this->db->update( self::$TABLE ,$data);
		
	}

	function deleteElement($id){
		$this->db->where( self::$PREFIX . '_CO_ID',$id);
		$this->db->update( self::$TABLE ,array( self::$PREFIX . '_BL_DELETE' => 1));
		
	}

}

/* End of file Tarifamodel.php */
/* Location: ./application/models/Tarifamodel.php */