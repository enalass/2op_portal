<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cerbero extends CI_Controller {

	function __construct(){
		parent::__construct();

		$this->load->model('usersmodel');
		$this->load->model('perfilmodel');

	}
	public function index(){
		//http://localhost/SGA/index.php
		if($this->session->userdata('logged')==TRUE){
			//Si es Administrador
			if($this->session->userdata('perfil')==1){
				redirect('dashboard','refresh');
			}if($this->session->userdata('perfil')==4){
				redirect('panel','refresh');
			}else{
				echo $this->session->userdata('perfil');
			}
			
		}else{
			$data['respuesta'] = "";
			$this->load->view('Login',$data);
		}
	}

	public function login(){
		$this->form_validation->set_rules('username', 'email', 'required|trim|xss_clean');
		
		$this->form_validation->set_rules('password', 'password', 'required|trim|xss_clean');
		
		$this->form_validation->set_message('required','Rellena el campo '. ' %s');

		if($this->form_validation->run()==FALSE){

			//Metemos la lógica para usar From_validation con Ajax
			$errors = validation_errors();
			$resp = array(
				"login_status"	=>"invalid"
				,"msg"			=>$errors
				,"hash"			=>$this->security->get_csrf_hash()
				,"token"		=>$this->security->get_csrf_token_name()
			);
			echo json_encode($resp);
		}else{
			 // echo "CORRECTO!";
			$this->verificarUsuario();

			
		}

	}
	

	public function verificarUsuario(){

		date_default_timezone_set('Europe/Madrid');

		$data = array(
			"USR_DS_USUARIO" => $this->input->post('username'),
			"USR_DS_PASSWORD" => $this->input->post('password')
		);
	
		$respuesta = $this->usersmodel->verificarUsuarioPassword($data);
		$resp = array();
		$resp["login_status"]="invalid";
		$resp["hash"]=$this->security->get_csrf_hash();
		$resp["token"]=$this->security->get_csrf_token_name();
		if($respuesta["error"]){
			//Hay un problema con el login
			if ($respuesta["tipo"]==1) {

				if($respuesta["accesos"]>=3 && (date("Y-m-d H:i:s",strtotime('+5 minute',strtotime($respuesta["ultimoIntentoAcceso"])))>date("Y-m-d H:i:s"))){
					$resp["msg"]="Has excedido el número de intentos, por favor, inténtalo en un rato.";
				}else{
					$this->usersmodel->anadeIntento($respuesta["id"],$respuesta["accesos"]);
					$resp["msg"]= $respuesta["mensaje"];
				}
			}else{
				$resp["msg"]= $respuesta["mensaje"];
			}

			//Deprecado, ahora el login es mediante ajax
			// $data['respuesta']=$respuesta["mensaje"];
			// $this->load->view('Login',$data);

			//Refactoring para el login con AJAX
			
			echo json_encode($resp);
			
			
		}else{
			//Se ha logueado correctamente
			#verificar que el número de intentos no sea mayor que 3, en caso afirmativo
			#verificar que la hora actual es 5 minutos mayor que la última hora de acceso

			if($respuesta["accesos"]>=3 && (date("Y-m-d H:i:s",strtotime('+5 minute',strtotime($respuesta["ultimoIntentoAcceso"])))>date("Y-m-d H:i:s"))){
				$resp["msg"]="Has excedido el número de intentos, por favor, inténtalo en un rato.";
			}else{
				$resp["login_status"]=  "success";
				$resp['redirect_url'] = base_url() . "index.php/Cerbero";
				$resp["hash"]=$this->security->get_csrf_hash();

				$sess_array = array(
	        		//usr_co_id
	               'id' => $respuesta["id"], 
	               //USR_DS_LOGIN
	               'username' => $respuesta["username"],
	               'perfil' => $respuesta["perfil"],
	               'acceso' => $this->perfilmodel->getAccesoByID($respuesta["perfil"]),
	               'logged' => TRUE,
	               'adminUser' => 0,
		           'gestSU'=>FALSE
	            );
	        

	            $this->session->set_userdata($sess_array);
	            $this->usersmodel->loginSuccess($respuesta["id"]);
	            $this->usersmodel->insertAcceso(array("USR_CO_ID"=>$respuesta["id"],"PER_CO_ID"=>$respuesta["perfil"]));
	        }
            // redirect('cerbero');
            echo json_encode($resp);
		}
		


	}

	public function logout(){
		$sess_array = array(
               'id' => "", 
               'username' => "",
               'acceso' => 0,
               'logged' => FALSE,
               'perfil' => 0,
               'id_cliente' => 0,
               'razon_social' => "",
               'accesoTecDoc'=>0,
               'tipoCliente' => 0,
               'adminUser' => 0,
		       'gestSU'=>FALSE
            );
        $this -> session -> set_userdata($sess_array);
        $this->session->unset_userdata('IDIOMA');

        redirect('');
	}

	public function recuperarPassword(){
		$this->form_validation->set_rules('email', 'email', 'required|trim|valid_email|xss_clean');
		$this->form_validation->set_message('required','Rellena el campo '. ' %s');

		$resp = array(
			"status"	=>"invalid",
			"msg"		=>"No se ha podido procesar la solicitud.",
			"hash"		=>$this->security->get_csrf_hash(),
			"token"	=>$this->security->get_csrf_token_name()
		);

		if($this->form_validation->run()==FALSE){
			$resp["msg"] = validation_errors();
			echo json_encode($resp);
			return;
		}

		$email = trim($this->input->post('email', TRUE));
		$user = $this->usersmodel->getUserByMailForRecover($email);

		if($user === FALSE){
			$resp["status"] = "success";
			$resp["msg"] = "Si el email existe en el sistema, recibirás una nueva contraseña.";
			$resp["hash"] = $this->security->get_csrf_hash();
			echo json_encode($resp);
			return;
		}

		$newPassword = $this->generarPasswordTemporal(12);
		$newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

		if($newPasswordHash === FALSE){
			$resp["msg"] = "No se ha podido generar una nueva contraseña.";
			$resp["hash"] = $this->security->get_csrf_hash();
			echo json_encode($resp);
			return;
		}

		$this->db->trans_begin();

		$this->usersmodel->updateUser($user->USR_CO_ID, array(
			"USR_DS_PASSWORD" => $newPasswordHash,
			"USR_NM_INTENTOSACCESO" => 0
		));

		if ($this->db->trans_status() === FALSE){
			$this->db->trans_rollback();
			$resp["msg"] = "No se ha podido actualizar la contraseña.";
			$resp["hash"] = $this->security->get_csrf_hash();
			echo json_encode($resp);
			return;
		}

		$this->load->library('Emailtemplate');
		$payload = array(
			'nombre' => $user->USR_DS_NOMBRE,
			'email' => $user->USR_DS_MAIL,
			'password' => $newPassword,
			'url_acceso' => rtrim(base_url(), '/').'/usuarios',
			'app_name' => 'Segunda opinión radiológica'
		);

		$sent = $this->emailtemplate->sendRecuperacionPassword($user->USR_DS_MAIL, $payload, array(
			'from_name' => 'Soporte Segunda opinión radiológica'
		));

		if(!$sent){
			$this->db->trans_rollback();
			$resp["msg"] = "No se ha podido enviar el email de recuperación.";
			$resp["hash"] = $this->security->get_csrf_hash();
			echo json_encode($resp);
			return;
		}

		$this->db->trans_commit();

		$resp["status"] = "success";
		$resp["msg"] = "Hemos enviado una nueva contraseña a tu correo electrónico.";
		$resp["hash"] = $this->security->get_csrf_hash();
		echo json_encode($resp);
	}

	private function generarPasswordTemporal($length = 12)
	{
		$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
		$maxIndex = strlen($chars) - 1;
		$password = '';

		for ($i = 0; $i < $length; $i++)
		{
			$index = $this->secureRandomInt(0, $maxIndex);
			$password .= $chars[$index];
		}

		return $password;
	}

	private function secureRandomInt($min, $max)
	{
		if (function_exists('random_int'))
		{
			return random_int($min, $max);
		}

		$bytes = $this->security->get_random_bytes(4);
		if ($bytes !== FALSE)
		{
			$value = unpack('N', $bytes);
			if (is_array($value) && isset($value[1]))
			{
				return $min + ($value[1] % (($max - $min) + 1));
			}
		}

		return mt_rand($min, $max);
	}

}

/* End of file cerbero.php */
/* Location: ./application/controllers/cerbero.php */