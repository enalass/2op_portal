<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CA002_users extends CI_Controller {

	function __construct(){
		parent::__construct();

		$this->load->model('usersmodel');
		$this->load->model('perfilmodel');
		// $this->load->model('generaladminmodel');
	}

	public function index()
	{
		
		if( $this->session->userdata('logged')==TRUE && ($this->session->userdata('acceso'))>=100 ){
			$data = array(
				"content"			=> "admin/vA002_usuarios.php"
				,"titulo" 			=> "2º Opinión Radiológica"
				,"javascriptMenu"	=>"$('#menuUsers').addClass('menu-item-active');"
				
			);
			$this->load->view('layout_admin',$data);
		}else{
			redirect('index.php/cerbero','refresh');
		}
	}

	public function getUsuarios(){
		$data = array();

		if( $this->session->userdata('logged')!=TRUE || ($this->session->userdata('acceso')) < 100 ){
			echo json_encode($data);
			return;
		}

		$usuarios = $this->usersmodel->getUsers();
		if($usuarios != false){
			foreach ($usuarios->result() as $usuario) {

				$data[] = array(
								"RecordID"			=>intval($usuario->USR_CO_ID)
								,"Name"				=>$usuario->USR_DS_NOMBRE
								,"Surname"			=>$usuario->USR_DS_APELLIDOS
								,"Mail"				=>$usuario->USR_DS_MAIL
								,"Perfil"			=>$usuario->PER_DS_NOMBRE
								
							);
			}
		}
		echo json_encode( array("meta"=>array("field"=>"RecordID"),"data"=>$data) );
	}

	public function nuevoUsuario(){

		//Cargamos el Formulario
		$nombreFichero= FCPATH. "application/views/admin/form_newUser.php";

		$fichero = fopen($nombreFichero,"r");

		//Cargamos la plantilla del formulario
		$contenido = fread($fichero,filesize($nombreFichero));

		//Añadimos los input
		$campos_formulario = array(
			"@FIELD_ID"
			,"@FIELD_PASSWORD"
			,"@FIELD_REPASSWORD"
			,"@FIELD_PERFIL"
			,"@FIELD_NAME"
			,"@FIELD_SURNAME"
			,"@FIELD_MAIL"
			,"@FIELD_DEPARTAMENTO"
		);
		$perfil[0]="Seleccionar perfil";
		$perfil[1]="ADMINISTRATOR";
		$perfil[4]="CANDIDATE";
		$valores_campos_formulario = array(
			form_hidden('user_id', '0')
			,form_password(array("name"=>"user_password", "id"=>"user_password","class"=>"form-control","placeholder"=>"Contraseña", "value"=>""))
			,form_password(array("name"=>"user_repassword", "id"=>"user_repassword","class"=>"form-control","placeholder"=>"Contraseña", "value"=>""))
			// ,form_dropdown('user_perfil', $perfil, set_value('1'),"id='user_perfil' class='form-control'")
			,form_dropdown('user_perfil', $this->perfilmodel->getPerfilesCombo(), set_value('1'),"id='user_perfil' class='form-control' ")
			// ,form_dropdown('user_perfil', $this->perfilmodel->getPerfilesCombo(), set_value('0'),"id='user_perfil' class='form-control'")
			,form_input(array("name"=>"user_name", "id"=>"user_name","class"=>"form-control","placeholder"=>"Nombre", "value"=>""))
			,form_input(array("name"=>"user_surname", "id"=>"user_surname","class"=>"form-control","placeholder"=>"Apellidos", "value"=>""))
			,form_input(array("name"=>"user_mail", "id"=>"user_mail","class"=>"form-control","placeholder"=>"E-Mail", "value"=>""))
			,""

		);
		$contenido = str_replace($campos_formulario, $valores_campos_formulario, $contenido);

		//Creamos el Formulario
		$formulario = form_open(base_url()."index.php/admin/cA002_users/addUser",array("id"=>"formModalUser"));
		$formulario .= $contenido;
		$formulario .= form_close();

		echo $formulario;
	}

	public function addUser(){

		$resp = array();


		$this->form_validation->set_rules('user_mail', 'e-mail', 'required|valid_email|trim|xss_clean');
		$this->form_validation->set_rules('user_password', 'password', 'required|matches[user_repassword]|trim|xss_clean');
		$this->form_validation->set_rules('user_repassword', 'retype password', 'required|xss_clean|trim');
		$this->form_validation->set_rules('user_name', 'name', 'required|trim|xss_clean');
		$this->form_validation->set_rules('user_surname', 'surname', 'required|trim|xss_clean');
		
		$this->form_validation->set_message('required','Debes rellenar el campo '. ' %s');
		$this->form_validation->set_message('matches', 'Los campos %s y %s no coinciden');
		$this->form_validation->set_message('min_length', 'El campo %s debe tener al menos %s caracteres');

		if($this->form_validation->run()==FALSE){
			// echo "NO CORRECTO!";
			// $data['respuesta'] = "";
			// $this->load->view('Login',$data);	

			//Metemos la lógica para usar From_validation con Ajax
			$errors = validation_errors();
			$resp = array(
				"status"=>	"unsuccess"
				,"msg"=>	$errors
				,"hash"=> 	$this->security->get_csrf_hash()
				,"token"=> 	$this->security->get_csrf_token_name()
			);
		}else{
			 // echo "CORRECTO!";
			$resp["status"]	=	"success";

			//GUARDAMOS
			$data = array(
				"USR_DS_MAIL"=>$this->input->post('user_mail')
				,"USR_DS_PASSWORD"=>password_hash($this->input->post('user_password'), PASSWORD_DEFAULT,['cost'=>10])
				,"USR_DS_NOMBRE"=>$this->input->post('user_name')
				,"USR_DS_APELLIDOS"=>$this->input->post('user_surname')
				,"PER_CO_ID"=>$this->input->post('user_perfil')
				,"USR_BL_ACEPTADO"=>1
			);
			//verificamos que cuando guarden estén logueados
			if( $this->session->userdata('logged')==TRUE && ($this->session->userdata('acceso'))>=100 ){

				$idInsert = $this->usersmodel->insertUser($data);
			}			
		}

		echo json_encode($resp);
		
	}

	public function editaUsuario($id_usuario=0){
		//Cargamos el Formulario
		$nombreFichero= FCPATH. "application/views/admin/form_newUser.php";

		$fichero = fopen($nombreFichero,"r");

		//Cargamos la plantilla del formulario
		$contenido = fread($fichero,filesize($nombreFichero));

		//Cargamos los datos de usuario
		$user = $this->usersmodel->getUserByCodUser($id_usuario);

		if($user!=false){
			foreach ($user->result() as $row) {
				//Añadimos los input
				$campos_formulario = array(
					"@FIELD_ID"
					,"@FIELD_PASSWORD"
					,"@FIELD_REPASSWORD"
					,"@FIELD_PERFIL"
					,"@FIELD_NAME"
					,"@FIELD_SURNAME"
					,"@FIELD_MAIL"
					,"@BUTTON_ACCION"
					,"@FIELD_DEPARTAMENTO"
				);
				$valores_campos_formulario = array(
					form_hidden('user_id', $row->USR_CO_ID)
					,form_password(array("name"=>"user_password", "id"=>"user_password","class"=>"form-control","placeholder"=>"Contraseña", "value"=>""))
					,form_password(array("name"=>"user_repassword", "id"=>"user_repassword","class"=>"form-control","placeholder"=>"Contraseña", "value"=>""))
					,form_dropdown('user_perfil', $this->perfilmodel->getPerfilesCombo(), $row->PER_CO_ID,"id='user_perfil' class='form-control' ")
					,form_input(array("name"=>"user_name", "id"=>"user_name","class"=>"form-control","placeholder"=>"Nombre", "value"=>"$row->USR_DS_NOMBRE"))
					,form_input(array("name"=>"user_surname", "id"=>"user_surname","class"=>"form-control","placeholder"=>"Apellidos", "value"=>"$row->USR_DS_APELLIDOS"))
					,form_input(array("name"=>"user_mail", "id"=>"user_mail","class"=>"form-control","placeholder"=>"E-Mail", "value"=>"$row->USR_DS_MAIL"))
					,"<button type='submit' class='btn btn-green btn-icon icon-left'>Modificar Usuario<i class='entypo-user'></i></button>"
					,""
					// ,$this->getSelectDepartamentos($row->USR_CO_ID)
				);
				$contenido = str_replace($campos_formulario, $valores_campos_formulario, $contenido);
			}
		}
		
		

		//Creamos el Formulario
		$formulario = form_open(base_url()."index.php/admin/cA002_users/updateUser",array("id"=>"formModalUser"));
		$formulario .= $contenido;
		$formulario .= form_close();

		echo $formulario;
	}

	public function updateUser(){

		$resp = array();


		$this->form_validation->set_rules('user_mail', 'e-mail', 'required|valid_email|trim|xss_clean');
		$this->form_validation->set_rules('user_password', 'password', 'matches[user_repassword]|trim|xss_clean');
		$this->form_validation->set_rules('user_repassword', 'retype password', 'xss_clean|trim');
		$this->form_validation->set_rules('user_name', 'name', 'required|trim|xss_clean');
		$this->form_validation->set_rules('user_surname', 'surname', 'required|trim|xss_clean');
		
		$this->form_validation->set_message('required','You must fill in the field '. ' %s');
		$this->form_validation->set_message('matches', 'The fields %s and %s don\'t match');
		$this->form_validation->set_message('min_length', 'The field %s must be at least %s characters');

		if($this->form_validation->run()==FALSE){
			// echo "NO CORRECTO!";
			// $data['respuesta'] = "";
			// $this->load->view('Login',$data);	

			//Metemos la lógica para usar From_validation con Ajax
			$errors = validation_errors();
			$resp = array(
				"status"=>"unsuccess"
				,"msg"=>$errors
				,"hash"=>$this->security->get_csrf_hash()
				,"token"=> 	$this->security->get_csrf_token_name()
			);
		}else{
			 // echo "CORRECTO!";
			$resp["status"]="success";
			//ACTUALIZAMOS, la password solamente la actualizamos si es distinto de ''
			$data = array(
				"USR_DS_MAIL"=>$this->input->post('user_mail')
				,"USR_DS_NOMBRE"=>$this->input->post('user_name')
				,"USR_DS_APELLIDOS"=>$this->input->post('user_surname')
				,"PER_CO_ID"=>$this->input->post('user_perfil')
			);
			if($this->input->post('user_password')!=""){
				$data["USR_DS_PASSWORD"]=password_hash($this->input->post('user_password'), PASSWORD_DEFAULT,['cost'=>10]);
			}
			//verificamos que cuando guarden estén logueados
			if( $this->session->userdata('logged')==TRUE && ($this->session->userdata('acceso')) >= 100 ){
				$this->usersmodel->updateUser($this->input->post('user_id'),$data);
			}
		}

		echo json_encode($resp);
		
	}

	public function borrarUsuario($id_usuario=0){
		$user = $this->usersmodel->getUserByCodUser($id_usuario);

		if($user!=false){
			foreach ($user->result() as $row) {
				$contenido = "<div>The user is about to be deleted: <strong>$row->USR_DS_MAIL</strong><br>";
				$contenido.= "<ul>";
				$contenido.= "<li>Name: $row->USR_DS_NOMBRE $row->USR_DS_APELLIDOS</li>";
				$contenido.= "<li>Last Access: $row->USR_DT_ULTIMOACCESO</li>";
				$contenido.= "</ul></div>";
				$contenido.= form_open(base_url()."index.php/admin/cA002_users/deleteUser",array("id"=>"formModalUser"));
				$contenido.= form_hidden('user_id', $row->USR_CO_ID);
				
				$contenido.= form_close();
				
			}
		}else{
			$contenido = "No user found";
		}
		
		echo $contenido;
	}

	public function deleteUser($id_usuario=0){
		$resp = array();
		$resp["status"]="success";
		$data=array(
			"USR_BL_ELIMINADO"=>1
		);
		if( $this->session->userdata('logged') == TRUE && ($this->session->userdata('acceso')) >= 100 ){
			$this->usersmodel->updateUser($this->input->post('user_id'),$data);
		}

		echo json_encode($resp);
	}

	public function cambiarPerfil()
	{
		if($this->session->userdata('adminUser')>0){
			$user = $this->usersmodel->getUserByCodUser($this->session->userdata('adminUser'));
			if ($user!=false) {
				// echo $user->USR_DS_LOGIN;
				// echo $user->USR_CO_ID;
				// echo $user->PER_CO_ID;
				// echo $this->perfilmodel->getAccesoByID($user->PER_CO_ID);
				foreach ($user->result() as $userRow) {
					$user = $userRow;
				}
				$sess_array = array(
		           'id' => $user->USR_CO_ID,
		           // 'superuser' => $respuesta["superuser"], 
		           'username' => $user->USR_DS_LOGIN,
		           'perfil' => $user->PER_CO_ID,
		           'acceso' => $this->perfilmodel->getAccesoByID($user->PER_CO_ID),
		           'logged' => TRUE,
		           'adminUser' => 0,
		           'gestSU'=>FALSE
		        );
		        $this->session->set_userdata($sess_array);
		        //$this->usersmodel->loginSuccess($user->USR_CO_ID);
		        if ($user->PER_CO_ID==3) {
		        	redirect('taller/cA001_panelControl','refresh');
		        }else  if ($user->PER_CO_ID==2) {
			        redirect('concesionario/cA001_panelControl','refresh');
			    }
			    else if ($user->PER_CO_ID==1) {
			    	redirect('admin/cA001_panelControl','refresh');
			    }else if ($user->PER_CO_ID==4) {
			    	redirect('comercial/cA001_panelControl','refresh');
			    }
		        
			}else{
				echo 'Acción no requerida, póngase en contacto con su administrador';
			}
		}

	}

	public function redirigePerfil($id=0){
		if($this->session->userdata('logged')==TRUE AND $this->session->userdata('acceso')>=100){
			$user = $this->usersmodel->getUserByCodUser($id);
			if ($user!=false) {
				// echo $user->USR_DS_LOGIN;
				// echo $user->USR_CO_ID;
				// echo $user->PER_CO_ID;
				// echo $this->perfilmodel->getAccesoByID($user->PER_CO_ID);
				foreach ($user->result() as $userRow) {
					$user = $userRow;
				}
				$sess_array = array(
		           'id' => $user->USR_CO_ID,
		           // 'superuser' => $respuesta["superuser"], 
		           'username' => $user->USR_DS_LOGIN,
		           'perfil' => $user->PER_CO_ID,
		           'acceso' => $this->perfilmodel->getAccesoByID($user->PER_CO_ID),
		           'logged' => TRUE,
		           'adminUser' => $this->session->userdata('id'),
		           'gestSU'=>TRUE
		        );
		        $this->session->set_userdata($sess_array);
		        //$this->usersmodel->loginSuccess($user->USR_CO_ID);
		        if ($user->PER_CO_ID==4) {
		        	redirect('candidate/cA001_panelControl','refresh');
		        }else if ($user->PER_CO_ID==1) {
			    	redirect('admin/cA001_panelControl','refresh');
			    }
		        
			}else{
				echo 'No entra';
			}     

		}else{
			echo "503 - Forbidden";
		}
    }

    public function manageDepartments(){
		$action = $this->input->post('event');
    	$value 	= $this->input->post('value');
    	$app 	= $this->input->post('to');

    	if($this->session->userdata('logged')==TRUE AND $this->session->userdata('acceso')>=100){
    		$response["status"] = "success";
    		$response["hash"] 	= $this->security->get_csrf_hash();
    		$response["token"] 	= $this->security->get_csrf_token_name();
    		// if ( $action == 'add' ){
    		// 	$this->generaladminmodel->addDepartmentUser($value,$app);
    		// }else if( $action == 'remove' ){
    		// 	$this->generaladminmodel->removeDepartmentUser($value,$app);
    		// }
    	}else{
    		$response["status"] = "unsuccess";
    	}
    	
    	echo json_encode($response);
	}

	private function getSelectDepartamentos($idUser){
    	

    	$response = "<style>.dual-listbox .dual-listbox__available, .dual-listbox .dual-listbox__selected{height:150px!important;}</style>
    				<div id='panelListBox' >
    					<h5>Mantenimiento de departamentos</h5>
    				<select id='kt_dual_listbox' class='dual-listbox' multiple >";

    	$response.= $this->generaladminmodel->getComboDepartamentosUsuario($idUser);

		$response .= "</select>";

		$response .= "<script>
						var listBox = new DualListbox('#kt_dual_listbox',{
							addEvent: function(value) { // Should use the event 
								eventDepartmentToUser('add',value,{$idUser});
						    },
						    removeEvent: function (value) {
							    eventDepartmentToUser('remove',value,{$idUser});
							},
						    availableTitle: 'Dept. disponibles',
						    selectedTitle: 'Dept. seleccionados',
						    addButtonText: 'Añadir',
						    removeButtonText: 'Quitar',
						    addAllButtonText: 'Añadir todos',
						    removeAllButtonText: 'Quitar todos',

						    
						});
						listBox.search.classList.add('dual-listbox__search--hidden');
					  </script>";

		return $response;
    }

}

/* End of file cA002_users.php */
/* Location: ./application/controllers/admin/cA002_users.php */ 
?>