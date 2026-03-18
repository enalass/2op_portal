<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Perfilmodel extends CI_Model {

	function getAccesoByID($id){
		$this->db->Where('PER_CO_ID',$id);

		$data = $this->db->get('m_per_perfiles');
		
		if($data->num_rows()>0){
			foreach ($data->result() as $row){
				$perfil = $row->PER_NM_ACCESO;
			}
			return $perfil;
		}else{
			return false;
		}
	}

	function getPerfilesCombo($primero=1){
		$this->db->Where('PER_CO_ID >=',$primero);
		$data = $this->db->get('m_per_perfiles');
		
		if ($data->num_rows() > 0){
			
			//Creamos un array para el drop_down (combobox)
			$perfiles[0]="Seleccionar perfil";
			foreach ($data->result() as $row){
				$perfiles[$row -> PER_CO_ID]=$row -> PER_DS_NOMBRE; 
			}
			

			return $perfiles;
		} else {
			$data = array('0'=>'ERROR');
			return $data;
		}
	}

}

/* End of file perfilmodel.php */
/* Location: ./application/models/perfilmodel.php */