<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CA003_solicitudes extends CI_Controller {

	private static $NAME 		= "cA003_solicitudes";
	private static $FORM 		= "form_solicitud";
	private static $PREFIX 		= "SOL";
	private static $CODE_DB 	= "SOL_CO_ID";
	private static $NAME_DB 	= "SOL_DS_NOMBRE";
	private static $ACTIVE_DB 	= "SOL_BL_ENABLE";
	private static $MODEL 		= "solicitudmodel";
	private static $COMPANY 	= "2º Opinión Radiológica";

	function __construct(){
		parent::__construct();
		$this->load->model(self::$MODEL);
		$this->load->model('usersmodel');
	}

	public function index()
	{
		if(($this->session->userdata('logged'))==TRUE && ($this->session->userdata('acceso'))>=100){
			$data = array(
				"content"				=> "admin/vA003_solicitudes.php"
				,"titulo" 				=> self::$COMPANY
				,"controller" 			=> self::$NAME
				,"javascriptMenu"		=>"$('#menuSolicitud').addClass('menu-item-active');"
				
			);
			$this->load->view('layout_admin',$data);
		}else{
			redirect('index.php/cerbero','refresh');
		}
	}

	public function getList(){
		$data = array();

		if($this->session->userdata('logged')!=TRUE || ($this->session->userdata('acceso'))<100){
			echo json_encode($data);
			return;
		}

		$elements = $this->{self::$MODEL}->getElementsList();
		if($elements != false){
			foreach ($elements->result() as $element) {
				$fechaSolicitud = '';
				if (!empty($element->SOL_DT_CREATE)) {
					$timestamp = strtotime($element->SOL_DT_CREATE);
					$fechaSolicitud = $timestamp ? date('d-m-Y H:i', $timestamp) : '';
				}

				$data[] = array(
								"RecordID"			=>intval($element->{self::$CODE_DB})
								,"Name"				=>$element->{self::$NAME_DB}
								,"State"			=>$element->ESO_DS_NAME
								,"Origin"			=>$element->FSO_DS_NAME
							,"DateRequest"		=>$fechaSolicitud
							);
			}
		}
		echo json_encode( array("meta"=>array("field"=>"RecordID"),"data"=>$data) );
	}

	public function newElement(){

		if($this->session->userdata('logged')!=TRUE || ($this->session->userdata('acceso'))<100){
			echo "";
			return;
		}
		//Cargamos el Formulario
		$nombreFichero= FCPATH. "application/views/admin/" . self::$FORM . ".php";

		$fichero = fopen($nombreFichero,"r");

		//Cargamos la plantilla del formulario
		$contenido = fread($fichero,filesize($nombreFichero));
		$field_map = $this->buildSolicitudFormFields();
		$contenido = str_replace(array_keys($field_map), array_values($field_map), $contenido);
		$contenido .= '';
		//Creamos el Formulario
		$formulario = form_open(base_url()."index.php/admin/" . self::$NAME . "/addElement",array("id"=>"formModalElement"));
		$formulario .= $contenido;
		$formulario .= form_close();

		echo $formulario;
	}

	public function addElement(){
		$resp = array();
		$_POST['ele_ped_importe'] = $this->normalizeImporteForValidation($this->input->post('ele_ped_importe', TRUE));

		$this->form_validation->set_rules('ele_name', 'name', 'required|trim|min_length[3]|xss_clean');
		$this->form_validation->set_rules('ele_fso_id', 'origen de la solicitud', 'required|integer');
		$this->form_validation->set_rules('ele_ped_importe', 'importe', 'required|decimal');
		$this->form_validation->set_rules('ele_ped_idioma', 'idioma preferido', 'required|trim|min_length[2]|max_length[5]|alpha_dash');
		
		$this->form_validation->set_message('required','Debes rellenar el campo '. ' %s');
		$this->form_validation->set_message('min_length', 'El campo %s debe tener al menos %s caracteres');

		if($this->form_validation->run()==FALSE){	

			//Metemos la lógica para usar From_validation con Ajax
			$errors = validation_errors();
			$resp = array(
				"status"=>"unsuccess"
				,"msg"=>$errors
				,"hash"=> 	$this->security->get_csrf_hash()
				,"token"=> 	$this->security->get_csrf_token_name()
			);
		}else{
			 // echo "CORRECTO!";
			$resp["status"]="success";
			
			//GUARDAMOS
			$activeElement = is_null( $this->input->post('ele_active') ) ? 0 : 1;
			
			$data = array(
				self::$PREFIX . "_DS_NOMBRE"			=>$this->input->post('ele_name', TRUE),
				"ESO_CO_ID"								=>1,
				"FSO_CO_ID"								=>$this->input->post('ele_fso_id', TRUE),
				self::$PREFIX . "_DS_ADQ_MAIL"			=>$this->input->post('ele_adq_mail', TRUE),
				self::$PREFIX . "_DS_ADQ_TELEFONO"		=>$this->input->post('ele_adq_phone', TRUE),
				self::$PREFIX . "_DS_ADQ_MOTIVO"		=>$this->input->post('ele_adq_reason', TRUE),
				self::$PREFIX . "_NM_IMPORTE"			=>$this->input->post('ele_ped_importe', TRUE),
				"IDI_CO_ISO"							=>$this->input->post('ele_ped_idioma', TRUE),
				self::$PREFIX . "_DS_SOLICITANTE_TIPO"	=>$this->input->post('ele_solicitante_tipo', TRUE),
				self::$PREFIX . "_DS_PAC_NOMBRE"		=>$this->input->post('ele_pac_nombre', TRUE),
				self::$PREFIX . "_DS_PAC_APELLIDO1"		=>$this->input->post('ele_pac_apellido1', TRUE),
				self::$PREFIX . "_DS_PAC_APELLIDO2"		=>$this->input->post('ele_pac_apellido2', TRUE),
				self::$PREFIX . "_DT_PAC_FECHA_NACIMIENTO"=>$this->input->post('ele_pac_fecha_nacimiento', TRUE),
				self::$PREFIX . "_DS_PAC_SEXO"			=>$this->input->post('ele_pac_sexo', TRUE),
				self::$PREFIX . "_DS_PAC_TIPO_DOCUMENTO"	=>$this->input->post('ele_pac_tipo_documento', TRUE),
				self::$PREFIX . "_DS_PAC_DOCUMENTO"		=>$this->input->post('ele_pac_documento', TRUE),
				self::$PREFIX . "_DS_PAC_PAIS"			=>$this->input->post('ele_pac_pais', TRUE),
				self::$PREFIX . "_DS_PAC_PROVINCIA"		=>$this->input->post('ele_pac_provincia', TRUE),
				self::$PREFIX . "_DS_PAC_POBLACION"		=>$this->input->post('ele_pac_poblacion', TRUE),
				self::$PREFIX . "_DS_PAC_DOMICILIO"		=>$this->input->post('ele_pac_domicilio', TRUE),
				self::$PREFIX . "_DS_PAC_COD_POSTAL"	=>$this->input->post('ele_pac_cp', TRUE),
				self::$PREFIX . "_DS_PAC_EMAIL"			=>$this->input->post('ele_pac_email', TRUE),
				self::$PREFIX . "_DS_PAC_TELEFONO"		=>$this->input->post('ele_pac_telefono', TRUE),
				self::$PREFIX . "_DS_TUT_NOMBRE"		=>$this->input->post('ele_tut_nombre', TRUE),
				self::$PREFIX . "_DS_TUT_APELLIDO1"		=>$this->input->post('ele_tut_apellido1', TRUE),
				self::$PREFIX . "_DS_TUT_APELLIDO2"		=>$this->input->post('ele_tut_apellido2', TRUE),
				self::$PREFIX . "_DT_TUT_FECHA_NACIMIENTO"=>$this->input->post('ele_tut_fecha_nacimiento', TRUE),
				self::$PREFIX . "_DS_TUT_SEXO"			=>$this->input->post('ele_tut_sexo', TRUE),
				self::$PREFIX . "_DS_TUT_TIPO_DOCUMENTO"	=>$this->input->post('ele_tut_tipo_documento', TRUE),
				self::$PREFIX . "_DS_TUT_DOCUMENTO"		=>$this->input->post('ele_tut_documento', TRUE),
				self::$PREFIX . "_DS_TUT_PAIS"			=>$this->input->post('ele_tut_pais', TRUE),
				self::$PREFIX . "_DS_TUT_PROVINCIA"		=>$this->input->post('ele_tut_provincia', TRUE),
				self::$PREFIX . "_DS_TUT_POBLACION"		=>$this->input->post('ele_tut_poblacion', TRUE),
				self::$PREFIX . "_DS_TUT_DOMICILIO"		=>$this->input->post('ele_tut_domicilio', TRUE),
				self::$PREFIX . "_DS_TUT_COD_POSTAL"	=>$this->input->post('ele_tut_cp', TRUE),
				self::$PREFIX . "_DS_TUT_EMAIL"			=>$this->input->post('ele_tut_email', TRUE),
				self::$PREFIX . "_DS_TUT_TELEFONO"		=>$this->input->post('ele_tut_telefono', TRUE),
				self::$PREFIX . "_DS_NOTAS"				=>$this->input->post('ele_notas', TRUE),
			);
			//verificamos que cuando guarden estén logueados
			if($this->session->userdata('logged')==TRUE && ($this->session->userdata('acceso'))>=100){
				$this->{self::$MODEL}->insertElement($data);
			}			
		}

		echo json_encode($resp);
	}

	public function editElement($id=0){
		if($this->session->userdata('logged')!=TRUE || $id==0 || ($this->session->userdata('acceso'))<100){
			echo "";
			return;
		}
		//Cargamos los datos de la etiqueta
		$cat = $this->{self::$MODEL}->getElementById($id);
		//Cargamos el Formulario
		$nombreFichero= FCPATH. "application/views/admin/" . self::$FORM . ".php";

		$fichero = fopen($nombreFichero,"r");

		//Cargamos la plantilla del formulario
		$contenido = fread($fichero,filesize($nombreFichero));
		$field_map = $this->buildSolicitudFormFields($cat, false, 'edit');
		$contenido = str_replace(array_keys($field_map), array_values($field_map), $contenido);
		$contenido .= '<script>
						jQuery(".selectDepartamento").select2({
		         			placeholder: "Seleccionar departamento"
		        		});
					 </script>';
		//Creamos el Formulario
		$formulario = form_open(base_url()."index.php/admin/" . self::$NAME . "/updateElement",array("id"=>"formModalElement"));
		$formulario .= $contenido;
		$formulario .= form_close();

		echo $formulario;
	}

	public function updateElement(){
		if($this->session->userdata('logged')!=TRUE || ($this->session->userdata('acceso'))<100){
			echo "";
			return;
		}
		$resp = array();
		$_POST['ele_ped_importe'] = $this->normalizeImporteForValidation($this->input->post('ele_ped_importe', TRUE));

		$this->form_validation->set_rules('ele_name', 'nombre', 'required|trim|min_length[3]|xss_clean');
		$this->form_validation->set_rules('ele_fso_id', 'origen de la solicitud', 'required|integer');
		$this->form_validation->set_rules('ele_ped_importe', 'importe', 'required|decimal');
		$this->form_validation->set_rules('ele_ped_idioma', 'idioma preferido', 'required|trim|min_length[2]|max_length[5]|alpha_dash');

		$this->form_validation->set_message('required','Debes rellenar el campo '. ' %s');
		$this->form_validation->set_message('min_length', 'El campo %s debe tener al menos %s caracteres');

		if($this->form_validation->run()==FALSE){	

			//Metemos la lógica para usar From_validation con Ajax
			$errors = validation_errors();
			$resp = array(
				"status"=>"unsuccess"
				,"msg"=>$errors
				,"hash"=> 	$this->security->get_csrf_hash()
				,"token"=> 	$this->security->get_csrf_token_name()
			);
		}else{
			 // echo "CORRECTO!";
			$resp["status"]="success";
			
			//GUARDAMOS
			$activeElement = is_null( $this->input->post('ele_active') ) ? 0 : 1;
			
			$data = array(
				self::$PREFIX . "_DS_NOMBRE"			=>$this->input->post('ele_name', TRUE),
				"FSO_CO_ID"								=>$this->input->post('ele_fso_id', TRUE),
				self::$PREFIX . "_DS_ADQ_MAIL"			=>$this->input->post('ele_adq_mail', TRUE),
				self::$PREFIX . "_DS_ADQ_TELEFONO"		=>$this->input->post('ele_adq_phone', TRUE),
				self::$PREFIX . "_DS_ADQ_MOTIVO"		=>$this->input->post('ele_adq_reason', TRUE),
				self::$PREFIX . "_NM_IMPORTE"			=>$this->input->post('ele_ped_importe', TRUE),
				"IDI_CO_ISO"							=>$this->input->post('ele_ped_idioma', TRUE),
				self::$PREFIX . "_DS_NOTAS"			=>$this->input->post('ele_notas', TRUE),
				self::$PREFIX . "_DS_SOLICITANTE_TIPO"	=>$this->input->post('ele_solicitante_tipo', TRUE),
				self::$PREFIX . "_DS_PAC_NOMBRE"		=>$this->input->post('ele_pac_nombre', TRUE),
				self::$PREFIX . "_DS_PAC_APELLIDO1"		=>$this->input->post('ele_pac_apellido1', TRUE),
				self::$PREFIX . "_DS_PAC_APELLIDO2"		=>$this->input->post('ele_pac_apellido2', TRUE),
				self::$PREFIX . "_DT_PAC_FECHA_NACIMIENTO"=>$this->input->post('ele_pac_fecha_nacimiento', TRUE),
				self::$PREFIX . "_DS_PAC_SEXO"			=>$this->input->post('ele_pac_sexo', TRUE),
				self::$PREFIX . "_DS_PAC_TIPO_DOCUMENTO"	=>$this->input->post('ele_pac_tipo_documento', TRUE),
				self::$PREFIX . "_DS_PAC_DOCUMENTO"		=>$this->input->post('ele_pac_documento', TRUE),
				self::$PREFIX . "_DS_PAC_PAIS"			=>$this->input->post('ele_pac_pais', TRUE),
				self::$PREFIX . "_DS_PAC_PROVINCIA"		=>$this->input->post('ele_pac_provincia', TRUE),
				self::$PREFIX . "_DS_PAC_POBLACION"		=>$this->input->post('ele_pac_poblacion', TRUE),
				self::$PREFIX . "_DS_PAC_DOMICILIO"		=>$this->input->post('ele_pac_domicilio', TRUE),
				self::$PREFIX . "_DS_PAC_COD_POSTAL"	=>$this->input->post('ele_pac_cp', TRUE),
				self::$PREFIX . "_DS_PAC_EMAIL"			=>$this->input->post('ele_pac_email', TRUE),
				self::$PREFIX . "_DS_PAC_TELEFONO"		=>$this->input->post('ele_pac_telefono', TRUE),
				self::$PREFIX . "_DS_TUT_NOMBRE"		=>$this->input->post('ele_tut_nombre', TRUE),
				self::$PREFIX . "_DS_TUT_APELLIDO1"		=>$this->input->post('ele_tut_apellido1', TRUE),
				self::$PREFIX . "_DS_TUT_APELLIDO2"		=>$this->input->post('ele_tut_apellido2', TRUE),
				self::$PREFIX . "_DT_TUT_FECHA_NACIMIENTO"=>$this->input->post('ele_tut_fecha_nacimiento', TRUE),
				self::$PREFIX . "_DS_TUT_SEXO"			=>$this->input->post('ele_tut_sexo', TRUE),
				self::$PREFIX . "_DS_TUT_TIPO_DOCUMENTO"	=>$this->input->post('ele_tut_tipo_documento', TRUE),
				self::$PREFIX . "_DS_TUT_DOCUMENTO"		=>$this->input->post('ele_tut_documento', TRUE),
				self::$PREFIX . "_DS_TUT_PAIS"			=>$this->input->post('ele_tut_pais', TRUE),
				self::$PREFIX . "_DS_TUT_PROVINCIA"		=>$this->input->post('ele_tut_provincia', TRUE),
				self::$PREFIX . "_DS_TUT_POBLACION"		=>$this->input->post('ele_tut_poblacion', TRUE),
				self::$PREFIX . "_DS_TUT_DOMICILIO"		=>$this->input->post('ele_tut_domicilio', TRUE),
				self::$PREFIX . "_DS_TUT_COD_POSTAL"	=>$this->input->post('ele_tut_cp', TRUE),
				self::$PREFIX . "_DS_TUT_EMAIL"			=>$this->input->post('ele_tut_email', TRUE),
				self::$PREFIX . "_DS_TUT_TELEFONO"		=>$this->input->post('ele_tut_telefono', TRUE)
			);
			//verificamos que cuando guarden estén logueados
			if($this->session->userdata('logged')==TRUE && ($this->session->userdata('acceso'))>=100){
				$this->{self::$MODEL}->updateElement($data,$this->input->post('ele_id'));
			}			
		}

		echo json_encode($resp);
	}

	public function deleteElement($id=0){
		if($this->session->userdata('logged')!=TRUE || $id==0 || ($this->session->userdata('acceso'))<100){
			echo "";
			return;
		}
		//Cargamos los datos de la etiqueta
		$cat = $this->{self::$MODEL}->getElementById($id);
		//Cargamos el Formulario
		$nombreFichero= FCPATH. "application/views/admin/" . self::$FORM . ".php";

		$fichero = fopen($nombreFichero,"r");

		//Cargamos la plantilla del formulario
		$contenido = fread($fichero,filesize($nombreFichero));
		$field_map = $this->buildSolicitudFormFields($cat, true, 'delete');
		$contenido = str_replace(array_keys($field_map), array_values($field_map), $contenido);
		$contenido .= '<script>
					 </script>';
		//Creamos el Formulario
		$formulario = form_open(base_url()."index.php/admin/" . self::$NAME . "/deleteConfirm",array("id"=>"formModalElement"));
		$formulario .= $contenido;
		$formulario .= form_close();

		echo $formulario;
	}

	public function deleteConfirm(){
		if($this->session->userdata('logged')!=TRUE || ($this->session->userdata('acceso'))<100){
			echo "";
			return;
		}
		$resp = array();


		$resp["status"]="success";
		
		//GUARDAMOS
		
		$data = array(
			self::$PREFIX . "_BL_DELETE"=>1
		);
		//verificamos que cuando guarden estén logueados
		if($this->session->userdata('logged')==TRUE && ($this->session->userdata('acceso'))>=100){
			$this->{self::$MODEL}->updateElement($data,$this->input->post('ele_id'));
		}			
		

		echo json_encode($resp);
	}

	public function solicitarPago(){
		if($this->session->userdata('logged')!=TRUE || ($this->session->userdata('acceso'))<100){
			echo "";
			return;
		}

		$resp = array();
		$_POST['ele_ped_importe'] = $this->normalizeImporteForValidation($this->input->post('ele_ped_importe', TRUE));

		$this->form_validation->set_rules('ele_id', 'solicitud', 'required|integer');
		$this->form_validation->set_rules('ele_ped_importe', 'importe', 'required|decimal');
		$this->form_validation->set_rules('ele_ped_idioma', 'idioma preferido', 'required|trim|min_length[2]|max_length[5]|alpha_dash');

		$this->form_validation->set_message('required','Debes rellenar el campo '. ' %s');

		if($this->form_validation->run()==FALSE){
			$resp = array(
				"status"=>"unsuccess"
				,"msg"=>validation_errors()
				,"hash"=> 	$this->security->get_csrf_hash()
				,"token"=> 	$this->security->get_csrf_token_name()
			);
			echo json_encode($resp);
			return;
		}

		$id = (int)$this->input->post('ele_id', TRUE);
		$solicitud = $this->{self::$MODEL}->getElementById($id);

		if($solicitud === false){
			$resp = array(
				"status"=>"unsuccess"
				,"msg"=>"La solicitud indicada no existe"
				,"hash"=> 	$this->security->get_csrf_hash()
				,"token"=> 	$this->security->get_csrf_token_name()
			);
			echo json_encode($resp);
			return;
		}

		$importe = $this->input->post('ele_ped_importe', TRUE);
		$idiomaIso = $this->input->post('ele_ped_idioma', TRUE);

		$updateData = array(
			self::$PREFIX . "_NM_IMPORTE" => $importe,
			"IDI_CO_ISO" => $idiomaIso,
		);
		$this->{self::$MODEL}->updateElement($updateData, $id);

		$to = trim((string)$this->input->post('ele_adq_mail', TRUE));
		if($to === '' && isset($solicitud->{self::$PREFIX . '_DS_ADQ_MAIL'})){
			$to = trim((string)$solicitud->{self::$PREFIX . '_DS_ADQ_MAIL'});
		}
		if($to === '' && isset($solicitud->{self::$PREFIX . '_DS_PAC_EMAIL'})){
			$to = trim((string)$solicitud->{self::$PREFIX . '_DS_PAC_EMAIL'});
		}

		if($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)){
			$resp = array(
				"status"=>"unsuccess"
				,"msg"=>"No hay un email de cliente valido para enviar la solicitud de pago"
				,"hash"=> 	$this->security->get_csrf_hash()
				,"token"=> 	$this->security->get_csrf_token_name()
			);
			echo json_encode($resp);
			return;
		}

		$this->load->library('Emailtemplate');
		$userCreated = $this->ensureClientUserForPayment($to, $this->input->post('ele_name', TRUE));
		if ($userCreated['status'] === 'unsuccess') {
			$resp = array(
				"status"=>"unsuccess"
				,"msg"=>$userCreated['msg']
				,"hash"=> 	$this->security->get_csrf_hash()
				,"token"=> 	$this->security->get_csrf_token_name()
			);
			echo json_encode($resp);
			return;
		}

		$nombreIdioma = $this->getIdiomaNombreByIso($idiomaIso);
		$payload = array(
			'nombre' => $this->input->post('ele_name', TRUE),
			'request_code' => $id,
			'importe' => $this->formatImporteForMail($importe),
			'url_acceso' => site_url('usuarios'),
			'observaciones' => 'Idioma preferido: ' . $nombreIdioma,
		);

		$sent = $this->emailtemplate->sendSolicitudPago($to, $payload, array(
			'from_email' => EMAIL_CONTACT,
			'from_name' => 'Portal 2OP',
			'reply_to' => EMAIL_REPLY,
		));

		if(!$sent){
			$resp = array(
				"status"=>"unsuccess"
				,"msg"=>"Se guardaron importe e idioma, pero no se pudo enviar el email de pago"
				,"hash"=> 	$this->security->get_csrf_hash()
				,"token"=> 	$this->security->get_csrf_token_name()
			);
			echo json_encode($resp);
			return;
		}

		$estadoData = array(
			'ESO_CO_ID' => 2,
		);
		if ($this->hasPagoRequestDateColumn()) {
			$estadoData['SOL_DT_PAGO_SOLICITADO'] = date('Y-m-d H:i:s');
		}
		$this->{self::$MODEL}->updateElement($estadoData, $id);

		$resp = array(
			"status"=>"success"
			,"msg"=>"Solicitud de pago enviada correctamente"
			,"hash"=> 	$this->security->get_csrf_hash()
			,"token"=> 	$this->security->get_csrf_token_name()
		);

		echo json_encode($resp);
	}

	private function buildSolicitudFormFields($cat = null, $disabled = false, $action = 'add'){
		$disabled = false;

		$get_value = function($field, $default = '') use ($cat){
			if (is_object($cat) && isset($cat->{$field}) && !is_null($cat->{$field})) {
				return $cat->{$field};
			}
			return $default;
		};

		$input_class = 'form-control';
		$disabled_attr = $disabled ? ' disabled="disabled"' : '';

		$sexo_options = array(
			'' => 'Seleccionar',
			'M' => 'Masculino',
			'F' => 'Femenino',
			'O' => 'Otro'
		);

		$tipo_documento_options = array(
			'' => 'Seleccionar',
			'DNI' => 'DNI',
			'NIE' => 'NIE',
			'PASAPORTE' => 'Pasaporte'
		);

		$solicitante_options = array(
			'PACIENTE' => 'Paciente',
			'TUTOR' => 'Tutor'
		);

		$fso_options = array('' => 'Seleccionar origen');
		$fuentes = $this->{self::$MODEL}->getFuentesSolicitudActivas();
		if($fuentes !== false){
			foreach($fuentes->result() as $fuente){
				$fso_options[$fuente->FSO_CO_ID] = $fuente->FSO_DS_NAME;
			}
		}

		$idioma_options = array('' => 'Seleccionar idioma');
		$idiomas = $this->{self::$MODEL}->getIdiomasActivos();
		if($idiomas !== false){
			foreach($idiomas->result() as $idioma){
				$idioma_options[$idioma->IDI_CO_ISO] = $idioma->IDI_DS_NOMBRE;
			}
		}

		$estado_nombre = $get_value('ESO_DS_NAME', 'Leed');
		$fecha_pago_solicitud = 'Pendiente solicitud pago';
		$fecha_pago_raw = $get_value('SOL_DT_PAGO_SOLICITADO', '');
		if(!empty($fecha_pago_raw)){
			$fecha_pago_solicitud = $this->formatDateTimeLabel($fecha_pago_raw);
		}

		$field_map = array(
			'@FIELD_ID' => form_hidden('ele_id', $get_value(self::$CODE_DB, '0')),
			'@FIELD_ACTION' => form_hidden('action', $action),
			'@FIELD_NAME' => form_input(array('name'=>'ele_name', 'id'=>'ele_name','class'=>$input_class,'placeholder'=>'Nombre', 'value'=>$get_value(self::$NAME_DB), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_LEED_FORM' => form_input(array('name'=>'ele_leed_form_view', 'id'=>'ele_leed_form','class'=>$input_class, 'value'=>$estado_nombre, 'disabled'=>'disabled')),
			'@FIELD_ORIGEN_SOLICITUD' => form_dropdown('ele_fso_id', $fso_options, $get_value('FSO_CO_ID'), 'id="ele_fso_id" class="' . $input_class . '"' . $disabled_attr),
			'@FIELD_ADQ_MAIL' => form_input(array('name'=>'ele_adq_mail', 'id'=>'ele_adq_mail','class'=>$input_class,'placeholder'=>'Mail', 'value'=>$get_value(self::$PREFIX . '_DS_ADQ_MAIL'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_ADQ_PHONE' => form_input(array('name'=>'ele_adq_phone', 'id'=>'ele_adq_phone','class'=>$input_class,'placeholder'=>'Teléfono', 'value'=>$get_value(self::$PREFIX . '_DS_ADQ_TELEFONO'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_ADQ_REASON' => form_textarea(array('name'=>'ele_adq_reason', 'id'=>'ele_adq_reason','class'=>$input_class,'rows'=>3,'placeholder'=>'Motivo', 'value'=>$get_value(self::$PREFIX . '_DS_ADQ_MOTIVO'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_PED_IMPORTE' => form_input(array('name'=>'ele_ped_importe', 'id'=>'ele_ped_importe','class'=>$input_class,'type'=>'text','inputmode'=>'decimal', 'placeholder'=>'0.00', 'value'=>$get_value(self::$PREFIX . '_NM_IMPORTE'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_PED_IDIOMA' => form_dropdown('ele_ped_idioma', $idioma_options, $get_value('IDI_CO_ISO'), 'id="ele_ped_idioma" class="' . $input_class . '"' . $disabled_attr),
			'@FIELD_PAGO_FECHA_SOLICITUD' => form_input(array('name'=>'ele_pago_fecha_solicitud', 'id'=>'ele_pago_fecha_solicitud','class'=>$input_class,'type'=>'text', 'value'=>$fecha_pago_solicitud, 'readonly'=>'readonly')),
			'@FIELD_SOLICITAR_PAGO' => ($action === 'edit')
				? '<button type="button" class="btn btn-warning" id="buttonSolicitarPago">Solicitar pago</button>'
				: '',
			'@FIELD_NOTAS' => form_textarea(array('name'=>'ele_notas', 'id'=>'ele_notas','class'=>$input_class,'rows'=>4,'placeholder'=>'Notas internas', 'value'=>$get_value(self::$PREFIX . '_DS_NOTAS'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_SOLICITANTE_TIPO' => form_dropdown('ele_solicitante_tipo', $solicitante_options, $get_value(self::$PREFIX . '_DS_SOLICITANTE_TIPO', 'PACIENTE'), 'id="ele_solicitante_tipo" class="' . $input_class . '"' . $disabled_attr),
			'@FIELD_PAC_NOMBRE' => form_input(array('name'=>'ele_pac_nombre', 'id'=>'ele_pac_nombre','class'=>$input_class,'placeholder'=>'Nombre', 'value'=>$get_value(self::$PREFIX . '_DS_PAC_NOMBRE'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_PAC_APELLIDO1' => form_input(array('name'=>'ele_pac_apellido1', 'id'=>'ele_pac_apellido1','class'=>$input_class,'placeholder'=>'Primer apellido', 'value'=>$get_value(self::$PREFIX . '_DS_PAC_APELLIDO1'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_PAC_APELLIDO2' => form_input(array('name'=>'ele_pac_apellido2', 'id'=>'ele_pac_apellido2','class'=>$input_class,'placeholder'=>'Segundo apellido', 'value'=>$get_value(self::$PREFIX . '_DS_PAC_APELLIDO2'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_PAC_FECHA_NACIMIENTO' => form_input(array('name'=>'ele_pac_fecha_nacimiento', 'id'=>'ele_pac_fecha_nacimiento','class'=>$input_class,'type'=>'date', 'value'=>$get_value(self::$PREFIX . '_DT_PAC_FECHA_NACIMIENTO'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_PAC_SEXO' => form_dropdown('ele_pac_sexo', $sexo_options, $get_value(self::$PREFIX . '_DS_PAC_SEXO'), 'id="ele_pac_sexo" class="' . $input_class . '"' . $disabled_attr),
			'@FIELD_PAC_TIPO_DOCUMENTO' => form_dropdown('ele_pac_tipo_documento', $tipo_documento_options, $get_value(self::$PREFIX . '_DS_PAC_TIPO_DOCUMENTO'), 'id="ele_pac_tipo_documento" class="' . $input_class . '"' . $disabled_attr),
			'@FIELD_PAC_DOCUMENTO' => form_input(array('name'=>'ele_pac_documento', 'id'=>'ele_pac_documento','class'=>$input_class,'placeholder'=>'Documento', 'value'=>$get_value(self::$PREFIX . '_DS_PAC_DOCUMENTO'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_PAC_PAIS' => form_input(array('name'=>'ele_pac_pais', 'id'=>'ele_pac_pais','class'=>$input_class,'placeholder'=>'Pais', 'value'=>$get_value(self::$PREFIX . '_DS_PAC_PAIS'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_PAC_PROVINCIA' => form_input(array('name'=>'ele_pac_provincia', 'id'=>'ele_pac_provincia','class'=>$input_class,'placeholder'=>'Provincia', 'value'=>$get_value(self::$PREFIX . '_DS_PAC_PROVINCIA'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_PAC_POBLACION' => form_input(array('name'=>'ele_pac_poblacion', 'id'=>'ele_pac_poblacion','class'=>$input_class,'placeholder'=>'Población', 'value'=>$get_value(self::$PREFIX . '_DS_PAC_POBLACION'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_PAC_DOMICILIO' => form_input(array('name'=>'ele_pac_domicilio', 'id'=>'ele_pac_domicilio','class'=>$input_class,'placeholder'=>'Domicilio', 'value'=>$get_value(self::$PREFIX . '_DS_PAC_DOMICILIO'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_PAC_CP' => form_input(array('name'=>'ele_pac_cp', 'id'=>'ele_pac_cp','class'=>$input_class,'placeholder'=>'Cod. postal', 'value'=>$get_value(self::$PREFIX . '_DS_PAC_COD_POSTAL'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_PAC_EMAIL' => form_input(array('name'=>'ele_pac_email', 'id'=>'ele_pac_email','class'=>$input_class,'placeholder'=>'Correo', 'value'=>$get_value(self::$PREFIX . '_DS_PAC_EMAIL'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_PAC_TELEFONO' => form_input(array('name'=>'ele_pac_telefono', 'id'=>'ele_pac_telefono','class'=>$input_class,'placeholder'=>'Teléfono', 'value'=>$get_value(self::$PREFIX . '_DS_PAC_TELEFONO'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_TUT_NOMBRE' => form_input(array('name'=>'ele_tut_nombre', 'id'=>'ele_tut_nombre','class'=>$input_class,'placeholder'=>'Nombre', 'value'=>$get_value(self::$PREFIX . '_DS_TUT_NOMBRE'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_TUT_APELLIDO1' => form_input(array('name'=>'ele_tut_apellido1', 'id'=>'ele_tut_apellido1','class'=>$input_class,'placeholder'=>'Primer apellido', 'value'=>$get_value(self::$PREFIX . '_DS_TUT_APELLIDO1'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_TUT_APELLIDO2' => form_input(array('name'=>'ele_tut_apellido2', 'id'=>'ele_tut_apellido2','class'=>$input_class,'placeholder'=>'Segundo apellido', 'value'=>$get_value(self::$PREFIX . '_DS_TUT_APELLIDO2'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_TUT_FECHA_NACIMIENTO' => form_input(array('name'=>'ele_tut_fecha_nacimiento', 'id'=>'ele_tut_fecha_nacimiento','class'=>$input_class,'type'=>'date', 'value'=>$get_value(self::$PREFIX . '_DT_TUT_FECHA_NACIMIENTO'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_TUT_SEXO' => form_dropdown('ele_tut_sexo', $sexo_options, $get_value(self::$PREFIX . '_DS_TUT_SEXO'), 'id="ele_tut_sexo" class="' . $input_class . '"' . $disabled_attr),
			'@FIELD_TUT_TIPO_DOCUMENTO' => form_dropdown('ele_tut_tipo_documento', $tipo_documento_options, $get_value(self::$PREFIX . '_DS_TUT_TIPO_DOCUMENTO'), 'id="ele_tut_tipo_documento" class="' . $input_class . '"' . $disabled_attr),
			'@FIELD_TUT_DOCUMENTO' => form_input(array('name'=>'ele_tut_documento', 'id'=>'ele_tut_documento','class'=>$input_class,'placeholder'=>'Documento', 'value'=>$get_value(self::$PREFIX . '_DS_TUT_DOCUMENTO'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_TUT_PAIS' => form_input(array('name'=>'ele_tut_pais', 'id'=>'ele_tut_pais','class'=>$input_class,'placeholder'=>'Pais', 'value'=>$get_value(self::$PREFIX . '_DS_TUT_PAIS'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_TUT_PROVINCIA' => form_input(array('name'=>'ele_tut_provincia', 'id'=>'ele_tut_provincia','class'=>$input_class,'placeholder'=>'Provincia', 'value'=>$get_value(self::$PREFIX . '_DS_TUT_PROVINCIA'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_TUT_POBLACION' => form_input(array('name'=>'ele_tut_poblacion', 'id'=>'ele_tut_poblacion','class'=>$input_class,'placeholder'=>'Población', 'value'=>$get_value(self::$PREFIX . '_DS_TUT_POBLACION'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_TUT_DOMICILIO' => form_input(array('name'=>'ele_tut_domicilio', 'id'=>'ele_tut_domicilio','class'=>$input_class,'placeholder'=>'Domicilio', 'value'=>$get_value(self::$PREFIX . '_DS_TUT_DOMICILIO'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_TUT_CP' => form_input(array('name'=>'ele_tut_cp', 'id'=>'ele_tut_cp','class'=>$input_class,'placeholder'=>'Cod. postal', 'value'=>$get_value(self::$PREFIX . '_DS_TUT_COD_POSTAL'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_TUT_EMAIL' => form_input(array('name'=>'ele_tut_email', 'id'=>'ele_tut_email','class'=>$input_class,'placeholder'=>'Correo', 'value'=>$get_value(self::$PREFIX . '_DS_TUT_EMAIL'), 'disabled'=>$disabled ? 'disabled' : null)),
			'@FIELD_TUT_TELEFONO' => form_input(array('name'=>'ele_tut_telefono', 'id'=>'ele_tut_telefono','class'=>$input_class,'placeholder'=>'Teléfono', 'value'=>$get_value(self::$PREFIX . '_DS_TUT_TELEFONO'), 'disabled'=>$disabled ? 'disabled' : null))
		);

		if($action !== 'delete'){
			foreach($field_map as $field_key => $field_html){
				if($field_key === '@FIELD_LEED_FORM'){
					continue;
				}
				$field_map[$field_key] = str_replace(array(' disabled="disabled"', ' disabled=""'), '', $field_html);
			}
		}

		return $field_map;
	}

	private function normalizeImporteForValidation($value){
		if (is_null($value)) {
			return '';
		}

		$value = trim((string)$value);
		if ($value === '') {
			return '';
		}

		$value = str_replace(' ', '', $value);
		$value = str_replace(',', '.', $value);

		if (!preg_match('/^\d+(\.\d+)?$/', $value)) {
			return $value;
		}

		if (strpos($value, '.') === false) {
			return $value . '.00';
		}

		list($intPart, $decPart) = explode('.', $value, 2);
		if ($decPart === '') {
			return $intPart . '.00';
		}

		if (strlen($decPart) === 1) {
			return $intPart . '.' . $decPart . '0';
		}

		return $intPart . '.' . substr($decPart, 0, 2);
	}

	private function getIdiomaNombreByIso($iso){
		$iso = strtoupper(trim((string)$iso));
		if($iso === ''){
			return '-';
		}

		$idiomas = $this->{self::$MODEL}->getIdiomasActivos();
		if($idiomas !== false){
			foreach($idiomas->result() as $idioma){
				if(strtoupper((string)$idioma->IDI_CO_ISO) === $iso){
					return $idioma->IDI_DS_NOMBRE;
				}
			}
		}

		return $iso;
	}

	private function formatImporteForMail($importe){
		$numeric = (float)$importe;
		return number_format($numeric, 2, ',', '.') . ' EUR';
	}

	private function hasPagoRequestDateColumn(){
		return $this->db->field_exists('SOL_DT_PAGO_SOLICITADO', 't_sol_solicitudes');
	}

	private function ensureClientUserForPayment($email, $name){
		$existingUser = $this->usersmodel->getUserByMail($email);
		if ($existingUser !== false) {
			return array('status' => 'success', 'created' => false);
		}

		$passwordPlain = $this->generateTemporaryPassword();
		$insertData = array(
			'USR_DS_LOGIN' => $email,
			'USR_DS_MAIL' => $email,
			'USR_DS_PASSWORD' => password_hash($passwordPlain, PASSWORD_DEFAULT, array('cost'=>10)),
			'USR_DS_NOMBRE' => trim((string)$name) !== '' ? $name : 'Usuario',
			'USR_DS_APELLIDOS' => '',
			'PER_CO_ID' => 4,
			'USR_BL_ACEPTADO' => 1,
		);

		$userId = $this->usersmodel->insertUser($insertData);
		if (!$userId) {
			return array('status' => 'unsuccess', 'msg' => 'No se pudo crear el usuario del cliente');
		}

		$payload = array(
			'nombre' => $insertData['USR_DS_NOMBRE'],
			'usuario' => $email,
			'email' => $email,
			'password' => $passwordPlain,
			'url_acceso' => site_url('usuarios'),
		);

		$sent = $this->emailtemplate->sendAltaUsuario($email, $payload, array(
			'from_email' => EMAIL_CONTACT,
			'from_name' => 'Portal 2OP',
			'reply_to' => EMAIL_REPLY,
		));

		if (!$sent) {
			return array('status' => 'unsuccess', 'msg' => 'Se creo el usuario pero no se pudo enviar el email de alta');
		}

		return array('status' => 'success', 'created' => true);
	}

	private function generateTemporaryPassword($length = 10){
		$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789@#$%';
		$max = strlen($alphabet) - 1;
		$password = '';

		for ($i = 0; $i < $length; $i++) {
			$password .= $alphabet[$this->getRandomIndex($max)];
		}

		return $password;
	}

	private function getRandomIndex($max){
		if (function_exists('random_int')) {
			return random_int(0, $max);
		}

		if (function_exists('openssl_random_pseudo_bytes')) {
			$byte = openssl_random_pseudo_bytes(1);
			if ($byte !== false) {
				return ord($byte) % ($max + 1);
			}
		}

		return mt_rand(0, $max);
	}

	private function formatDateTimeLabel($dateTimeRaw){
		$timestamp = strtotime((string)$dateTimeRaw);
		if($timestamp === false){
			return 'Pendiente solicitud pago';
		}

		return date('d-m-Y H:i', $timestamp);
	}

}

/* End of file CA003_solicitudes.php */
/* Location: ./application/controllers/admin/CA003_solicitudes.php */