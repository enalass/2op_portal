<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Clientesmodel extends CI_Model {

	function getClientes(){
		$this->db->select("USR_CO_ID,USR_DS_LOGIN,USR_DS_NOMBRE,USR_DS_APELLIDOS,PER_DS_NOMBRE,USR_DT_ULTIMOACCESO,USR_DS_MAIL");
		$this->db->where('USR_BL_ELIMINADO','0');
		$this->db->where('USR_CO_ID>','0');
		$this->db->where('t_usr_usuarios.PER_CO_ID','4');
		$this->db->from('t_usr_usuarios');
		$this->db->join('m_per_perfiles', 'm_per_perfiles.PER_CO_ID = t_usr_usuarios.PER_CO_ID');
		$data = $this->db->get();

		if ($data->num_rows() > 0){
			return $data;
		}

		return false;
	}

	function getClienteByCodUser($cod_user){
		$this->db->where('USR_CO_ID',$cod_user);
		$this->db->where('USR_BL_ELIMINADO','0');
		$this->db->where('PER_CO_ID','4');

		$data = $this->db->get('t_usr_usuarios');

		if ($data->num_rows() > 0){
			return $data;
		}

		return false;
	}

	function insertCliente($data){
		$data['PER_CO_ID'] = 4;
		$this->db->insert('t_usr_usuarios',$data);
		return $this->db->insert_id();
	}

	function updateCliente($id,$data){
		$data['PER_CO_ID'] = 4;
		$this->db->where('USR_CO_ID',$id);
		$this->db->where('PER_CO_ID','4');
		$this->db->update('t_usr_usuarios',$data);
	}
}

/* End of file clientesmodel.php */
/* Location: ./application/models/clientesmodel.php */
