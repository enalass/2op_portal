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
	private static $PACS_DEFAULT_HOST = 'segundaopinionradiologica.actualpacs.com';
	private static $PACS_DEFAULT_PORT = 104;
	private static $PACS_DEFAULT_CALLED_AET = 'ANY-SCP';
	private static $PACS_DEFAULT_CALLING_AET = 'ANY-SCU';

	function __construct(){
		parent::__construct();
		$this->load->model(self::$MODEL);
		$this->load->model('usersmodel');
		$this->load->model('Solicitudarchivosmodel');
	}

	public function index()
	{
		if(($this->session->userdata('logged'))==TRUE && ($this->session->userdata('acceso'))>=100){
			$estadosSolicitud = array();
			$estados = $this->{self::$MODEL}->getEstadosSolicitudActivos();
			if($estados !== false){
				foreach($estados->result() as $estado){
					$estadosSolicitud[] = array(
						'id' => (int)$estado->ESO_CO_ID,
						'name' => (string)$estado->ESO_DS_NAME,
					);
				}
			}

			$data = array(
				"content"				=> "admin/vA003_solicitudes.php"
				,"titulo" 				=> self::$COMPANY
				,"controller" 			=> self::$NAME
				,"javascriptMenu"		=>"$('#menuSolicitud').addClass('menu-item-active');"
				,"estadosSolicitud" => $estadosSolicitud
				
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
				$estadoId = (int)$element->ESO_CO_ID;
				$stateBucket = 'otro';
				if($estadoId === 1){
					$stateBucket = 'lead';
				}else if($estadoId === 2){
					$stateBucket = 'solicitado';
				}else if($estadoId === 6){
					$stateBucket = 'estudio_subido';
				}else if($estadoId === 8){
					$stateBucket = 'finalizado';
				}

				$fechaSolicitud = '';
				if (!empty($element->SOL_DT_CREATE)) {
					$timestamp = strtotime($element->SOL_DT_CREATE);
					$fechaSolicitud = $timestamp ? date('d-m-Y H:i', $timestamp) : '';
				}

				$data[] = array(
								"RecordID"			=>intval($element->{self::$CODE_DB})
							,"StateId"			=>(int)$element->ESO_CO_ID
							,"StateBucket"		=>$stateBucket
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

	public function viewElement($id=0){
		if($this->session->userdata('logged')!=TRUE || $id==0 || ($this->session->userdata('acceso'))<100){
			redirect('index.php/cerbero','refresh');
			return;
		}

		$cat = $this->{self::$MODEL}->getElementById($id);
		if($cat === false){
			redirect('index.php/admin/' . self::$NAME,'refresh');
			return;
		}

		$nombreFichero= FCPATH . "application/views/admin/" . self::$FORM . ".php";
		$fichero = fopen($nombreFichero,"r");
		$contenido = fread($fichero,filesize($nombreFichero));
		fclose($fichero);

		$field_map = $this->buildSolicitudFormFields($cat, false, 'edit');
		$contenido = str_replace(array_keys($field_map), array_values($field_map), $contenido);

		$formulario = form_open(base_url()."index.php/admin/" . self::$NAME . "/updateElement",array("id"=>"formModalElement"));
		$formulario .= $contenido;
		$formulario .= form_close();

		$data = array(
			"content" => "admin/vA003_solicitudes_view.php",
			"titulo" => self::$COMPANY,
			"controller" => self::$NAME,
			"javascriptMenu" => "$('#menuSolicitud').addClass('menu-item-active');",
			"solicitudId" => (int)$id,
			"formSolicitud" => $formulario,
		);

		$this->load->view('layout_admin',$data);
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

		if (!empty($userCreated['user_id']) && $this->hasSolicitudClientColumn()) {
			$this->{self::$MODEL}->updateElement(array('USR_CO_ID' => (int)$userCreated['user_id']), $id);
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

	public function processDicomBatch(){
		if($this->session->userdata('logged')!=TRUE || ($this->session->userdata('acceso'))<100){
			echo "";
			return;
		}

		$solicitudId = (int)$this->input->post('ele_id', TRUE);
		if($solicitudId <= 0){
			$this->jsonDicomProcessResponse('unsuccess', 'Solicitud invalida', array());
			return;
		}

		$solicitud = $this->{self::$MODEL}->getElementById($solicitudId);
		if($solicitud === false){
			$this->jsonDicomProcessResponse('unsuccess', 'La solicitud indicada no existe', array());
			return;
		}

		$estadoSolicitud = isset($solicitud->ESO_CO_ID) ? (int)$solicitud->ESO_CO_ID : 0;
		if($estadoSolicitud < 5){
			$this->jsonDicomProcessResponse('unsuccess', 'La solicitud aun no esta disponible para procesado DICOM', array());
			return;
		}

		if(!$this->Solicitudarchivosmodel->canUse()){
			$this->jsonDicomProcessResponse('unsuccess', 'No se puede procesar porque falta la tabla de archivos de solicitud', array());
			return;
		}

		$this->Solicitudarchivosmodel->ensurePacsColumns();

		$progressBefore = $this->Solicitudarchivosmodel->getPacsProgressBySolicitud($solicitudId);
		if($progressBefore === false || (int)$progressBefore['total'] <= 0){
			$this->jsonDicomProcessResponse('unsuccess', 'No hay archivos para procesar en esta solicitud', array());
			return;
		}

		$pacsConfig = $this->getPacsConfig();
		if($pacsConfig['host'] === '' || $pacsConfig['port'] <= 0){
			$this->jsonDicomProcessResponse(
				'unsuccess',
				'Configuracion PACS incompleta. Define host y puerto en el controlador antes de procesar.',
				array('progress' => $progressBefore)
			);
			return;
		}

		$connectivity = $this->checkPacsConnectivity($pacsConfig['host'], $pacsConfig['port']);
		if(!$connectivity['success']){
			$this->jsonDicomProcessResponse(
				'unsuccess',
				'No hay conectividad con PACS (' . $pacsConfig['host'] . ':' . $pacsConfig['port'] . '). ' . $connectivity['error'],
				array('progress' => $progressBefore)
			);
			return;
		}

		$nextFile = $this->Solicitudarchivosmodel->getNextPendingArchivoBySolicitud($solicitudId);
		if($nextFile === false){
			$this->markSolicitudAsProcessedIfComplete($solicitudId);
			$progressDone = $this->Solicitudarchivosmodel->getPacsProgressBySolicitud($solicitudId);
			$this->jsonDicomProcessResponse('success', 'Procesado completado', array(
				'done' => true,
				'last' => null,
				'progress' => $progressDone,
			));
			return;
		}

		$archivoId = isset($nextFile->SAR_CO_ID) ? (int)$nextFile->SAR_CO_ID : 0;
		if($archivoId <= 0){
			$this->jsonDicomProcessResponse('unsuccess', 'No se pudo identificar el archivo a procesar', array('progress' => $progressBefore));
			return;
		}

		$this->Solicitudarchivosmodel->markArchivoProcessing($archivoId);

		$archivoNombre = isset($nextFile->SAR_DS_NOMBRE_ORIGINAL) ? (string)$nextFile->SAR_DS_NOMBRE_ORIGINAL : ('Archivo #' . $archivoId);
		$archivoRuta = isset($nextFile->SAR_DS_RUTA) ? (string)$nextFile->SAR_DS_RUTA : '';
		$archivoUsuarioId = isset($nextFile->USR_CO_ID) ? (int)$nextFile->USR_CO_ID : 0;

		$clienteId = $this->resolveSolicitudClientId($solicitud, $archivoUsuarioId);
		$patientId = $this->buildPatientId($clienteId, $solicitudId);

		$result = $this->processSingleSolicitudFile($solicitudId, $archivoRuta, $patientId, $pacsConfig);
		$processedAt = date('Y-m-d H:i:s');

		if($result['success']){
			$this->Solicitudarchivosmodel->markArchivoResult(
				$archivoId,
				Solicitudarchivosmodel::PACS_STATUS_OK,
				'',
				$processedAt,
				$patientId,
				isset($result['dicom_relative_path']) ? (string)$result['dicom_relative_path'] : ''
			);
		}else{
			$errorMessage = isset($result['error']) ? (string)$result['error'] : 'Error no identificado';
			$errorMessage = substr($errorMessage, 0, 2000);
			$this->Solicitudarchivosmodel->markArchivoResult(
				$archivoId,
				Solicitudarchivosmodel::PACS_STATUS_ERROR,
				$errorMessage,
				$processedAt,
				$patientId,
				isset($result['dicom_relative_path']) ? (string)$result['dicom_relative_path'] : ''
			);
		}

		$progressAfter = $this->Solicitudarchivosmodel->getPacsProgressBySolicitud($solicitudId);
		$done = ((int)$progressAfter['pending'] <= 0 && (int)$progressAfter['processing'] <= 0);
		if($done){
			$this->markSolicitudAsProcessedIfComplete($solicitudId);
		}

		$this->jsonDicomProcessResponse(
			'success',
			$result['success']
				? ('Archivo procesado correctamente: ' . $archivoNombre)
				: ('Archivo con error: ' . $archivoNombre),
			array(
				'done' => $done,
				'last' => array(
					'file_id' => $archivoId,
					'file_name' => $archivoNombre,
					'status' => $result['success'] ? 'ok' : 'error',
					'error' => $result['success'] ? '' : (isset($result['error']) ? (string)$result['error'] : ''),
					'patient_id' => $patientId,
				),
				'progress' => $progressAfter,
			)
		);
	}

	public function get_dicom_file_detail(){
		@header('Content-Type: application/json; charset=utf-8');
		@ob_end_clean();
		$maxPreviewBytes = 10 * 1024 * 1024;
		$logPath = '';
		
		if($this->session->userdata('logged')!=TRUE || ($this->session->userdata('acceso'))<100){
			$this->outputJsonAndExit(array('status' => 'unsuccess', 'msg' => 'No autorizado'), $logPath);
		}

		$solicitudId = (int)$this->input->post('ele_id', TRUE);
		$archivoId = (int)$this->input->post('archivo_id', TRUE);

		if($solicitudId <= 0 || $archivoId <= 0){
			$this->outputJsonAndExit(array(
				'status' => 'unsuccess',
				'msg' => 'Parametros invalidos',
				'hash' => $this->security->get_csrf_hash(),
				'token' => $this->security->get_csrf_token_name(),
			), $logPath);
		}

		if(!$this->Solicitudarchivosmodel->canUse()){
			$this->outputJsonAndExit(array(
				'status' => 'unsuccess',
				'msg' => 'Tabla de archivos no disponible',
				'hash' => $this->security->get_csrf_hash(),
				'token' => $this->security->get_csrf_token_name(),
			), $logPath);
		}

		$archivo = $this->Solicitudarchivosmodel->getArchivoById($archivoId, $solicitudId);
		if($archivo === false){
			$this->outputJsonAndExit(array(
				'status' => 'unsuccess',
				'msg' => 'Archivo no encontrado',
				'hash' => $this->security->get_csrf_hash(),
				'token' => $this->security->get_csrf_token_name(),
			), $logPath);
		}

		$relativePath = isset($archivo->SAR_DS_RUTA) ? (string)$archivo->SAR_DS_RUTA : '';
		$absolutePath = $this->resolveAbsolutePath($relativePath);
		if($absolutePath === '' || !file_exists($absolutePath)){
			$this->outputJsonAndExit(array(
				'status' => 'unsuccess',
				'msg' => 'Fichero no existe en disco',
				'hash' => $this->security->get_csrf_hash(),
				'token' => $this->security->get_csrf_token_name(),
			), $logPath);
		}

		$fileSize = @filesize($absolutePath);
		if($fileSize !== false && (int)$fileSize > $maxPreviewBytes){
			$this->load->library('Dicom');
			$metadataResult = $this->dicom->getBasicMetadata($absolutePath);
			$this->outputJsonAndExit(array(
				'status' => 'success',
				'msg' => 'Detalle obtenido correctamente',
				'hash' => $this->security->get_csrf_hash(),
				'token' => $this->security->get_csrf_token_name(),
				'file_id' => $archivoId,
				'file_name' => isset($archivo->SAR_DS_NOMBRE_ORIGINAL) ? (string)$archivo->SAR_DS_NOMBRE_ORIGINAL : ('Archivo #' . $archivoId),
				'metadata' => ($metadataResult['success'] && isset($metadataResult['metadata'])) ? $metadataResult['metadata'] : array(),
				'preview_data_uri' => '',
				'preview_disabled_reason' => 'Preview no disponible para ficheros mayores de 10MB',
			), $logPath);
		}

		$this->load->library('Dicom');
		if(!$this->dicom->isDicom($absolutePath)){
			$this->outputJsonAndExit(array(
				'status' => 'unsuccess',
				'msg' => 'No es un fichero DICOM valido: ' . $this->dicom->getLastError(),
				'hash' => $this->security->get_csrf_hash(),
				'token' => $this->security->get_csrf_token_name(),
			), $logPath);
		}

		$metadataResult = $this->dicom->getBasicMetadata($absolutePath);
		if(!$metadataResult['success']){
			$this->outputJsonAndExit(array(
				'status' => 'unsuccess',
				'msg' => 'No se pudieron leer metadatos: ' . (isset($metadataResult['error']) ? $metadataResult['error'] : 'Error desconocido'),
				'hash' => $this->security->get_csrf_hash(),
				'token' => $this->security->get_csrf_token_name(),
			), $logPath);
		}

		$tmpBase = tempnam(sys_get_temp_dir(), 'dicom_preview_');
		if($tmpBase === false){
			$this->outputJsonAndExit(array(
				'status' => 'unsuccess',
				'msg' => 'No se pudo crear archivo temporal',
				'hash' => $this->security->get_csrf_hash(),
				'token' => $this->security->get_csrf_token_name(),
			), $logPath);
		}

		$previewFile = $tmpBase . '.png';
		@unlink($tmpBase);

		$previewResult = $this->dicom->createPreview($absolutePath, $previewFile, 'png');
		if(!$previewResult['success']){
			@unlink($previewFile);
			$this->outputJsonAndExit(array(
				'status' => 'unsuccess',
				'msg' => 'No se pudo generar preview: ' . (isset($previewResult['error']) ? $previewResult['error'] : 'Error desconocido'),
				'hash' => $this->security->get_csrf_hash(),
				'token' => $this->security->get_csrf_token_name(),
			), $logPath);
		}

		$imageRaw = @file_get_contents($previewFile);
		@unlink($previewFile);

		if($imageRaw === false || $imageRaw === ''){
			$this->outputJsonAndExit(array(
				'status' => 'unsuccess',
				'msg' => 'No se pudo leer la imagen generada',
				'hash' => $this->security->get_csrf_hash(),
				'token' => $this->security->get_csrf_token_name(),
			), $logPath);
		}

		$previewMime = $this->detectPreviewMime($imageRaw);
		if(($previewMime === 'image/x-portable-graymap' || $previewMime === 'image/x-portable-pixmap') && function_exists('imagecreatetruecolor')){
			$pngConverted = $this->convertPnmRawToPng($imageRaw);
			if($pngConverted !== false && $pngConverted !== ''){
				$imageRaw = $pngConverted;
				$previewMime = 'image/png';
			}
		}

		$response = array(
			'status' => 'success',
			'msg' => 'Detalle obtenido correctamente',
			'hash' => $this->security->get_csrf_hash(),
			'token' => $this->security->get_csrf_token_name(),
			'file_id' => $archivoId,
			'file_name' => isset($archivo->SAR_DS_NOMBRE_ORIGINAL) ? (string)$archivo->SAR_DS_NOMBRE_ORIGINAL : ('Archivo #' . $archivoId),
			'metadata' => isset($metadataResult['metadata']) ? $metadataResult['metadata'] : array(),
			'preview_data_uri' => 'data:' . $previewMime . ';base64,' . base64_encode($imageRaw),
		);

		$this->outputJsonAndExit($response, $logPath);
	}

	private function outputJsonAndExit($payload, $logPath = ''){
		$json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		if($json === false){
			if($logPath !== ''){
				file_put_contents($logPath, 'JSON encode error (1): ' . json_last_error_msg() . "\n", FILE_APPEND);
			}

			$payload = $this->sanitizeUtf8Recursive($payload);
			$json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}

		if($json === false){
			if($logPath !== ''){
				file_put_contents($logPath, 'JSON encode error (2): ' . json_last_error_msg() . "\n", FILE_APPEND);
			}
			$json = '{"status":"unsuccess","msg":"Error serializando respuesta JSON"}';
		}

		die($json);
	}

	private function sanitizeUtf8Recursive($value){
		if(is_array($value)){
			foreach($value as $k => $v){
				$value[$k] = $this->sanitizeUtf8Recursive($v);
			}
			return $value;
		}

		if(is_object($value)){
			foreach($value as $k => $v){
				$value->{$k} = $this->sanitizeUtf8Recursive($v);
			}
			return $value;
		}

		if(is_string($value)){
			if(@preg_match('//u', $value)){
				return $value;
			}

			if(function_exists('mb_convert_encoding')){
				$converted = @mb_convert_encoding($value, 'UTF-8', 'UTF-8');
				if($converted !== false && @preg_match('//u', $converted)){
					return $converted;
				}
			}

			if(function_exists('iconv')){
				$converted = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
				if($converted !== false){
					return $converted;
				}
			}

			return preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $value);
		}

		return $value;
	}

	private function detectPreviewMime($raw){
		if(!is_string($raw) || $raw === ''){
			return 'application/octet-stream';
		}

		if(substr($raw, 0, 8) === "\x89PNG\x0D\x0A\x1A\x0A"){
			return 'image/png';
		}

		if(substr($raw, 0, 3) === "\xFF\xD8\xFF"){
			return 'image/jpeg';
		}

		if(substr($raw, 0, 2) === 'BM'){
			return 'image/bmp';
		}

		if(substr($raw, 0, 2) === 'P5'){
			return 'image/x-portable-graymap';
		}

		if(substr($raw, 0, 2) === 'P6'){
			return 'image/x-portable-pixmap';
		}

		return 'application/octet-stream';
	}

	private function convertPnmRawToPng($raw){
		$len = strlen($raw);
		$idx = 0;

		$nextToken = function() use ($raw, $len, &$idx){
			while($idx < $len){
				$ch = $raw[$idx];
				if($ch === '#'){
					while($idx < $len && $raw[$idx] !== "\n"){
						$idx++;
					}
					continue;
				}
				if(!ctype_space($ch)){
					break;
				}
				$idx++;
			}

			if($idx >= $len){
				return null;
			}

			$start = $idx;
			while($idx < $len && !ctype_space($raw[$idx]) && $raw[$idx] !== '#'){
				$idx++;
			}

			return substr($raw, $start, $idx - $start);
		};

		$magic = $nextToken();
		$width = $nextToken();
		$height = $nextToken();
		$maxVal = $nextToken();

		if(($magic !== 'P5' && $magic !== 'P6') || !is_numeric($width) || !is_numeric($height) || !is_numeric($maxVal)){
			return false;
		}

		$w = (int)$width;
		$h = (int)$height;
		$max = (int)$maxVal;
		if($w <= 0 || $h <= 0 || $max <= 0 || $max > 255){
			return false;
		}

		while($idx < $len && ctype_space($raw[$idx])){
			$idx++;
		}

		$pixelData = substr($raw, $idx);
		$expected = ($magic === 'P5') ? ($w * $h) : ($w * $h * 3);
		if(strlen($pixelData) < $expected){
			return false;
		}

		$im = imagecreatetruecolor($w, $h);
		if($im === false){
			return false;
		}

		$pos = 0;
		for($y = 0; $y < $h; $y++){
			for($x = 0; $x < $w; $x++){
				if($magic === 'P5'){
					$g = ord($pixelData[$pos]);
					$pos++;
					$color = ($g << 16) | ($g << 8) | $g;
				}else{
					$r = ord($pixelData[$pos]);
					$g = ord($pixelData[$pos + 1]);
					$b = ord($pixelData[$pos + 2]);
					$pos += 3;
					$color = ($r << 16) | ($g << 8) | $b;
				}
				imagesetpixel($im, $x, $y, $color);
			}
		}

		ob_start();
		imagepng($im);
		$png = ob_get_clean();
		imagedestroy($im);

		if($png === false || $png === ''){
			return false;
		}

		return $png;
	}

	private function jsonDicomProcessResponse($status, $msg, array $extra){
		$response = array(
			'status' => $status,
			'msg' => $msg,
			'hash' => $this->security->get_csrf_hash(),
			'token' => $this->security->get_csrf_token_name(),
		);

		foreach($extra as $key => $value){
			$response[$key] = $value;
		}

		echo json_encode($response);
	}

	private function processSingleSolicitudFile($solicitudId, $relativePath, $patientId, array $pacsConfig){
		$this->load->library('Dicom');

		$sourcePath = $this->resolveAbsolutePath($relativePath);
		if($sourcePath === '' || !file_exists($sourcePath)){
			return array(
				'success' => false,
				'error' => 'No existe el archivo origen para procesar',
				'dicom_relative_path' => '',
			);
		}

		$type = $this->dicom->detectFileType($sourcePath);
		$processedDir = FCPATH . 'uploadDocumentation/' . (int)$solicitudId . '/dicom_processed';
		if(!is_dir($processedDir)){
			@mkdir($processedDir, 0775, true);
		}

		if(!is_dir($processedDir) || !is_writable($processedDir)){
			return array(
				'success' => false,
				'error' => 'Directorio de salida DICOM no disponible: ' . $processedDir,
				'dicom_relative_path' => '',
			);
		}

		$baseName = pathinfo($sourcePath, PATHINFO_FILENAME);
		$safeBase = preg_replace('/[^A-Za-z0-9_\-]/', '_', $baseName);
		if($safeBase === '' || $safeBase === null){
			$safeBase = 'archivo_' . time();
		}

		$dicomTarget = $processedDir . '/' . $safeBase . '_' . uniqid('', true) . '.dcm';
		$dicomRelative = 'uploadDocumentation/' . (int)$solicitudId . '/dicom_processed/' . basename($dicomTarget);

		$metadata = array(
			'patient_id' => $patientId,
			'patient_name' => isset($patientId) ? $patientId : '',
			'study_date' => date('Ymd'),
			'study_time' => date('His'),
			'study_description' => 'Solicitud ' . (int)$solicitudId,
		);

		$conversion = array('success' => false, 'error' => '');

		if($type === 'dicom'){
			if(!@copy($sourcePath, $dicomTarget)){
				return array(
					'success' => false,
					'error' => 'No se pudo preparar copia DICOM para procesado',
					'dicom_relative_path' => '',
				);
			}

			$tagResult = $this->dicom->modifyTag($dicomTarget, 'PatientID', $patientId, true);
			if(!$tagResult['success']){
				return array(
					'success' => false,
					'error' => isset($tagResult['error']) ? (string)$tagResult['error'] : 'No se pudo actualizar PatientID en DICOM',
					'dicom_relative_path' => $dicomRelative,
				);
			}

			$conversion = array('success' => true, 'error' => '');
		}else if($type === 'png'){
			$conversion = $this->dicom->convertPngToDicom($sourcePath, $dicomTarget, $metadata);
		}else if($type === 'jpg' || $type === 'jpeg'){
			$conversion = $this->dicom->convertJpegToDicom($sourcePath, $dicomTarget, $metadata);
		}else if($type === 'pdf'){
			$conversion = $this->dicom->convertPdfToDicom($sourcePath, $dicomTarget, $metadata);
		}else{
			return array(
				'success' => false,
				'error' => 'Tipo de archivo no soportado para procesado DICOM: ' . $type,
				'dicom_relative_path' => '',
			);
		}

		if(!$conversion['success']){
			return array(
				'success' => false,
				'error' => isset($conversion['error']) ? (string)$conversion['error'] : 'Error de conversion a DICOM',
				'dicom_relative_path' => $dicomRelative,
			);
		}

		$send = $this->dicom->sendToPacs(
			$pacsConfig['host'],
			$pacsConfig['port'],
			array($dicomTarget),
			$pacsConfig['called_aet'],
			$pacsConfig['calling_aet']
		);

		if(!$send['success']){
			return array(
				'success' => false,
				'error' => isset($send['error']) ? (string)$send['error'] : 'Error enviando a PACS',
				'dicom_relative_path' => $dicomRelative,
			);
		}

		return array(
			'success' => true,
			'error' => '',
			'dicom_relative_path' => $dicomRelative,
		);
	}

	private function resolveAbsolutePath($relativePath){
		$path = trim((string)$relativePath);
		if($path === ''){
			return '';
		}

		if(strpos($path, '/') === 0){
			return $path;
		}

		return FCPATH . ltrim($path, '/');
	}

	private function resolveSolicitudClientId($solicitud, $fallbackUserId = 0){
		if(is_object($solicitud) && isset($solicitud->USR_CO_ID) && (int)$solicitud->USR_CO_ID > 0){
			return (int)$solicitud->USR_CO_ID;
		}

		if((int)$fallbackUserId > 0){
			return (int)$fallbackUserId;
		}

		return 0;
	}

	private function buildPatientId($clientId, $solicitudId){
		$clientBlock = str_pad((string)max(0, (int)$clientId), 4, '0', STR_PAD_LEFT);
		$solicitudBlock = str_pad((string)max(0, (int)$solicitudId), 5, '0', STR_PAD_LEFT);

		return '2OP-' . $clientBlock . '-' . $solicitudBlock;
	}

	private function getPacsConfig(){
		$host = defined('PACS_HOST') ? trim((string)PACS_HOST) : trim((string)self::$PACS_DEFAULT_HOST);
		$port = defined('PACS_PORT') ? (int)PACS_PORT : (int)self::$PACS_DEFAULT_PORT;
		$calledAet = defined('PACS_CALLED_AET') ? trim((string)PACS_CALLED_AET) : trim((string)self::$PACS_DEFAULT_CALLED_AET);
		$callingAet = defined('PACS_CALLING_AET') ? trim((string)PACS_CALLING_AET) : trim((string)self::$PACS_DEFAULT_CALLING_AET);

		return array(
			'host' => $host,
			'port' => $port,
			'called_aet' => $calledAet !== '' ? $calledAet : 'ANY-SCP',
			'calling_aet' => $callingAet !== '' ? $callingAet : 'ANY-SCU',
		);
	}

	private function checkPacsConnectivity($host, $port){
		$host = trim((string)$host);
		$port = (int)$port;

		if($host === '' || $port <= 0){
			return array('success' => false, 'error' => 'Host o puerto no validos');
		}

		$errno = 0;
		$errstr = '';
		$timeout = 3;
		$socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
		if($socket === false){
			$detail = trim((string)$errstr);
			if($detail === ''){
				$detail = 'Error de red #' . (int)$errno;
			}
			return array('success' => false, 'error' => $detail);
		}

		fclose($socket);
		return array('success' => true, 'error' => '');
	}

	private function markSolicitudAsProcessedIfComplete($solicitudId){
		$progress = $this->Solicitudarchivosmodel->getPacsProgressBySolicitud($solicitudId);
		if($progress === false){
			return;
		}

		if((int)$progress['total'] > 0 && (int)$progress['pending'] <= 0 && (int)$progress['processing'] <= 0){
			$this->{self::$MODEL}->updateElement(array('ESO_CO_ID' => 7), (int)$solicitudId);
		}
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
			'@FIELD_DICOM_SECTION' => $this->buildDicomSectionHtml($cat, $action),
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

	private function buildDicomSectionHtml($cat, $action){
		if($action !== 'edit' || !is_object($cat)){
			return '';
		}

		$estadoId = isset($cat->ESO_CO_ID) ? (int)$cat->ESO_CO_ID : 0;
		if($estadoId < 5){
			return '';
		}

		$solicitudId = isset($cat->{self::$CODE_DB}) ? (int)$cat->{self::$CODE_DB} : 0;
		$files = array();

		if($solicitudId > 0 && $this->Solicitudarchivosmodel->canUse()){
			$this->Solicitudarchivosmodel->ensurePacsColumns();
			$result = $this->Solicitudarchivosmodel->getArchivosBySolicitud($solicitudId);
			if($result !== false){
				foreach($result->result() as $row){
					$pacsStatus = isset($row->SAR_NM_PACS_STATUS) ? (int)$row->SAR_NM_PACS_STATUS : 0;
					$pacsDate = isset($row->SAR_DT_PACS_PROCESADO) ? (string)$row->SAR_DT_PACS_PROCESADO : '';
					$pacsError = isset($row->SAR_DS_PACS_ERROR) ? (string)$row->SAR_DS_PACS_ERROR : '';
					$pacsLabel = 'Pendiente';
					$pacsClass = 'badge-light';
					if($pacsStatus === 1){
						$pacsLabel = 'Subido';
						$pacsClass = 'badge-success';
					}else if($pacsStatus === 2){
						$pacsLabel = 'Error';
						$pacsClass = 'badge-danger';
					}else if($pacsStatus === 3){
						$pacsLabel = 'Procesando';
						$pacsClass = 'badge-warning';
					}

					$files[] = array(
						'id' => isset($row->SAR_CO_ID) ? (int)$row->SAR_CO_ID : 0,
						'name' => isset($row->SAR_DS_NOMBRE_ORIGINAL) ? (string)$row->SAR_DS_NOMBRE_ORIGINAL : 'Sin nombre',
						'extension' => isset($row->SAR_DS_EXTENSION) ? strtoupper((string)$row->SAR_DS_EXTENSION) : '-',
						'size' => $this->formatBytesLabel(isset($row->SAR_NM_TAM_BYTES) ? (float)$row->SAR_NM_TAM_BYTES : 0),
						'date' => $this->formatDateTimeLabel(isset($row->SAR_DT_CREATE) ? $row->SAR_DT_CREATE : ''),
						'pacs_status_label' => $pacsLabel,
						'pacs_status_class' => $pacsClass,
						'pacs_processed_date' => $pacsDate !== '' ? $this->formatDateTimeLabel($pacsDate) : '',
						'pacs_error' => $pacsError,
					);
				}
			}
		}

		$progress = array('total' => count($files), 'ok' => 0, 'error' => 0, 'pending' => 0, 'processing' => 0);
		if($solicitudId > 0 && $this->Solicitudarchivosmodel->canUse()){
			$progressModel = $this->Solicitudarchivosmodel->getPacsProgressBySolicitud($solicitudId);
			if($progressModel !== false){
				$progress = $progressModel;
			}
		}

		$sectionId = 'dicomSection_' . $solicitudId;
		$listId = 'dicomFileList_' . $solicitudId;
		$prevId = 'dicomPrev_' . $solicitudId;
		$nextId = 'dicomNext_' . $solicitudId;
		$infoId = 'dicomPageInfo_' . $solicitudId;
		$buttonId = 'buttonProcesarDicom_' . $solicitudId;
		$progressBarId = 'dicomProgressBar_' . $solicitudId;
		$progressTextId = 'dicomProgressText_' . $solicitudId;
		$logId = 'dicomProcessLog_' . $solicitudId;
		$statusWrapId = 'dicomStatusWrap_' . $solicitudId;
		$modalId = 'dicomDetailModal_' . $solicitudId;
		$modalFileNameId = 'dicomDetailFileName_' . $solicitudId;
		$modalMetaId = 'dicomDetailMetadata_' . $solicitudId;
		$modalImgId = 'dicomDetailImage_' . $solicitudId;
		$modalAlertId = 'dicomDetailAlert_' . $solicitudId;
		$modalLoadingId = 'dicomDetailLoading_' . $solicitudId;

		$html = '';
		$html .= '<hr>';
		$html .= '<h5 class="mb-4">Archivos DICOM</h5>';
		$html .= '<div class="row" id="' . $sectionId . '">';
		$html .= '<div class="col-md-6">';
		$html .= '<div class="border rounded p-3 h-100">';
		$html .= '<div class="d-flex align-items-center justify-content-between mb-3">';
		$html .= '<strong>Ficheros subidos</strong>';
		$html .= '<span class="badge badge-light-primary">Total: ' . count($files) . '</span>';
		$html .= '</div>';

		if(empty($files)){
			$html .= '<div class="text-muted">Todavia no hay ficheros subidos.</div>';
		}else{
			$html .= '<ul class="list-group" id="' . $listId . '">';
			foreach($files as $index => $file){
				$html .= '<li class="list-group-item py-2 dicom-file-item" data-item-index="' . $index . '" data-file-id="' . (int)$file['id'] . '">';
				$html .= '<div class="d-flex align-items-start justify-content-between">';
				$html .= '<div class="pr-2">';
				$html .= '<div class="font-weight-bold text-truncate" title="' . html_escape($file['name']) . '">' . html_escape($file['name']) . '</div>';
				$html .= '<div class="small text-muted">' . html_escape($file['date']) . ' | ' . html_escape($file['extension']) . ' | ' . html_escape($file['size']) . '</div>';
				if($file['pacs_processed_date'] !== ''){
					$html .= '<div class="small text-muted">Procesado: ' . html_escape($file['pacs_processed_date']) . '</div>';
				}
				if($file['pacs_error'] !== ''){
					$html .= '<div class="small text-danger" title="' . html_escape($file['pacs_error']) . '">' . html_escape(substr($file['pacs_error'], 0, 120)) . '</div>';
				}
				$html .= '</div>';
				$html .= '<div class="d-flex flex-column align-items-end">';
				$html .= '<button type="button" class="btn btn-sm btn-light-info mb-2 dicom-view-detail" title="Ver detalle DICOM" data-file-id="' . (int)$file['id'] . '"><i class="fa fa-eye"></i></button>';
				$html .= '<span class="badge ' . html_escape($file['pacs_status_class']) . '">' . html_escape($file['pacs_status_label']) . '</span>';
				$html .= '</div>';
				$html .= '</div>';
				$html .= '</li>';
			}
			$html .= '</ul>';
			$html .= '<div class="d-flex align-items-center justify-content-between mt-3">';
			$html .= '<button type="button" class="btn btn-sm btn-light-primary" id="' . $prevId . '">Anterior</button>';
			$html .= '<span class="small text-muted" id="' . $infoId . '"></span>';
			$html .= '<button type="button" class="btn btn-sm btn-light-primary" id="' . $nextId . '">Siguiente</button>';
			$html .= '</div>';
		}

		$html .= '</div>';
		$html .= '</div>';
		$html .= '<div class="col-md-6 mt-4 mt-md-0">';
		$html .= '<div class="border rounded p-3 h-100 d-flex flex-column justify-content-center align-items-center text-center">';
		$html .= '<p class="text-muted mb-3">Proceso manual para subir y procesar archivos DICOM.</p>';
		$html .= '<button type="button" class="btn btn-primary" id="' . $buttonId . '">Ejecutar proceso subir archivos DICOM</button>';
		$html .= '<div class="w-100 mt-3" id="' . $statusWrapId . '">';
		$html .= '<div class="progress">';
		$html .= '<div id="' . $progressBarId . '" class="progress-bar progress-bar-striped" role="progressbar" style="width:0%"></div>';
		$html .= '</div>';
		$html .= '<div class="small text-muted mt-2" id="' . $progressTextId . '">Pendiente de inicio</div>';
		$html .= '<div class="small mt-2 text-left" id="' . $logId . '"></div>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '<div class="modal fade" id="' . $modalId . '" tabindex="-1" role="dialog" aria-hidden="true">';
		$html .= '<div class="modal-dialog modal-xl" role="document">';
		$html .= '<div class="modal-content">';
		$html .= '<div class="modal-header">';
		$html .= '<h5 class="modal-title">Detalle DICOM</h5>';
		$html .= '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
		$html .= '</div>';
		$html .= '<div class="modal-body">';
		$html .= '<div class="font-weight-bold mb-3" id="' . $modalFileNameId . '">-</div>';
		$html .= '<div class="alert alert-danger d-none" id="' . $modalAlertId . '"></div>';
		$html .= '<div class="row">';
		$html .= '<div class="col-md-6">';
		$html .= '<h6>Metadatos</h6>';
		$html .= '<div class="table-responsive">';
		$html .= '<table class="table table-sm table-bordered mb-0"><tbody id="' . $modalMetaId . '"></tbody></table>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '<div class="col-md-6 mt-4 mt-md-0 text-center">';
		$html .= '<h6>Imagen</h6>';
		$html .= '<div class="small text-muted mb-2 d-none" id="' . $modalLoadingId . '">Generando preview...</div>';
		$html .= '<img id="' . $modalImgId . '" src="" alt="Preview DICOM" style="max-width:100%; max-height:65vh; border:1px solid #ddd; border-radius:4px;" />';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '<div class="modal-footer">';
		$html .= '<button type="button" class="btn btn-light-primary" data-dismiss="modal">Cerrar</button>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';

		$html .= '<script>';
		$html .= '(function bootDicomSection(){';
		$html .= 'if(typeof window.jQuery === "undefined"){';
		$html .= 'window.setTimeout(bootDicomSection, 60);';
		$html .= 'return;';
		$html .= '}';
		$html .= 'var sectionId = "' . $sectionId . '";';
		$html .= 'var listId = "' . $listId . '";';
		$html .= 'var prevId = "' . $prevId . '";';
		$html .= 'var nextId = "' . $nextId . '";';
		$html .= 'var infoId = "' . $infoId . '";';
		$html .= 'var buttonId = "' . $buttonId . '";';
		$html .= 'var progressBarId = "' . $progressBarId . '";';
		$html .= 'var progressTextId = "' . $progressTextId . '";';
		$html .= 'var logId = "' . $logId . '";';
		$html .= 'var modalId = "' . $modalId . '";';
		$html .= 'var modalFileNameId = "' . $modalFileNameId . '";';
		$html .= 'var modalMetaId = "' . $modalMetaId . '";';
		$html .= 'var modalImgId = "' . $modalImgId . '";';
		$html .= 'var modalAlertId = "' . $modalAlertId . '";';
		$html .= 'var modalLoadingId = "' . $modalLoadingId . '";';
		$html .= 'var solicitudId = ' . (int)$solicitudId . ';';
		$html .= 'var processUrl = "' . base_url() . 'index.php/admin/' . self::$NAME . '/processDicomBatch";';
		$html .= 'var detailUrl = "' . base_url() . 'index.php/admin/' . self::$NAME . '/get_dicom_file_detail";';
		$html .= 'var pageSize = 5;';
		$html .= 'var page = 1;';
		$html .= 'var processing = false;';
		$html .= 'var $section = jQuery("#" + sectionId);';
		$html .= 'if($section.length === 0){ return; }';
		$html .= 'var $items = jQuery("#" + listId + " .dicom-file-item");';
		$html .= 'var totalItems = $items.length;';
		$html .= 'var totalPages = Math.max(1, Math.ceil(totalItems / pageSize));';
		$html .= 'function toLabel(key){ return String(key || "").replace(/_/g, " ").replace(/\b\w/g, function(c){ return c.toUpperCase(); }); }';
		$html .= 'function showDetailError(msg){ jQuery("#" + modalAlertId).removeClass("d-none").text(msg || "No se pudo cargar el detalle DICOM."); }';
		$html .= 'function clearDetailError(){ jQuery("#" + modalAlertId).addClass("d-none").text(""); }';
		$html .= 'function setDetailLoading(isLoading){ if(isLoading){ jQuery("#" + modalLoadingId).removeClass("d-none"); } else { jQuery("#" + modalLoadingId).addClass("d-none"); } }';
		$html .= 'function renderMetadataRows(metadata){';
		$html .= 'var rows = "";';
		$html .= 'jQuery.each(metadata || {}, function(key, value){';
		$html .= 'var safeKey = jQuery("<div>").text(toLabel(key)).html();';
		$html .= 'var safeValue = jQuery("<div>").text(value === null ? "" : String(value)).html();';
		$html .= 'rows += "<tr><th style=\"width:38%\">" + safeKey + "</th><td>" + safeValue + "</td></tr>";';
		$html .= '});';
		$html .= 'if(rows === ""){ rows = "<tr><td colspan=\"2\" class=\"text-muted\">Sin metadatos disponibles</td></tr>"; }';
		$html .= 'jQuery("#" + modalMetaId).html(rows);';
		$html .= '}';
		$html .= 'function buildDetailPayload(fileId){';
		$html .= 'var $form = jQuery("#formModalElement");';
		$html .= 'var payload = $form.length ? $form.serializeArray() : [];';
		$html .= 'payload.push({name:"ele_id", value: solicitudId});';
		$html .= 'payload.push({name:"archivo_id", value: fileId});';
		$html .= 'return jQuery.param(payload);';
		$html .= '}';
		$html .= 'function setProgress(progress){';
		$html .= 'if(!progress){ return; }';
		$html .= 'var total = parseInt(progress.total || 0, 10);';
		$html .= 'var ok = parseInt(progress.ok || 0, 10);';
		$html .= 'var error = parseInt(progress.error || 0, 10);';
		$html .= 'var done = ok + error;';
		$html .= 'var percent = total > 0 ? Math.round((done * 100) / total) : 0;';
		$html .= 'jQuery("#" + progressBarId).css("width", percent + "%").attr("aria-valuenow", percent).text(percent + "%");';
		$html .= 'jQuery("#" + progressTextId).text("Procesados " + done + " de " + total + " (OK: " + ok + ", Error: " + error + ")");';
		$html .= '}';
		$html .= 'setProgress(' . json_encode($progress) . ');';
		$html .= 'function appendLog(msg, isError){';
		$html .= 'var safeMsg = jQuery("<div>").text(msg).html();';
		$html .= 'var klass = isError ? "text-danger" : "text-muted";';
		$html .= 'jQuery("#" + logId).prepend("<div class=\"" + klass + "\">" + safeMsg + "</div>");';
		$html .= '}';
		$html .= 'function renderPage(){';
		$html .= 'if(totalItems === 0){ return; }';
		$html .= 'if(page < 1){ page = 1; }';
		$html .= 'if(page > totalPages){ page = totalPages; }';
		$html .= 'var start = (page - 1) * pageSize;';
		$html .= 'var end = start + pageSize;';
		$html .= '$items.hide().slice(start, end).show();';
		$html .= 'jQuery("#" + infoId).text("Pagina " + page + " de " + totalPages);';
		$html .= 'jQuery("#" + prevId).prop("disabled", page <= 1);';
		$html .= 'jQuery("#" + nextId).prop("disabled", page >= totalPages);';
		$html .= '}';
		$html .= 'function processNext(){';
		$html .= 'if(!processing){ return; }';
		$html .= 'var $form = jQuery("#formModalElement");';
		$html .= 'if($form.length === 0){ processing = false; return; }';
		$html .= 'var payload = $form.serializeArray();';
		$html .= 'payload.push({name:"ele_id", value: solicitudId});';
		$html .= 'jQuery.ajax({';
		$html .= 'url: processUrl,';
		$html .= 'method: "POST",';
		$html .= 'dataType: "json",';
		$html .= 'data: jQuery.param(payload),';
		$html .= 'success: function(response){';
		$html .= 'if(response && response.token && response.hash){ jQuery("input[name=\"" + response.token + "\"]").val(response.hash); }';
		$html .= 'if(!response || response.status !== "success"){ appendLog(response && response.msg ? response.msg : "Error en procesado.", true); processing = false; jQuery("#" + buttonId).prop("disabled", false).text("Ejecutar proceso subir archivos DICOM"); return; }';
		$html .= 'if(response.progress){ setProgress(response.progress); }';
		$html .= 'if(response.last && response.last.file_name){ appendLog(response.last.file_name + " -> " + (response.last.status === "ok" ? "OK" : "ERROR"), response.last.status !== "ok"); }';
		$html .= 'if(response.done){ processing = false; jQuery("#" + buttonId).prop("disabled", false).text("Proceso completado"); appendLog("Procesado finalizado.", false); return; }';
		$html .= 'window.setTimeout(processNext, 30);';
		$html .= '},';
		$html .= 'error: function(){ appendLog("Error de comunicacion con el servidor.", true); processing = false; jQuery("#" + buttonId).prop("disabled", false).text("Ejecutar proceso subir archivos DICOM"); }';
		$html .= '});';
		$html .= '}';
		$html .= 'jQuery(document).off("click.dicomPrev_" + sectionId, "#" + prevId).on("click.dicomPrev_" + sectionId, "#" + prevId, function(e){ e.preventDefault(); page--; renderPage(); });';
		$html .= 'jQuery(document).off("click.dicomNext_" + sectionId, "#" + nextId).on("click.dicomNext_" + sectionId, "#" + nextId, function(e){ e.preventDefault(); page++; renderPage(); });';
		$html .= 'jQuery(document).off("click.dicomProcess_" + sectionId, "#" + buttonId).on("click.dicomProcess_" + sectionId, "#" + buttonId, function(e){ e.preventDefault(); if(processing){ return; } processing = true; jQuery(this).prop("disabled", true).text("Procesando..."); appendLog("Inicio del procesado DICOM.", false); processNext(); });';
		$html .= 'jQuery(document).off("click.dicomView_" + sectionId, "#" + listId + " .dicom-view-detail").on("click.dicomView_" + sectionId, "#" + listId + " .dicom-view-detail", function(e){';
		$html .= 'e.preventDefault();';
		$html .= 'var fileId = parseInt(jQuery(this).data("file-id") || 0, 10);';
		$html .= 'if(fileId <= 0){ return; }';
		$html .= 'clearDetailError();';
		$html .= 'setDetailLoading(true);';
		$html .= 'jQuery("#" + modalFileNameId).text("Cargando...");';
		$html .= 'jQuery("#" + modalMetaId).html("<tr><td colspan=\"2\" class=\"text-muted\">Cargando metadatos...</td></tr>");';
		$html .= 'jQuery("#" + modalImgId).attr("src", "");';
		$html .= 'jQuery("#" + modalId).modal("show");';
		$html .= 'var payload = buildDetailPayload(fileId);';
		$html .= 'jQuery.ajax({';
		$html .= 'url: detailUrl,';
		$html .= 'method: "POST",';
		$html .= 'dataType: "json",';
		$html .= 'data: payload,';
		$html .= 'success: function(response){';
		$html .= 'setDetailLoading(false);';
		$html .= 'if(response && response.token && response.hash){ jQuery("input[name=\"" + response.token + "\"]").val(response.hash); }';
		$html .= 'if(!response || response.status !== "success"){ showDetailError(response && response.msg ? response.msg : "No se pudo cargar el detalle DICOM."); return; }';
		$html .= 'jQuery("#" + modalFileNameId).text(response.file_name || ("Archivo #" + fileId));';
		$html .= 'renderMetadataRows(response.metadata || {});';
		$html .= 'if(response.preview_data_uri){ jQuery("#" + modalImgId).attr("src", response.preview_data_uri); } else if(response.preview_disabled_reason){ showDetailError(response.preview_disabled_reason); } else { showDetailError("No se pudo generar la imagen del DICOM."); }';
		$html .= '},';
		$html .= 'error: function(xhr){';
		$html .= 'setDetailLoading(false);';
		$html .= 'var detail = "";';
		$html .= 'if(xhr && xhr.status){ detail = " (HTTP " + xhr.status + ")"; }';
		$html .= 'showDetailError("Error de comunicacion con el servidor al pedir detalle DICOM" + detail + ".");';
		$html .= '}';
		$html .= '});';
		$html .= '});';
		$html .= 'renderPage();';
		$html .= '})();';
		$html .= '</script>';

		return $html;
	}

	private function formatBytesLabel($bytes){
		$bytes = (float)$bytes;
		if($bytes <= 0){
			return '0 B';
		}

		$units = array('B', 'KB', 'MB', 'GB', 'TB');
		$pow = (int)floor(log($bytes, 1024));
		$pow = max(0, min($pow, count($units) - 1));
		$value = $bytes / pow(1024, $pow);

		return number_format($value, 2, ',', '.') . ' ' . $units[$pow];
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
			return array('status' => 'success', 'created' => false, 'user_id' => (int)$existingUser->USR_CO_ID);
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

		return array('status' => 'success', 'created' => true, 'user_id' => (int)$userId);
	}

	private function hasSolicitudClientColumn(){
		return $this->db->field_exists('USR_CO_ID', 't_sol_solicitudes');
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