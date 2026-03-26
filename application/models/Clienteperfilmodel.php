<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Clienteperfilmodel extends CI_Model {

	public function getClienteById($userId){
		$this->db->select('USR_CO_ID, USR_DS_NOMBRE, USR_DS_MAIL');
		$this->db->from('t_usr_usuarios');
		$this->db->where('USR_CO_ID', (int)$userId);
		$this->db->where('USR_BL_ELIMINADO', '0');
		$this->db->where('PER_CO_ID', '4');
		$query = $this->db->get();

		if($query->num_rows() <= 0){
			return false;
		}

		return $query->row();
	}

	public function updateClientePerfil($userId, $data){
		$this->db->where('USR_CO_ID', (int)$userId);
		$this->db->where('USR_BL_ELIMINADO', '0');
		$this->db->where('PER_CO_ID', '4');
		return $this->db->update('t_usr_usuarios', $data);
	}
}
