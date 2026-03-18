<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Apimodel extends CI_Model {

	function insertLog($data){
		$this->db->insert( 't_apt_apitest' ,$data);
		return $this->db->insert_id();
	}

}

/* End of file apimodel.php */
/* Location: ./application/models/apimodel.php */