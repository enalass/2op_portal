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
				redirect('index.php/admin/cA001_panelControl','refresh');
			}if($this->session->userdata('perfil')==4){
				redirect('index.php/dashboard','refresh');
			}if($this->session->userdata('perfil')==5){
				redirect('index.php/panel','refresh');
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

}

/* End of file cerbero.php */
/* Location: ./application/controllers/cerbero.php */