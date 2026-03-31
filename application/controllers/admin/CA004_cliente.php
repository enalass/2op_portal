<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CA004_cliente extends CI_Controller {

	function __construct(){
		parent::__construct();

		$this->load->model('clientesmodel');
		$this->load->model('solicitudmodel');
	}

	public function index()
	{
		if( $this->session->userdata('logged')==TRUE && ($this->session->userdata('acceso'))>=100 ){
			$data = array(
				"content"			=> "admin/vA004_cliente.php"
				,"titulo" 			=> "2º Opinión Radiológica"
				,"javascriptMenu"	=>"$('#menuClientes').addClass('menu-item-active');"
			);
			$this->load->view('layout_admin',$data);
		}else{
			redirect('index.php/cerbero','refresh');
		}
	}

	public function getClientes(){
		$data = array();

		if( $this->session->userdata('logged')!=TRUE || ($this->session->userdata('acceso')) < 100 ){
			echo json_encode($data);
			return;
		}

		$clientes = $this->clientesmodel->getClientes();
		if($clientes != false){
			foreach ($clientes->result() as $cliente) {
				$data[] = array(
							"RecordID"			=>intval($cliente->USR_CO_ID)
							,"Name"				=>$cliente->USR_DS_NOMBRE
							,"Surname"			=>$cliente->USR_DS_APELLIDOS
							,"Mail"				=>$cliente->USR_DS_MAIL
							,"Perfil"			=>$cliente->PER_DS_NOMBRE
						);
			}
		}
		echo json_encode( array("meta"=>array("field"=>"RecordID"),"data"=>$data) );
	}

	public function nuevoCliente(){
		$nombreFichero= FCPATH. "application/views/admin/form_newUser.php";
		$fichero = fopen($nombreFichero,"r");
		$contenido = fread($fichero,filesize($nombreFichero));

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

		$perfilCliente = form_hidden('user_perfil', '4')."<input type='text' class='form-control' value='CLIENTE' disabled='disabled'>";
		$valores_campos_formulario = array(
			form_hidden('user_id', '0')
			,form_password(array("name"=>"user_password", "id"=>"user_password","class"=>"form-control","placeholder"=>"Contraseña", "value"=>""))
			,form_password(array("name"=>"user_repassword", "id"=>"user_repassword","class"=>"form-control","placeholder"=>"Contraseña", "value"=>""))
			,$perfilCliente
			,form_input(array("name"=>"user_name", "id"=>"user_name","class"=>"form-control","placeholder"=>"Nombre", "value"=>""))
			,form_input(array("name"=>"user_surname", "id"=>"user_surname","class"=>"form-control","placeholder"=>"Apellidos", "value"=>""))
			,form_input(array("name"=>"user_mail", "id"=>"user_mail","class"=>"form-control","placeholder"=>"E-Mail", "value"=>""))
			,"<div class='alert alert-light mb-0'>Guarda el cliente para ver sus solicitudes asociadas.</div>"
		);
		$contenido = str_replace($campos_formulario, $valores_campos_formulario, $contenido);

		$formulario = form_open(base_url()."index.php/admin/cA004_cliente/addCliente",array("id"=>"formModalUser"));
		$formulario .= $contenido;
		$formulario .= form_close();

		echo $formulario;
	}

	public function addCliente(){
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
			$errors = validation_errors();
			$resp = array(
				"status"=>"unsuccess"
				,"msg"=>$errors
				,"hash"=>$this->security->get_csrf_hash()
				,"token"=>$this->security->get_csrf_token_name()
			);
		}else{
			$resp["status"] = "success";
			$data = array(
				"USR_DS_MAIL"=>$this->input->post('user_mail')
				,"USR_DS_PASSWORD"=>password_hash($this->input->post('user_password'), PASSWORD_DEFAULT,['cost'=>10])
				,"USR_DS_NOMBRE"=>$this->input->post('user_name')
				,"USR_DS_APELLIDOS"=>$this->input->post('user_surname')
				,"PER_CO_ID"=>4
				,"USR_BL_ACEPTADO"=>1
			);

			if( $this->session->userdata('logged')==TRUE && ($this->session->userdata('acceso'))>=100 ){
				$this->clientesmodel->insertCliente($data);
			}
		}

		echo json_encode($resp);
	}

	public function editaCliente($id_usuario=0){
		$nombreFichero= FCPATH. "application/views/admin/form_newUser.php";
		$fichero = fopen($nombreFichero,"r");
		$contenido = fread($fichero,filesize($nombreFichero));

		$user = $this->clientesmodel->getClienteByCodUser($id_usuario);
		if($user!=false){
			foreach ($user->result() as $row) {
				$solicitudesCliente = $this->renderSolicitudesCliente($row->USR_CO_ID);
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
				$perfilCliente = form_hidden('user_perfil', '4')."<input type='text' class='form-control' value='CLIENTE' disabled='disabled'>";
				$valores_campos_formulario = array(
					form_hidden('user_id', $row->USR_CO_ID)
					,form_password(array("name"=>"user_password", "id"=>"user_password","class"=>"form-control","placeholder"=>"Contraseña", "value"=>""))
					,form_password(array("name"=>"user_repassword", "id"=>"user_repassword","class"=>"form-control","placeholder"=>"Contraseña", "value"=>""))
					,$perfilCliente
					,form_input(array("name"=>"user_name", "id"=>"user_name","class"=>"form-control","placeholder"=>"Nombre", "value"=>"$row->USR_DS_NOMBRE"))
					,form_input(array("name"=>"user_surname", "id"=>"user_surname","class"=>"form-control","placeholder"=>"Apellidos", "value"=>"$row->USR_DS_APELLIDOS"))
					,form_input(array("name"=>"user_mail", "id"=>"user_mail","class"=>"form-control","placeholder"=>"E-Mail", "value"=>"$row->USR_DS_MAIL"))
					,"<button type='submit' class='btn btn-green btn-icon icon-left'>Modificar Cliente<i class='entypo-user'></i></button>"
					,$solicitudesCliente
				);
				$contenido = str_replace($campos_formulario, $valores_campos_formulario, $contenido);
			}
		}

		$formulario = form_open(base_url()."index.php/admin/cA004_cliente/updateCliente",array("id"=>"formModalUser"));
		$formulario .= $contenido;
		$formulario .= form_close();

		echo $formulario;
	}

	public function updateCliente(){
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
			$errors = validation_errors();
			$resp = array(
				"status"=>"unsuccess"
				,"msg"=>$errors
				,"hash"=>$this->security->get_csrf_hash()
				,"token"=>$this->security->get_csrf_token_name()
			);
		}else{
			$resp["status"] = "success";
			$data = array(
				"USR_DS_MAIL"=>$this->input->post('user_mail')
				,"USR_DS_NOMBRE"=>$this->input->post('user_name')
				,"USR_DS_APELLIDOS"=>$this->input->post('user_surname')
				,"PER_CO_ID"=>4
			);
			if($this->input->post('user_password')!=""){
				$data["USR_DS_PASSWORD"] = password_hash($this->input->post('user_password'), PASSWORD_DEFAULT,['cost'=>10]);
			}
			if( $this->session->userdata('logged')==TRUE && ($this->session->userdata('acceso')) >= 100 ){
				$this->clientesmodel->updateCliente($this->input->post('user_id'),$data);
			}
		}

		echo json_encode($resp);
	}

	public function borrarCliente($id_usuario=0){
		$user = $this->clientesmodel->getClienteByCodUser($id_usuario);

		if($user!=false){
			foreach ($user->result() as $row) {
				$contenido = "<div>El cliente va a ser eliminado: <strong>$row->USR_DS_MAIL</strong><br>";
				$contenido.= "<ul>";
				$contenido.= "<li>Nombre: $row->USR_DS_NOMBRE $row->USR_DS_APELLIDOS</li>";
				$contenido.= "<li>Último acceso: $row->USR_DT_ULTIMOACCESO</li>";
				$contenido.= "</ul></div>";
				$contenido.= form_open(base_url()."index.php/admin/cA004_cliente/deleteCliente",array("id"=>"formModalUser"));
				$contenido.= form_hidden('user_id', $row->USR_CO_ID);
				$contenido.= form_close();
			}
		}else{
			$contenido = "No se encontró cliente";
		}

		echo $contenido;
	}

	public function deleteCliente(){
		$resp = array();
		$resp["status"] = "success";
		$data = array(
			"USR_BL_ELIMINADO"=>1
		);
		if( $this->session->userdata('logged') == TRUE && ($this->session->userdata('acceso')) >= 100 ){
			$this->clientesmodel->updateCliente($this->input->post('user_id'),$data);
		}

		echo json_encode($resp);
	}

	private function renderSolicitudesCliente($idUsuario)
	{
		$solicitudes = $this->solicitudmodel->getClientVisibleSolicitudes((int)$idUsuario, 0);

		$html = "<div class='card card-custom card-stretch mt-4'>";
		$html .= "<div class='card-header py-3'><div class='card-title'><h3 class='card-label'>Solicitudes asociadas</h3></div></div>";
		$html .= "<div class='card-body p-0'>";

		if($solicitudes === false){
			$html .= "<div class='p-4 text-muted'>Este cliente no tiene solicitudes asociadas.</div>";
		}else{
			$html .= "<div class='table-responsive'>";
			$html .= "<table class='table table-sm table-striped mb-0'>";
			$html .= "<thead><tr><th>ID</th><th>Nombre</th><th>Estado</th><th>Fecha</th><th class='text-right'>Accion</th></tr></thead><tbody>";

			foreach($solicitudes->result() as $solicitud){
				$nombre = isset($solicitud->SOL_DS_NOMBRE) ? $solicitud->SOL_DS_NOMBRE : '-';
				$estado = isset($solicitud->ESO_DS_NAME) ? $solicitud->ESO_DS_NAME : '-';
				$fechaRaw = isset($solicitud->SOL_DT_CREATE) ? $solicitud->SOL_DT_CREATE : '';
				$fecha = '-';
				if(!empty($fechaRaw)){
					$timestamp = strtotime($fechaRaw);
					$fecha = $timestamp ? date('d-m-Y H:i', $timestamp) : '-';
				}

				$html .= "<tr>";
				$html .= "<td>#" . (int)$solicitud->SOL_CO_ID . "</td>";
				$html .= "<td>" . html_escape($nombre) . "</td>";
				$html .= "<td>" . html_escape($estado) . "</td>";
				$html .= "<td>" . html_escape($fecha) . "</td>";
				$html .= "<td class='text-right'><a href='javascript:;' class='btn btn-sm btn-light-primary buttonOpenSolicitud' data-sol-id='" . (int)$solicitud->SOL_CO_ID . "'>Abrir</a></td>";
				$html .= "</tr>";
			}

			$html .= "</tbody></table></div>";
		}

		$html .= "</div></div>";

		return $html;
	}
}

/* End of file cA004_cliente.php */
/* Location: ./application/controllers/admin/cA004_cliente.php */
?>
