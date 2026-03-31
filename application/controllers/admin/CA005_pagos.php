<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CA005_pagos extends CI_Controller {

	private static $NAME = 'cA005_pagos';

	function __construct(){
		parent::__construct();
		$this->load->model('pagosmodel');
	}

	public function index()
	{
		if(($this->session->userdata('logged'))==TRUE && ($this->session->userdata('acceso'))>=100){
			$data = array(
				"content" => "admin/vA005_pagos.php",
				"titulo" => "2º Opinión Radiológica",
				"controller" => self::$NAME,
				"javascriptMenu" => "$('#menuPagos').addClass('menu-item-active');"
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

		$elements = $this->pagosmodel->getList();
		if($elements != false){
			$rows = $elements->result();
			$solicitudCodeByOrder = array();

			foreach($rows as $rowMap){
				$orderKey = !empty($rowMap->RPI_DS_ORDER) ? (string)$rowMap->RPI_DS_ORDER : '';
				if($orderKey === ''){
					continue;
				}

				$rowSolicitudCode = '-';
				$rowSolicitudId = (int)$rowMap->SOL_CO_ID;
				$rowClienteId = (int)$rowMap->USR_CO_ID;
				if($rowSolicitudId > 0 && $rowClienteId > 0){
					$rowSolicitudCode = $this->composeSolicitudCode($rowClienteId, $rowSolicitudId);
				}

				if(strtoupper((string)$rowMap->RPI_DS_CANAL) === 'INIT'){
					$solicitudCodeByOrder[$orderKey] = $rowSolicitudCode;
				}elseif(!isset($solicitudCodeByOrder[$orderKey])){
					$solicitudCodeByOrder[$orderKey] = $rowSolicitudCode;
				}
			}

			foreach($rows as $element){
				$fechaPago = '';
				if(!empty($element->RPI_DT_CREATE)){
					$timestamp = strtotime($element->RPI_DT_CREATE);
					$fechaPago = $timestamp ? date('d-m-Y H:i', $timestamp) : '';
				}

				$cliente = trim((string)$element->USR_DS_NOMBRE . ' ' . (string)$element->USR_DS_APELLIDOS);
				if($cliente === ''){
					$cliente = (string)$element->USR_DS_MAIL;
				}
				if($cliente === ''){
					$cliente = '-';
				}

				$solicitudId = (int)$element->SOL_CO_ID;
				$clienteId = (int)$element->USR_CO_ID;
				$solicitudClienteCode = '-';
				$orderKey = !empty($element->RPI_DS_ORDER) ? (string)$element->RPI_DS_ORDER : '';
				if($orderKey !== '' && isset($solicitudCodeByOrder[$orderKey])){
					$solicitudClienteCode = $solicitudCodeByOrder[$orderKey];
				}elseif($solicitudId > 0 && $clienteId > 0){
					$solicitudClienteCode = $this->composeSolicitudCode($clienteId, $solicitudId);
				}

				$data[] = array(
					"RecordID" => intval($element->RPI_CO_ID),
					"SolicitudID" => $solicitudId,
					"SolicitudClienteID" => $solicitudClienteCode,
					"Solicitud" => !empty($element->SOL_DS_NOMBRE) ? $element->SOL_DS_NOMBRE : '-',
					"Cliente" => $cliente,
					"PagoEstado" => !empty($element->RPI_DS_ESTADO) ? $element->RPI_DS_ESTADO : '-',
					"Canal" => !empty($element->RPI_DS_CANAL) ? $element->RPI_DS_CANAL : '-',
					"Respuesta" => !empty($element->RPI_DS_RESPONSE_CODE) ? $element->RPI_DS_RESPONSE_CODE : '-',
					"CodigoPagoRedsys" => !empty($element->RPI_DS_ORDER) ? $element->RPI_DS_ORDER : '-',
					"FechaPago" => $fechaPago
				);
			}
		}

		echo json_encode(array("meta" => array("field" => "RecordID"), "data" => $data));
	}

	private function composeSolicitudCode($clientId, $solicitudId){
		$clientPart = str_pad((string)((int)$clientId), 4, '0', STR_PAD_LEFT);
		$solicitudPart = str_pad((string)((int)$solicitudId), 5, '0', STR_PAD_LEFT);

		return '2OP-' . $clientPart . $solicitudPart;
	}
}

/* End of file cA005_pagos.php */
/* Location: ./application/controllers/admin/cA005_pagos.php */
