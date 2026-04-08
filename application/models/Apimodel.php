<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Apimodel extends CI_Model {

	function insertLog($data){
		$this->db->insert( 't_apt_apitest' ,$data);
		return $this->db->insert_id();
	}

	function getLatestInformeBySolicitud($solicitudId){
		$this->db->where('SOL_CO_ID', (int)$solicitudId);
		$this->db->from('t_apt_apitest');
		$this->db->order_by('APT_DT_ACCESO', 'DESC');
		$this->db->order_by('APT_CO_ID', 'DESC');
		$this->db->limit(1);

		$data = $this->db->get();
		if($data->num_rows() > 0){
			foreach($data->result() as $row){
				return $row;
			}
		}

		return false;
	}

}

/* End of file apimodel.php */
/* Location: ./application/models/apimodel.php */