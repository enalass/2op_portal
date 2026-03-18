<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// https://github.com/chriskacerguis/codeigniter-restserver/
use chriskacerguis\RestServer\RestController;
require APPPATH . '/libraries/REST_Controller.php';
require APPPATH . '/libraries/Format.php';

header('Access-Control-Allow-Origin: https://segundaopinionradiologica.actualpacs.com/');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header("Access-Control-Allow-Methods: POST, OPTIONS");

class Pacs extends RestController {

  public function __construct() {
      parent::__construct();
      $this->load->model('apimodel');
      
  }

  public function report_post()
  {
    $informe      = $this->post( 'report' );
    $idPaciente   = $this->post( 'patient_id' );
    $fechaEstudio = $this->post( 'date' );
    $radiologo    = $this->post( 'radiologist' );
    $nColegiado   = $this->post( 'membership' );

    if ( $informe == "" || $idPaciente == "" || $fechaEstudio == "" || $radiologo == "" || $nColegiado == ""){
      $this->response(array("success"=>false,"msg"=>"Debe informar todos los campos","data"=>array()),200);
    }
    
    $arregloEstudio = array(
      "report" => $informe
      ,"patient_id" => $idPaciente
      ,"date" => $fechaEstudio
      ,"radiologist" => $radiologo
      ,"membership" => $nColegiado
    );


    $this->apimodel->insertLog( ["APT_DS_DATA" => json_encode( $arregloEstudio )] );




    $this->response(array("success"=>true,"msg"=>"","data"=>$arregloEstudio),200);


  }

}

/* End of file Pedidos.php */
/* Location: ./application/controllers/api/Pedidos.php */