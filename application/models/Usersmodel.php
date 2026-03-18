<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Usersmodel extends CI_Model {

	function verificarUsuarioPassword($valores){

		$this->db->where('USR_BL_ELIMINADO','0');
		$this->db->Where('USR_DS_MAIL',$valores["USR_DS_USUARIO"]);

		$data = $this->db->get('t_usr_usuarios');

		if($data->num_rows()>0){
			foreach ($data->result() as $row){
				if ($row->USR_BL_ACEPTADO == 0) {
					return array('error'=>TRUE, 'tipo'=>0, 'mensaje'=>"Tu usuario está pendiente de aceptación");
				}
				elseif ($row->USR_BL_DESHABILITADO == 1){
					return array('error'=>TRUE, 'tipo'=>0, 'mensaje'=>"El usuario está deshabilitado");

				}
				elseif( password_verify($valores["USR_DS_PASSWORD"], $row->USR_DS_PASSWORD) ){
					return array('error'=>FALSE, 'tipo'=>0, 'id'=>$row->USR_CO_ID,'username'=>$row->USR_DS_NOMBRE,'perfil'=>$row->PER_CO_ID, 'accesos'=>$row->USR_NM_INTENTOSACCESO, 'ultimoIntentoAcceso'=>$row->USR_DT_ULTIMOINTENTOACCESO);
				}
				
				else
				{
					return array('error'=>TRUE, 'tipo'=>1, 'mensaje'=>"Login error", 'id'=>$row->USR_CO_ID, 'accesos'=>$row->USR_NM_INTENTOSACCESO, 'ultimoIntentoAcceso'=>$row->USR_DT_ULTIMOINTENTOACCESO);
				}
			}
		}else{
			return array('error'=>TRUE, 'tipo'=>0, 'mensaje'=>"La contraseña no se corresponde con el usuario");
		}
	}

	function anadeIntento($id_usuario,$intentos){
		
		date_default_timezone_set('Europe/Madrid');
		$data = array(
			"USR_NM_INTENTOSACCESO"=>$intentos+1
			,"USR_DT_ULTIMOINTENTOACCESO"=>date ("Y-m-d H:i:s")
		);
		$this->db->where('USR_CO_ID',$id_usuario);
		$this->db->update('t_usr_usuarios',$data);
	}

	function loginSuccess($id_usuario){
		date_default_timezone_set('Europe/Madrid');
		$data = array(
			"USR_NM_INTENTOSACCESO"=>0
			,"USR_DT_ULTIMOACCESO"=>date ("Y-m-d H:i:s")
		);
		$this->db->where('USR_CO_ID',$id_usuario);
		$this->db->update('t_usr_usuarios',$data);
	}

	function getNumUsers(){
		$this->db->select("USR_CO_ID");
		$this->db->where('USR_BL_ELIMINADO','0');
		$this->db->where('USR_CO_ID>','0');

		$data = $this->db->get('t_usr_usuarios');

		return $data->num_rows();
	}


	function getUsers(){
		$this->db->select("USR_CO_ID,USR_DS_LOGIN,USR_DS_NOMBRE,USR_DS_APELLIDOS,PER_DS_NOMBRE,USR_DT_ULTIMOACCESO, USR_DS_MAIL");
		$this->db->where('USR_BL_ELIMINADO','0');
		$this->db->where('USR_CO_ID>','0');
		// $this->db->where('t_usr_usuarios.PER_CO_ID','1')->or_where('t_usr_usuarios.PER_CO_ID', '4');
		$this->db->from('t_usr_usuarios');
		$this->db->join('m_per_perfiles', 'm_per_perfiles.PER_CO_ID = t_usr_usuarios.PER_CO_ID');
		$data = $this->db->get();
		
		if ($data->num_rows() > 0){
			return $data;
		} else {
			$data = "No hay datos";
			return false;
		}
	}

	

	function getUserByCodUser($cod_user){
		$this->db->where('USR_CO_ID',$cod_user);
		$this->db->where('USR_BL_ELIMINADO','0');

		$data = $this->db->get('t_usr_usuarios');

		if ($data->num_rows() > 0){
			return $data;
		} else {
			$data = "No hay datos";
			return false;
		}
	}

	function getUserByIdUser($idUser){
		$this->db->where('USR_CO_ID',$idUser);
		$this->db->where('USR_BL_ELIMINADO','0');

		$data = $this->db->get('t_usr_usuarios');

		if ($data->num_rows() > 0){
			foreach ($data->result() as $row) {
				return $row;
			}
		} else {
			$data = "No hay datos";
			return false;
		}
	}

	

	

	function insertUser($data){
		$this->db->insert('t_usr_usuarios',$data);
		return $this->db->insert_id();
	}

	function updateUser($id,$data){
		$this->db->where('USR_CO_ID',$id);
		$this->db->update('t_usr_usuarios',$data);
		
	}

	function getUserByLogin($login){
		$query = "SELECT USR_CO_ID ";
		$query.= "FROM t_usr_usuarios ";
		$query.= "WHERE USR_DS_LOGIN like '".$login."' ";
		$query.= "AND PER_CO_ID=2 ";

		$data = $this->db->query($query);

		if($data->num_rows()>0){
			foreach ($data->result() as $row){
				return $row->USR_CO_ID;
			}
			
		}else{
			return 0;
		}
	}

	function insertAcceso($data){
		$this->db->insert('t_log_accesos',$data);
		return $this->db->insert_id();
	}

	

}

/* End of file usersmodel.php */
/* Location: ./application/models/usersmodel.php */