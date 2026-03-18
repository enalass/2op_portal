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

				$data[] = array(
								"RecordID"			=>intval($element->{self::$CODE_DB})
								,"Name"				=>$element->{self::$NAME_DB}
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

		//Añadimos los input
		$campos_formulario = array(
			"@FIELD_ID"
			,"@FIELD_ACTION"
			,"@FIELD_NAME"
			
		);

		$valores_campos_formulario = array(
			form_hidden('ele_id', '0')
			,form_hidden('action', 'add')
			,form_input(array("name"=>"ele_name", "id"=>"ele_name","class"=>"form-control","placeholder"=>"Nombre", "value"=>""))
		);
		$contenido = str_replace($campos_formulario, $valores_campos_formulario, $contenido);
		$contenido .= '';
		//Creamos el Formulario
		$formulario = form_open(base_url()."index.php/admin/" . self::$NAME . "/addElement",array("id"=>"formModalElement"));
		$formulario .= $contenido;
		$formulario .= form_close();

		echo $formulario;
	}

	public function addElement(){
		$resp = array();

		$this->form_validation->set_rules('ele_name', 'name', 'required|trim|min_length[3]|xss_clean');
		
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
				self::$PREFIX . "_DS_NOMBRE"		=>$this->input->post('ele_name')
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

		//Añadimos los input
		$campos_formulario = array(
			"@FIELD_ID"
			,"@FIELD_ACTION"
			,"@FIELD_NAME"
			
		);
		
		$valores_campos_formulario = array(
			form_hidden('ele_id', $cat->{self::$CODE_DB})
			,form_hidden('action', 'edit')
			,form_input(array("name"=>"ele_name", "id"=>"ele_name","class"=>"form-control","placeholder"=>"Nombre", "value"=>$cat->{self::$NAME_DB}))
		);
		$contenido = str_replace($campos_formulario, $valores_campos_formulario, $contenido);
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

		$this->form_validation->set_rules('ele_name', 'nombre', 'required|trim|min_length[3]|xss_clean');

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
				self::$PREFIX . "_DS_NOMBRE"		=>$this->input->post('ele_name')
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

		//Añadimos los input
		$campos_formulario = array(
			"@FIELD_ID"
			,"@FIELD_ACTION"
			,"@FIELD_NAME"
		);
		$optionsCheck = array("name"=>"ele_active", "id"=>"ele_active", "value"=>1,"disabled"=>"disabled");
		$valores_campos_formulario = array(
			form_hidden('ele_id', $cat->{self::$CODE_DB})
			,form_hidden('action', 'delete')
			,form_input(array("name"=>"ele_name", "id"=>"ele_name","class"=>"form-control","placeholder"=>"Nombre", "value"=>$cat->{self::$NAME_DB},"disabled"=>"disabled"))
		);
		$contenido = str_replace($campos_formulario, $valores_campos_formulario, $contenido);
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

}

/* End of file CA003_solicitudes.php */
/* Location: ./application/controllers/admin/CA003_solicitudes.php */