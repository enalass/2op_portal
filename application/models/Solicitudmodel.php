<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Solicitudmodel extends CI_Model {

	private static $TABLE = "t_sol_solicitudes";
	private static $PREFIX = "SOL";

	function getElementsList(){
		$this->db->where( self::$PREFIX . "_BL_DELETE",0);
		$this->db->from(self::$TABLE);

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

/* End of file Solicitudmodel.php */
/* Location: ./application/models/Solicitudmodel.php */