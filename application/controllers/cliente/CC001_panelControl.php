<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CC001_panelControl extends CI_Controller {

	function __construct(){
		parent::__construct();
		$this->load->model('Solicitudmodel');
		$this->load->model('Redsysintentosmodel');
		$this->load->model('Solicitudarchivosmodel');
		$this->config->load('redsys', TRUE);
	}

	public function index()
	{
		if($this->session->userdata('logged')!=TRUE || (int)$this->session->userdata('perfil')!==4){
			redirect('index.php/cerbero','refresh');
			return;
		}

		$userId = (int)$this->session->userdata('id');
		$warningMessage = '';
		$solicitudes = array();

		if(!$this->Solicitudmodel->hasSolicitudUserColumn()){
			$warningMessage = 'No se ha configurado la asociacion de solicitudes con usuario cliente.';
		}else{
			$result = $this->Solicitudmodel->getClientVisibleSolicitudes($userId, 2);
			if($result !== false){
				foreach($result->result() as $row){
					$statusInfo = $this->resolveClientStatus($row);
					$solicitudId = (int)$row->SOL_CO_ID;
					$solicitudes[] = array(
						'id' => $solicitudId,
						'codigo_solicitud' => $this->composeSolicitudCode($userId, $solicitudId),
						'nombre' => $row->SOL_DS_NOMBRE,
						'estado_real_id' => (int)$row->ESO_CO_ID,
						'estado_real_nombre' => $row->ESO_DS_NAME,
						'estado_cliente_id' => $statusInfo['id'],
						'estado_cliente_nombre' => $statusInfo['name'],
						'origen' => $row->FSO_DS_NAME,
						'importe' => $row->SOL_NM_IMPORTE,
						'fecha_solicitud' => $this->formatDate($row->SOL_DT_CREATE),
					);
				}
			}
		}

		$selectedId = (int)$this->input->get('solicitud');
		$selectedSolicitud = null;
		$selectedSolicitudData = $this->getDefaultClientFormData();
		$selectedSolicitudFiles = array();
		$uploadWarningMessage = '';
		if(count($solicitudes) === 1){
			$selectedSolicitud = $solicitudes[0];
		}else if(count($solicitudes) > 1){
			foreach($solicitudes as $item){
				if($selectedId > 0 && $item['id'] === $selectedId){
					$selectedSolicitud = $item;
					break;
				}
			}
			if($selectedSolicitud === null){
				$selectedSolicitud = $solicitudes[0];
			}
		}

		if($selectedSolicitud !== null){
			$selectedDetalle = $this->Solicitudmodel->getClientSolicitudById($userId, (int)$selectedSolicitud['id']);
			if($selectedDetalle !== false){
				$selectedSolicitudData = $this->extractClientFormData($selectedDetalle);
				if($this->Solicitudarchivosmodel->canUse()){
					$filesResult = $this->Solicitudarchivosmodel->getArchivosBySolicitud((int)$selectedSolicitud['id']);
					if($filesResult !== false){
						foreach($filesResult->result() as $fileRow){
							$selectedSolicitudFiles[] = array(
								'nombre_original' => isset($fileRow->SAR_DS_NOMBRE_ORIGINAL) ? (string)$fileRow->SAR_DS_NOMBRE_ORIGINAL : '',
								'extension' => isset($fileRow->SAR_DS_EXTENSION) ? (string)$fileRow->SAR_DS_EXTENSION : '',
								'tam_bytes' => isset($fileRow->SAR_NM_TAM_BYTES) ? (int)$fileRow->SAR_NM_TAM_BYTES : 0,
								'fecha' => isset($fileRow->SAR_DT_CREATE) ? $this->formatDate($fileRow->SAR_DT_CREATE) : '-',
							);
						}
					}
				}else{
					$uploadWarningMessage = 'No se puede guardar la subida porque falta la tabla de archivos de solicitud.';
				}
			}
		}

		$data = array(
			"content" => "cliente/vC001_panelControl.php",
			"titulo" => "Panel cliente",
			"javascriptMenu" => "$('#menuDashBoard').addClass('menu-item-active');",
			"solicitudes" => $solicitudes,
			"selectedSolicitud" => $selectedSolicitud,
			"selectedSolicitudData" => $selectedSolicitudData,
			"selectedSolicitudFiles" => $selectedSolicitudFiles,
			"uploadWarningMessage" => $uploadWarningMessage,
			"uploadTechnicalLogEnabled" => (bool)$this->config->item('upload_technical_log_enabled'),
			"warningMessage" => $warningMessage,
		);

		$this->load->view('layout_cliente', $data);
	}

	public function confirmarPago(){
		if($this->session->userdata('logged')!=TRUE || (int)$this->session->userdata('perfil')!==4){
			echo "";
			return;
		}

		$this->form_validation->set_rules('ele_id', 'solicitud', 'required|integer');
		$this->form_validation->set_message('required','Debes rellenar el campo '. ' %s');

		if($this->form_validation->run()==FALSE){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>validation_errors(),
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		$userId = (int)$this->session->userdata('id');
		$solicitudId = (int)$this->input->post('ele_id', TRUE);
		$solicitud = $this->Solicitudmodel->getClientSolicitudById($userId, $solicitudId);

		if($solicitud === false){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>"La solicitud indicada no existe o no pertenece al cliente autenticado",
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		if((int)$solicitud->ESO_CO_ID !== 2){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>"Solo se puede confirmar el pago para solicitudes en estado Solicitado Pago",
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		$updateData = array('ESO_CO_ID' => 3);
		if($this->hasPagoCompletedDateColumn()){
			$updateData['SOL_DT_PAGO_REALIZADO'] = date('Y-m-d H:i:s');
		}

		$this->Solicitudmodel->updateElement($updateData, $solicitudId);

		echo json_encode(array(
			"status"=>"success",
			"msg"=>"Pago confirmado correctamente",
			"hash"=> $this->security->get_csrf_hash(),
			"token"=> $this->security->get_csrf_token_name()
		));
	}

	public function iniciarPago(){
		if($this->session->userdata('logged')!=TRUE || (int)$this->session->userdata('perfil')!==4){
			redirect('index.php/cerbero','refresh');
			return;
		}

		$redsys = $this->getRedsysConfig();
		if(!$redsys['enabled']){
			redirect(site_url('panel'));
			return;
		}

		$userId = (int)$this->session->userdata('id');
		$solicitudId = (int)$this->input->get('solicitud', TRUE);
		if($solicitudId <= 0){
			redirect(site_url('panel'));
			return;
		}

		$solicitud = $this->Solicitudmodel->getClientSolicitudById($userId, $solicitudId);
		if($solicitud === false || (int)$solicitud->ESO_CO_ID !== 2){
			redirect(site_url('panel') . '?solicitud=' . $solicitudId);
			return;
		}

		$amount = $this->formatRedsysAmount($solicitud->SOL_NM_IMPORTE);
		if($amount <= 0){
			redirect(site_url('panel') . '?solicitud=' . $solicitudId);
			return;
		}

		$order = $this->buildRedsysOrder($solicitudId);
		$merchantData = base64_encode(json_encode(array(
			'solicitud_id' => $solicitudId,
			'user_id' => $userId
		)));

		$merchantParams = array(
			'DS_MERCHANT_AMOUNT' => (string)$amount,
			'DS_MERCHANT_ORDER' => $order,
			'DS_MERCHANT_MERCHANTCODE' => $redsys['merchant_code'],
			'DS_MERCHANT_CURRENCY' => $redsys['currency'],
			'DS_MERCHANT_TRANSACTIONTYPE' => $redsys['transaction_type'],
			'DS_MERCHANT_TERMINAL' => $redsys['terminal'],
			'DS_MERCHANT_MERCHANTURL' => $redsys['merchant_url'],
			'DS_MERCHANT_URLOK' => $redsys['url_ok'],
			'DS_MERCHANT_URLKO' => $redsys['url_ko'],
			'DS_MERCHANT_PRODUCTDESCRIPTION' => 'Solicitud ' . $this->composeSolicitudCode($userId, $solicitudId),
			'DS_MERCHANT_TITULAR' => isset($solicitud->SOL_DS_NOMBRE) ? (string)$solicitud->SOL_DS_NOMBRE : 'Cliente',
			'DS_MERCHANT_MERCHANTDATA' => $merchantData,
		);

		$merchantParameters = base64_encode(json_encode($merchantParams));
		$signature = $this->createRedsysSignature($merchantParameters, $order, $redsys['secret_key']);

		$this->logRedsysAttempt('init', 'INIT', array(
			'solicitud_id' => $solicitudId,
			'user_id' => $userId,
			'order' => $order,
			'response_code' => '',
			'payload' => json_encode($merchantParams),
			'raw' => $merchantParameters,
		));

		$this->load->view('cliente/vC001_redsys_redirect.php', array(
			'gateway_url' => $redsys['gateway_url'],
			'signature_version' => 'HMAC_SHA256_V1',
			'merchant_parameters' => $merchantParameters,
			'signature' => $signature,
		));
	}

	public function subirEstudioCliente(){
		if($this->session->userdata('logged')!=TRUE || (int)$this->session->userdata('perfil')!==4){
			echo "";
			return;
		}

		$this->applyLargeUploadRuntimeLimits();

		$contentLength = (int)$this->input->server('CONTENT_LENGTH');
		$postMaxBytes = $this->iniSizeToBytes(ini_get('post_max_size'));
		if($contentLength > 0 && $postMaxBytes > 0 && $contentLength > $postMaxBytes){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>'El envio supera post_max_size (' . ini_get('post_max_size') . '). Reduce el lote o aumenta este limite en la configuracion de PHP del servidor.',
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		if($contentLength > 0){
			$tempDir = $this->getUploadTempDir();
			$freeBytes = @disk_free_space($tempDir);
			if(is_numeric($freeBytes) && (float)$freeBytes > 0 && (float)$freeBytes < (float)$contentLength){
				echo json_encode(array(
					"status"=>"unsuccess",
					"msg"=>'Espacio insuficiente en carpeta temporal de PHP para recibir la subida. ' . $this->buildUploadTempDiagnostic() . ', tamano_peticion_mb=' . (int)floor(((float)$contentLength) / 1048576),
					"hash"=> $this->security->get_csrf_hash(),
					"token"=> $this->security->get_csrf_token_name()
				));
				return;
			}
		}

		if(!$this->Solicitudarchivosmodel->canUse()){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>"No se puede guardar la subida porque falta la tabla de archivos de solicitud",
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		$this->form_validation->set_rules('ele_id', 'solicitud', 'required|integer');
		$this->form_validation->set_message('required','Debes rellenar el campo '. ' %s');

		if($this->form_validation->run()==FALSE){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>validation_errors(),
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		$userId = (int)$this->session->userdata('id');
		$solicitudId = (int)$this->input->post('ele_id', TRUE);
		$solicitud = $this->Solicitudmodel->getClientSolicitudById($userId, $solicitudId);

		if($solicitud === false){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>"La solicitud indicada no existe o no pertenece al cliente autenticado",
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		if((int)$solicitud->ESO_CO_ID < 5){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>"La solicitud aun no esta disponible para subida de estudios",
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		if(!isset($_FILES['estudios']) || !isset($_FILES['estudios']['name']) || !is_array($_FILES['estudios']['name'])){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>"Debes seleccionar al menos un archivo",
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		$uploadDir = $this->getSolicitudUploadDirectory($solicitudId);
		if(!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true)){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>"No se pudo preparar el directorio de subida",
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		$allowedExtensions = array('dcm', 'dicom', 'zip', 'pdf', 'jpg', 'png');
		$allowedExtractedExtensions = array('dcm', 'dicom', 'pdf', 'jpg', 'png');
		$maxBytes = $this->getMaxUploadFileBytes();
		$maxFiles = 1500;
		$maxTotalBytes = (int)(1536 * 1024 * 1024); // 1.5GB total aprox
		$uploaded = 0;
		$uploadedItems = array();
		$errors = array();
		$totalIncomingBytes = 0;

		$names = $_FILES['estudios']['name'];
		$tmpNames = $_FILES['estudios']['tmp_name'];
		$sizes = $_FILES['estudios']['size'];
		$errorCodes = $_FILES['estudios']['error'];
		$expectedBatchCount = (int)$this->input->post('expected_batch_count', TRUE);
		$receivedBatchCount = is_array($names) ? count($names) : 0;

		if($expectedBatchCount > 0 && $receivedBatchCount < $expectedBatchCount){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>'El servidor ha recibido menos archivos de los enviados en el lote (' . $receivedBatchCount . '/' . $expectedBatchCount . '). Ajusta max_file_uploads en PHP o reduce archivos por lote.',
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		if(count($names) > $maxFiles){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>'Has superado el maximo de archivos permitidos por envio (' . $maxFiles . ')',
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		foreach($sizes as $sizeCandidate){
			$totalIncomingBytes += (int)$sizeCandidate;
		}

		if($totalIncomingBytes > $maxTotalBytes){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>'El tamano total del envio supera el maximo permitido (1.5GB)',
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		for($i = 0; $i < count($names); $i++){
			$originalName = isset($names[$i]) ? (string)$names[$i] : '';
			$tmpName = isset($tmpNames[$i]) ? (string)$tmpNames[$i] : '';
			$size = isset($sizes[$i]) ? (int)$sizes[$i] : 0;
			$errorCode = isset($errorCodes[$i]) ? (int)$errorCodes[$i] : 4;
			$isDicomWithoutExtension = false;

			if($originalName === '' || $errorCode === 4){
				continue;
			}

			if($errorCode !== 0){
				$errors[] = $this->buildUploadErrorMessage($originalName, $errorCode);
				continue;
			}

			$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
			if(!in_array($extension, $allowedExtensions, true)){
				if($extension === '' && $this->isDicomFileByContent($tmpName)){
					$isDicomWithoutExtension = true;
					$extension = 'dcm';
				}else{
					$errors[] = 'Extension no permitida: ' . html_escape($originalName);
					continue;
				}
			}

			if($size <= 0 || $size > $maxBytes){
				$errors[] = 'Tamano no valido para ' . html_escape($originalName);
				continue;
			}

			if($extension === 'zip'){
				$zipStoredName = $this->buildStoredUploadName($solicitudId, $i, 'zip');
				$zipPath = $uploadDir . DIRECTORY_SEPARATOR . $zipStoredName;

				if(!is_uploaded_file($tmpName) || !@move_uploaded_file($tmpName, $zipPath)){
					$errors[] = 'No se pudo mover el ZIP ' . html_escape($originalName);
					continue;
				}

				$processedInZip = $this->processZipUploadFile(
					$zipPath,
					$originalName,
					$solicitudId,
					$userId,
					$maxBytes,
					$allowedExtractedExtensions,
					$uploaded,
					$uploadedItems,
					$errors
				);

				@unlink($zipPath);
				if($processedInZip <= 0){
					$errors[] = 'El ZIP no contiene ficheros validos procesables: ' . html_escape($originalName);
				}

				continue;
			}

			$safeOriginal = $this->sanitizeUploadName($originalName);
			$storedName = $this->buildStoredUploadName($solicitudId, $i, $extension);
			$targetPath = $uploadDir . DIRECTORY_SEPARATOR . $storedName;

			if(!is_uploaded_file($tmpName) || !@move_uploaded_file($tmpName, $targetPath)){
				$errors[] = 'No se pudo mover el archivo ' . html_escape($originalName);
				continue;
			}

			$relativePath = 'uploadDocumentation/' . $solicitudId . '/' . $storedName;
			$insertData = array(
				'SOL_CO_ID' => $solicitudId,
				'USR_CO_ID' => $userId,
				'SAR_DS_NOMBRE_ORIGINAL' => $safeOriginal,
				'SAR_DS_NOMBRE_GUARDADO' => $storedName,
				'SAR_DS_RUTA' => $relativePath,
				'SAR_NM_TAM_BYTES' => $size,
				'SAR_DS_EXTENSION' => $extension,
				'SAR_DT_CREATE' => date('Y-m-d H:i:s'),
				'SAR_BL_ENABLE' => 1,
				'SAR_BL_DELETE' => 0,
			);

			if($this->Solicitudarchivosmodel->insertArchivo($insertData) === false){
				@unlink($targetPath);
				$errors[] = 'No se pudo registrar el archivo ' . html_escape($originalName);
				continue;
			}

			$uploaded++;
			$uploadedItems[] = array(
				'nombre_original' => $safeOriginal,
				'extension' => $isDicomWithoutExtension ? 'DICOM' : strtoupper($extension),
				'tam_bytes' => (int)$size,
				'fecha' => $this->formatDate(date('Y-m-d H:i:s')),
			);
		}

		if($uploaded <= 0){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=> !empty($errors) ? implode('<br>', $errors) : 'No se ha subido ningun archivo valido',
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		$statusChanged = false;
		if((int)$solicitud->ESO_CO_ID < 6){
			$this->Solicitudmodel->updateElement(array('ESO_CO_ID' => 6), $solicitudId);
			$statusChanged = true;
		}

		if($statusChanged){
			$this->notifyAdminEstudioSubido($solicitudId, isset($solicitud->SOL_DS_NOMBRE) ? (string)$solicitud->SOL_DS_NOMBRE : '');
		}

		$msg = 'Archivos subidos correctamente (' . $uploaded . ')';
		if(!empty($errors)){
			$msg .= '<br>Algunos archivos no se pudieron procesar:<br>' . implode('<br>', $errors);
		}

		echo json_encode(array(
			"status"=>"success",
			"msg"=>$msg,
			"uploaded_count"=>$uploaded,
			"uploaded_files"=>$uploadedItems,
			"hash"=> $this->security->get_csrf_hash(),
			"token"=> $this->security->get_csrf_token_name()
		));
	}

	public function subirEstudioClienteChunk(){
		if($this->session->userdata('logged')!=TRUE || (int)$this->session->userdata('perfil')!==4){
			echo "";
			return;
		}

		$this->applyLargeUploadRuntimeLimits();

		if(!$this->Solicitudarchivosmodel->canUse()){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>"No se puede guardar la subida porque falta la tabla de archivos de solicitud",
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		$userId = (int)$this->session->userdata('id');
		$solicitudId = (int)$this->input->post('ele_id', TRUE);
		$chunkIndex = (int)$this->input->post('chunk_index', TRUE);
		$totalChunks = (int)$this->input->post('total_chunks', TRUE);
		$totalSize = (int)$this->input->post('total_size', TRUE);
		$chunkOffset = (int)$this->input->post('chunk_offset', TRUE);
		$chunkEnd = (int)$this->input->post('chunk_end', TRUE);
		$isLastChunkFlag = (int)$this->input->post('is_last_chunk', TRUE) === 1;
		$originalName = trim((string)$this->input->post('original_name', TRUE));
		$uploadTokenRaw = trim((string)$this->input->post('upload_token', TRUE));

		if($solicitudId <= 0 || $chunkIndex < 0 || $totalChunks <= 0 || $originalName === '' || $uploadTokenRaw === ''){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>"Peticion chunk invalida",
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		$uploadToken = preg_replace('/[^A-Za-z0-9_-]/', '', $uploadTokenRaw);
		if($uploadToken === ''){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>"Token de chunk invalido",
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		$solicitud = $this->Solicitudmodel->getClientSolicitudById($userId, $solicitudId);
		if($solicitud === false){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>"La solicitud indicada no existe o no pertenece al cliente autenticado",
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		if((int)$solicitud->ESO_CO_ID < 5){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>"La solicitud aun no esta disponible para subida de estudios",
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		if(!isset($_FILES['chunk']) || !isset($_FILES['chunk']['tmp_name'])){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>"No se recibio el chunk",
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		$chunkError = isset($_FILES['chunk']['error']) ? (int)$_FILES['chunk']['error'] : 4;
		if($chunkError !== 0){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>$this->buildUploadErrorMessage($originalName, $chunkError),
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		$chunkTmp = (string)$_FILES['chunk']['tmp_name'];
		if(!is_uploaded_file($chunkTmp)){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>"Chunk invalido recibido",
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		$chunkDir = $this->getChunkTempDirectory($solicitudId);
		if(!is_dir($chunkDir) && !@mkdir($chunkDir, 0755, true)){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>"No se pudo preparar almacenamiento temporal de chunks",
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		$chunkBase = 'u' . $userId . '_s' . $solicitudId . '_' . $uploadToken;
		$chunkPath = $chunkDir . DIRECTORY_SEPARATOR . $chunkBase . '.part';

		if($chunkIndex === 0 && is_file($chunkPath)){
			@unlink($chunkPath);
		}

		$currentSize = is_file($chunkPath) ? (int)@filesize($chunkPath) : 0;
		$incomingChunkSize = isset($_FILES['chunk']['size']) ? (int)$_FILES['chunk']['size'] : 0;
		$skipWriteBecauseAlreadyPersisted = false;
		$derivedOffset = -1;
		if($chunkEnd > 0 && $incomingChunkSize > 0){
			$derivedOffset = $chunkEnd - $incomingChunkSize;
		}
		if($derivedOffset >= 0){
			$chunkOffset = $derivedOffset;
		}

		if($chunkOffset < 0){
			$chunkOffset = $currentSize;
		}

		if($chunkOffset < $currentSize){
			$alreadyPersisted = ($incomingChunkSize > 0 && ($chunkOffset + $incomingChunkSize) <= $currentSize);
			if($alreadyPersisted){
				$isLastChunkRetry = $isLastChunkFlag || (($chunkIndex + 1) >= $totalChunks);
				if($totalSize > 0 && $currentSize >= $totalSize){
					$isLastChunkRetry = true;
				}
				if(!$isLastChunkRetry){
					echo json_encode(array(
						"status"=>"partial",
						"msg"=>'Chunk duplicado ignorado ' . ($chunkIndex + 1) . '/' . $totalChunks,
						"next_offset"=>$currentSize,
						"hash"=> $this->security->get_csrf_hash(),
						"token"=> $this->security->get_csrf_token_name()
					));
					return;
				}

				$skipWriteBecauseAlreadyPersisted = true;
			}else{
				echo json_encode(array(
					"status"=>"resync",
					"msg"=>'Desfase detectado en chunks para ' . html_escape($originalName),
					"next_offset"=>$currentSize,
					"hash"=> $this->security->get_csrf_hash(),
					"token"=> $this->security->get_csrf_token_name()
				));
				return;
			}
		}

		if($chunkOffset > $currentSize){
			echo json_encode(array(
				"status"=>"resync",
				"msg"=>"Offset de chunk invalido para " . html_escape($originalName),
				"next_offset"=>$currentSize,
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		$written = 0;
		if(!$skipWriteBecauseAlreadyPersisted){
			$sourceHandle = @fopen($chunkTmp, 'rb');
			$targetHandle = @fopen($chunkPath, 'c+b');
			if($sourceHandle === false || $targetHandle === false){
				if(is_resource($sourceHandle)){ @fclose($sourceHandle); }
				if(is_resource($targetHandle)){ @fclose($targetHandle); }
				echo json_encode(array(
					"status"=>"unsuccess",
					"msg"=>"No se pudo almacenar temporalmente el chunk",
					"hash"=> $this->security->get_csrf_hash(),
					"token"=> $this->security->get_csrf_token_name()
				));
				return;
			}

			if(!@flock($targetHandle, LOCK_EX)){
				@fclose($sourceHandle);
				@fclose($targetHandle);
				echo json_encode(array(
					"status"=>"unsuccess",
					"msg"=>"No se pudo bloquear el archivo temporal del chunk",
					"hash"=> $this->security->get_csrf_hash(),
					"token"=> $this->security->get_csrf_token_name()
				));
				return;
			}

			@fseek($targetHandle, $chunkOffset);
			while(!feof($sourceHandle)){
				$buffer = fread($sourceHandle, 8192);
				if($buffer === false || $buffer === ''){
					continue;
				}

				$bufferLength = strlen($buffer);
				$offset = 0;
				while($offset < $bufferLength){
					$w = fwrite($targetHandle, substr($buffer, $offset));
					if($w === false || $w === 0){
						$written = -1;
						break 2;
					}
					$offset += $w;
					$written += $w;
				}
			}

			@fflush($targetHandle);
			@flock($targetHandle, LOCK_UN);
			@fclose($sourceHandle);
			@fclose($targetHandle);
		}

		if($written < 0){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>"No se pudo escribir un chunk en disco",
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		if(!$skipWriteBecauseAlreadyPersisted && $incomingChunkSize > 0 && $written !== $incomingChunkSize){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>"No se pudo persistir el chunk completo en disco",
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		$confirmedSize = is_file($chunkPath) ? (int)@filesize($chunkPath) : ($chunkOffset + max(0, $written));
		$isLastChunk = false;
		if($totalSize > 0 && $chunkEnd > 0){
			$isLastChunk = ($chunkEnd >= $totalSize);
		}else if($totalSize > 0 && $confirmedSize >= $totalSize){
			$isLastChunk = true;
		}else if($isLastChunkFlag){
			$isLastChunk = true;
		}else{
			$isLastChunk = (($chunkIndex + 1) >= $totalChunks);
		}

		if(!$isLastChunk){
			$newSize = $confirmedSize;
			echo json_encode(array(
				"status"=>"partial",
				"msg"=>'Chunk ' . ($chunkIndex + 1) . '/' . $totalChunks . ' recibido',
				"next_offset"=>$newSize,
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		$assembledSize = is_file($chunkPath) ? (int)@filesize($chunkPath) : 0;
		if($totalSize > 0 && $assembledSize !== $totalSize){
			if($assembledSize > $totalSize){
				@unlink($chunkPath);
			}
			echo json_encode(array(
				"status"=>"resync",
				"msg"=>'Tamano final inconsistente para ' . html_escape($originalName),
				"next_offset"=>$assembledSize,
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		$uploaded = 0;
		$uploadedItems = array();
		$errors = array();
		$allowedExtensions = array('dcm', 'dicom', 'zip', 'pdf', 'jpg', 'png');
		$allowedExtractedExtensions = array('dcm', 'dicom', 'pdf', 'jpg', 'png');
		$maxBytes = $this->getMaxUploadFileBytes();

		$processedOk = $this->processStoredFileUpload(
			$chunkPath,
			$originalName,
			$solicitudId,
			$userId,
			$maxBytes,
			$allowedExtensions,
			$allowedExtractedExtensions,
			$uploaded,
			$uploadedItems,
			$errors,
			0
		);

		@unlink($chunkPath);

		if(!$processedOk || $uploaded <= 0){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=> !empty($errors) ? implode('<br>', $errors) : 'No se ha subido ningun archivo valido',
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		$statusChanged = false;
		if((int)$solicitud->ESO_CO_ID < 6){
			$this->Solicitudmodel->updateElement(array('ESO_CO_ID' => 6), $solicitudId);
			$statusChanged = true;
		}

		if($statusChanged){
			$this->notifyAdminEstudioSubido($solicitudId, isset($solicitud->SOL_DS_NOMBRE) ? (string)$solicitud->SOL_DS_NOMBRE : '');
		}

		$msg = 'Archivo subido correctamente por chunks (' . $uploaded . ')';
		if(!empty($errors)){
			$msg .= '<br>Algunas entradas no se pudieron procesar:<br>' . implode('<br>', $errors);
		}

		echo json_encode(array(
			"status"=>"success",
			"msg"=>$msg,
			"uploaded_count"=>$uploaded,
			"uploaded_files"=>$uploadedItems,
			"hash"=> $this->security->get_csrf_hash(),
			"token"=> $this->security->get_csrf_token_name()
		));
	}

	public function guardarDatosCliente(){
		if($this->session->userdata('logged')!=TRUE || (int)$this->session->userdata('perfil')!==4){
			echo "";
			return;
		}

		$this->form_validation->set_rules('ele_id', 'solicitud', 'required|integer');
		$this->form_validation->set_rules('ele_solicitante_tipo', 'tipo solicitante', 'required|in_list[PACIENTE,TUTOR]');
		$this->form_validation->set_rules('ele_pac_nombre', 'nombre paciente', 'required|trim');
		$this->form_validation->set_rules('ele_pac_apellido1', 'primer apellido paciente', 'required|trim');
		$this->form_validation->set_rules('ele_pac_fecha_nacimiento', 'fecha nacimiento paciente', 'required|trim');
		$this->form_validation->set_rules('ele_pac_sexo', 'sexo paciente', 'required|in_list[M,F,O]');
		$this->form_validation->set_rules('ele_pac_tipo_documento', 'tipo documento paciente', 'required|in_list[DNI,NIE,PASAPORTE]');
		$this->form_validation->set_rules('ele_pac_documento', 'documento paciente', 'required|trim');
		$this->form_validation->set_rules('ele_pac_pais', 'pais paciente', 'required|trim');
		$this->form_validation->set_rules('ele_pac_provincia', 'provincia paciente', 'required|trim');
		$this->form_validation->set_rules('ele_pac_poblacion', 'poblacion paciente', 'required|trim');
		$this->form_validation->set_rules('ele_pac_domicilio', 'domicilio paciente', 'required|trim');
		$this->form_validation->set_rules('ele_pac_cp', 'codigo postal paciente', 'required|trim');
		$this->form_validation->set_rules('ele_pac_email', 'email paciente', 'required|trim|valid_email');
		$this->form_validation->set_rules('ele_pac_telefono', 'telefono paciente', 'required|trim');

		$tipoSolicitante = strtoupper(trim((string)$this->input->post('ele_solicitante_tipo', TRUE)));
		if($tipoSolicitante === 'TUTOR'){
			$this->form_validation->set_rules('ele_tut_nombre', 'nombre tutor', 'required|trim');
			$this->form_validation->set_rules('ele_tut_apellido1', 'primer apellido tutor', 'required|trim');
			$this->form_validation->set_rules('ele_tut_fecha_nacimiento', 'fecha nacimiento tutor', 'required|trim');
			$this->form_validation->set_rules('ele_tut_sexo', 'sexo tutor', 'required|in_list[M,F,O]');
			$this->form_validation->set_rules('ele_tut_tipo_documento', 'tipo documento tutor', 'required|in_list[DNI,NIE,PASAPORTE]');
			$this->form_validation->set_rules('ele_tut_documento', 'documento tutor', 'required|trim');
			$this->form_validation->set_rules('ele_tut_pais', 'pais tutor', 'required|trim');
			$this->form_validation->set_rules('ele_tut_provincia', 'provincia tutor', 'required|trim');
			$this->form_validation->set_rules('ele_tut_poblacion', 'poblacion tutor', 'required|trim');
			$this->form_validation->set_rules('ele_tut_domicilio', 'domicilio tutor', 'required|trim');
			$this->form_validation->set_rules('ele_tut_cp', 'codigo postal tutor', 'required|trim');
			$this->form_validation->set_rules('ele_tut_email', 'email tutor', 'required|trim|valid_email');
			$this->form_validation->set_rules('ele_tut_telefono', 'telefono tutor', 'required|trim');
		}

		$this->form_validation->set_message('required','Debes rellenar el campo '. ' %s');
		$this->form_validation->set_message('in_list','El valor indicado no es valido para '. ' %s');

		if($this->form_validation->run()==FALSE){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>validation_errors(),
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		$userId = (int)$this->session->userdata('id');
		$solicitudId = (int)$this->input->post('ele_id', TRUE);
		$fechaNacimientoPaciente = trim((string)$this->input->post('ele_pac_fecha_nacimiento', TRUE));
		$edadPaciente = $this->calculateAge($fechaNacimientoPaciente);

		if($edadPaciente < 0){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>"La fecha de nacimiento del paciente no es valida",
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		if($edadPaciente < 18 && $tipoSolicitante !== 'TUTOR'){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>"Si el paciente es menor de 18 anos, debes seleccionar tipo solicitante TUTOR",
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		$solicitud = $this->Solicitudmodel->getClientSolicitudById($userId, $solicitudId);

		if($solicitud === false){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>"La solicitud indicada no existe o no pertenece al cliente autenticado",
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		if((int)$solicitud->ESO_CO_ID < 3){
			echo json_encode(array(
				"status"=>"unsuccess",
				"msg"=>"La solicitud aun no esta disponible para completar datos",
				"hash"=> $this->security->get_csrf_hash(),
				"token"=> $this->security->get_csrf_token_name()
			));
			return;
		}

		$updateData = $this->buildClientUpdateData($tipoSolicitante);

		$preview = clone $solicitud;
		foreach($updateData as $field => $value){
			$preview->{$field} = $value;
		}

		$isComplete = $this->isClientInfoComplete($preview);
		if($isComplete && (int)$solicitud->ESO_CO_ID < 5){
			$updateData['ESO_CO_ID'] = 5;
		}

		$this->Solicitudmodel->updateElement($updateData, $solicitudId);

		echo json_encode(array(
			"status"=>"success",
			"msg"=> $isComplete ? 'Datos guardados correctamente. Tu solicitud pasa a estado Solicitado estudio.' : 'Datos guardados correctamente.',
			"hash"=> $this->security->get_csrf_hash(),
			"token"=> $this->security->get_csrf_token_name()
		));
	}

	public function redsysNotify(){
		$result = $this->processRedsysResponse('notify');
		echo ($result['processed'] ? 'OK' : 'KO');
	}

	public function redsysReturnOk(){
		$result = $this->processRedsysResponse('ok');
		$solicitudId = isset($result['solicitud_id']) ? (int)$result['solicitud_id'] : 0;

		if($solicitudId > 0){
			redirect(site_url('panel') . '?solicitud=' . $solicitudId);
			return;
		}

		redirect(site_url('panel'));
	}

	public function redsysReturnKo(){
		$result = $this->processRedsysResponse('ko');
		$solicitudId = isset($result['solicitud_id']) ? (int)$result['solicitud_id'] : 0;

		if($solicitudId > 0){
			redirect(site_url('panel') . '?solicitud=' . $solicitudId);
			return;
		}

		redirect(site_url('panel'));
	}

	private function resolveClientStatus($solicitud){
		$estadoId = (int)$solicitud->ESO_CO_ID;

		if($estadoId === 3){
			if($this->isClientInfoComplete($solicitud)){
				return array('id' => 5, 'name' => $this->getClientStateName(5));
			}

			return array('id' => 4, 'name' => $this->getClientStateName(4));
		}

		return array('id' => $estadoId, 'name' => $this->getClientStateName($estadoId));
	}

	private function isClientInfoComplete($solicitud){
		$required = array(
			'SOL_DS_SOLICITANTE_TIPO',
			'SOL_DS_PAC_NOMBRE',
			'SOL_DS_PAC_APELLIDO1',
			'SOL_DT_PAC_FECHA_NACIMIENTO',
			'SOL_DS_PAC_SEXO',
			'SOL_DS_PAC_TIPO_DOCUMENTO',
			'SOL_DS_PAC_DOCUMENTO',
			'SOL_DS_PAC_PAIS',
			'SOL_DS_PAC_PROVINCIA',
			'SOL_DS_PAC_POBLACION',
			'SOL_DS_PAC_DOMICILIO',
			'SOL_DS_PAC_COD_POSTAL',
			'SOL_DS_PAC_EMAIL',
			'SOL_DS_PAC_TELEFONO',
		);

		foreach($required as $field){
			if(!isset($solicitud->{$field}) || trim((string)$solicitud->{$field}) === ''){
				return false;
			}
		}

		if(isset($solicitud->SOL_DS_SOLICITANTE_TIPO) && strtoupper((string)$solicitud->SOL_DS_SOLICITANTE_TIPO) === 'TUTOR'){
			$tutorRequired = array(
				'SOL_DS_TUT_NOMBRE',
				'SOL_DS_TUT_APELLIDO1',
				'SOL_DT_TUT_FECHA_NACIMIENTO',
				'SOL_DS_TUT_SEXO',
				'SOL_DS_TUT_TIPO_DOCUMENTO',
				'SOL_DS_TUT_DOCUMENTO',
				'SOL_DS_TUT_PAIS',
				'SOL_DS_TUT_PROVINCIA',
				'SOL_DS_TUT_POBLACION',
				'SOL_DS_TUT_DOMICILIO',
				'SOL_DS_TUT_COD_POSTAL',
				'SOL_DS_TUT_EMAIL',
				'SOL_DS_TUT_TELEFONO',
			);

			foreach($tutorRequired as $field){
				if(!isset($solicitud->{$field}) || trim((string)$solicitud->{$field}) === ''){
					return false;
				}
			}
		}

		return true;
	}

	private function getClientStateName($stateId){
		$map = array(
			1 => 'Leed',
			2 => 'Solicitado Pago',
			3 => 'Pagado',
			4 => 'Solicitada informacion',
			5 => 'Solicitado estudio',
			6 => 'Estudio subido',
			7 => 'Generado informe',
			8 => 'Finalizado',
		);

		return isset($map[$stateId]) ? $map[$stateId] : 'Estado desconocido';
	}

	private function formatDate($rawDate){
		if(empty($rawDate)){
			return '-';
		}

		$ts = strtotime((string)$rawDate);
		if($ts === false){
			return '-';
		}

		return date('d-m-Y H:i', $ts);
	}

	private function composeSolicitudCode($clientId, $solicitudId){
		$clientPart = str_pad((string)((int)$clientId), 4, '0', STR_PAD_LEFT);
		$solicitudPart = str_pad((string)((int)$solicitudId), 5, '0', STR_PAD_LEFT);

		return '2OP-' . $clientPart . $solicitudPart;
	}

	private function hasPagoCompletedDateColumn(){
		return $this->db->field_exists('SOL_DT_PAGO_REALIZADO', 't_sol_solicitudes');
	}

	private function getDefaultClientFormData(){
		return array(
			'solicitante_tipo' => 'PACIENTE',
			'pac_nombre' => '',
			'pac_apellido1' => '',
			'pac_apellido2' => '',
			'pac_fecha_nacimiento' => '',
			'pac_sexo' => '',
			'pac_tipo_documento' => '',
			'pac_documento' => '',
			'pac_pais' => '',
			'pac_provincia' => '',
			'pac_poblacion' => '',
			'pac_domicilio' => '',
			'pac_cp' => '',
			'pac_email' => '',
			'pac_telefono' => '',
			'tut_nombre' => '',
			'tut_apellido1' => '',
			'tut_apellido2' => '',
			'tut_fecha_nacimiento' => '',
			'tut_sexo' => '',
			'tut_tipo_documento' => '',
			'tut_documento' => '',
			'tut_pais' => '',
			'tut_provincia' => '',
			'tut_poblacion' => '',
			'tut_domicilio' => '',
			'tut_cp' => '',
			'tut_email' => '',
			'tut_telefono' => '',
		);
	}

	private function extractClientFormData($solicitud){
		$data = $this->getDefaultClientFormData();

		$data['solicitante_tipo'] = isset($solicitud->SOL_DS_SOLICITANTE_TIPO) ? strtoupper((string)$solicitud->SOL_DS_SOLICITANTE_TIPO) : 'PACIENTE';
		$data['pac_nombre'] = isset($solicitud->SOL_DS_PAC_NOMBRE) ? (string)$solicitud->SOL_DS_PAC_NOMBRE : '';
		$data['pac_apellido1'] = isset($solicitud->SOL_DS_PAC_APELLIDO1) ? (string)$solicitud->SOL_DS_PAC_APELLIDO1 : '';
		$data['pac_apellido2'] = isset($solicitud->SOL_DS_PAC_APELLIDO2) ? (string)$solicitud->SOL_DS_PAC_APELLIDO2 : '';
		$data['pac_fecha_nacimiento'] = isset($solicitud->SOL_DT_PAC_FECHA_NACIMIENTO) ? substr((string)$solicitud->SOL_DT_PAC_FECHA_NACIMIENTO, 0, 10) : '';
		$data['pac_sexo'] = isset($solicitud->SOL_DS_PAC_SEXO) ? (string)$solicitud->SOL_DS_PAC_SEXO : '';
		$data['pac_tipo_documento'] = isset($solicitud->SOL_DS_PAC_TIPO_DOCUMENTO) ? (string)$solicitud->SOL_DS_PAC_TIPO_DOCUMENTO : '';
		$data['pac_documento'] = isset($solicitud->SOL_DS_PAC_DOCUMENTO) ? (string)$solicitud->SOL_DS_PAC_DOCUMENTO : '';
		$data['pac_pais'] = isset($solicitud->SOL_DS_PAC_PAIS) ? (string)$solicitud->SOL_DS_PAC_PAIS : '';
		$data['pac_provincia'] = isset($solicitud->SOL_DS_PAC_PROVINCIA) ? (string)$solicitud->SOL_DS_PAC_PROVINCIA : '';
		$data['pac_poblacion'] = isset($solicitud->SOL_DS_PAC_POBLACION) ? (string)$solicitud->SOL_DS_PAC_POBLACION : '';
		$data['pac_domicilio'] = isset($solicitud->SOL_DS_PAC_DOMICILIO) ? (string)$solicitud->SOL_DS_PAC_DOMICILIO : '';
		$data['pac_cp'] = isset($solicitud->SOL_DS_PAC_COD_POSTAL) ? (string)$solicitud->SOL_DS_PAC_COD_POSTAL : '';
		$data['pac_email'] = isset($solicitud->SOL_DS_PAC_EMAIL) ? (string)$solicitud->SOL_DS_PAC_EMAIL : '';
		$data['pac_telefono'] = isset($solicitud->SOL_DS_PAC_TELEFONO) ? (string)$solicitud->SOL_DS_PAC_TELEFONO : '';
		$data['tut_nombre'] = isset($solicitud->SOL_DS_TUT_NOMBRE) ? (string)$solicitud->SOL_DS_TUT_NOMBRE : '';
		$data['tut_apellido1'] = isset($solicitud->SOL_DS_TUT_APELLIDO1) ? (string)$solicitud->SOL_DS_TUT_APELLIDO1 : '';
		$data['tut_apellido2'] = isset($solicitud->SOL_DS_TUT_APELLIDO2) ? (string)$solicitud->SOL_DS_TUT_APELLIDO2 : '';
		$data['tut_fecha_nacimiento'] = isset($solicitud->SOL_DT_TUT_FECHA_NACIMIENTO) ? substr((string)$solicitud->SOL_DT_TUT_FECHA_NACIMIENTO, 0, 10) : '';
		$data['tut_sexo'] = isset($solicitud->SOL_DS_TUT_SEXO) ? (string)$solicitud->SOL_DS_TUT_SEXO : '';
		$data['tut_tipo_documento'] = isset($solicitud->SOL_DS_TUT_TIPO_DOCUMENTO) ? (string)$solicitud->SOL_DS_TUT_TIPO_DOCUMENTO : '';
		$data['tut_documento'] = isset($solicitud->SOL_DS_TUT_DOCUMENTO) ? (string)$solicitud->SOL_DS_TUT_DOCUMENTO : '';
		$data['tut_pais'] = isset($solicitud->SOL_DS_TUT_PAIS) ? (string)$solicitud->SOL_DS_TUT_PAIS : '';
		$data['tut_provincia'] = isset($solicitud->SOL_DS_TUT_PROVINCIA) ? (string)$solicitud->SOL_DS_TUT_PROVINCIA : '';
		$data['tut_poblacion'] = isset($solicitud->SOL_DS_TUT_POBLACION) ? (string)$solicitud->SOL_DS_TUT_POBLACION : '';
		$data['tut_domicilio'] = isset($solicitud->SOL_DS_TUT_DOMICILIO) ? (string)$solicitud->SOL_DS_TUT_DOMICILIO : '';
		$data['tut_cp'] = isset($solicitud->SOL_DS_TUT_COD_POSTAL) ? (string)$solicitud->SOL_DS_TUT_COD_POSTAL : '';
		$data['tut_email'] = isset($solicitud->SOL_DS_TUT_EMAIL) ? (string)$solicitud->SOL_DS_TUT_EMAIL : '';
		$data['tut_telefono'] = isset($solicitud->SOL_DS_TUT_TELEFONO) ? (string)$solicitud->SOL_DS_TUT_TELEFONO : '';

		if($data['solicitante_tipo'] !== 'TUTOR'){
			$data['solicitante_tipo'] = 'PACIENTE';
		}

		return $data;
	}

	private function buildClientUpdateData($tipoSolicitante){
		$tipoSolicitante = ($tipoSolicitante === 'TUTOR') ? 'TUTOR' : 'PACIENTE';

		$data = array(
			'SOL_DS_SOLICITANTE_TIPO' => $tipoSolicitante,
			'SOL_DS_PAC_NOMBRE' => trim((string)$this->input->post('ele_pac_nombre', TRUE)),
			'SOL_DS_PAC_APELLIDO1' => trim((string)$this->input->post('ele_pac_apellido1', TRUE)),
			'SOL_DS_PAC_APELLIDO2' => trim((string)$this->input->post('ele_pac_apellido2', TRUE)),
			'SOL_DT_PAC_FECHA_NACIMIENTO' => trim((string)$this->input->post('ele_pac_fecha_nacimiento', TRUE)),
			'SOL_DS_PAC_SEXO' => trim((string)$this->input->post('ele_pac_sexo', TRUE)),
			'SOL_DS_PAC_TIPO_DOCUMENTO' => trim((string)$this->input->post('ele_pac_tipo_documento', TRUE)),
			'SOL_DS_PAC_DOCUMENTO' => trim((string)$this->input->post('ele_pac_documento', TRUE)),
			'SOL_DS_PAC_PAIS' => trim((string)$this->input->post('ele_pac_pais', TRUE)),
			'SOL_DS_PAC_PROVINCIA' => trim((string)$this->input->post('ele_pac_provincia', TRUE)),
			'SOL_DS_PAC_POBLACION' => trim((string)$this->input->post('ele_pac_poblacion', TRUE)),
			'SOL_DS_PAC_DOMICILIO' => trim((string)$this->input->post('ele_pac_domicilio', TRUE)),
			'SOL_DS_PAC_COD_POSTAL' => trim((string)$this->input->post('ele_pac_cp', TRUE)),
			'SOL_DS_PAC_EMAIL' => trim((string)$this->input->post('ele_pac_email', TRUE)),
			'SOL_DS_PAC_TELEFONO' => trim((string)$this->input->post('ele_pac_telefono', TRUE)),
			'SOL_DS_TUT_NOMBRE' => trim((string)$this->input->post('ele_tut_nombre', TRUE)),
			'SOL_DS_TUT_APELLIDO1' => trim((string)$this->input->post('ele_tut_apellido1', TRUE)),
			'SOL_DS_TUT_APELLIDO2' => trim((string)$this->input->post('ele_tut_apellido2', TRUE)),
			'SOL_DT_TUT_FECHA_NACIMIENTO' => trim((string)$this->input->post('ele_tut_fecha_nacimiento', TRUE)),
			'SOL_DS_TUT_SEXO' => trim((string)$this->input->post('ele_tut_sexo', TRUE)),
			'SOL_DS_TUT_TIPO_DOCUMENTO' => trim((string)$this->input->post('ele_tut_tipo_documento', TRUE)),
			'SOL_DS_TUT_DOCUMENTO' => trim((string)$this->input->post('ele_tut_documento', TRUE)),
			'SOL_DS_TUT_PAIS' => trim((string)$this->input->post('ele_tut_pais', TRUE)),
			'SOL_DS_TUT_PROVINCIA' => trim((string)$this->input->post('ele_tut_provincia', TRUE)),
			'SOL_DS_TUT_POBLACION' => trim((string)$this->input->post('ele_tut_poblacion', TRUE)),
			'SOL_DS_TUT_DOMICILIO' => trim((string)$this->input->post('ele_tut_domicilio', TRUE)),
			'SOL_DS_TUT_COD_POSTAL' => trim((string)$this->input->post('ele_tut_cp', TRUE)),
			'SOL_DS_TUT_EMAIL' => trim((string)$this->input->post('ele_tut_email', TRUE)),
			'SOL_DS_TUT_TELEFONO' => trim((string)$this->input->post('ele_tut_telefono', TRUE)),
		);

		if($tipoSolicitante !== 'TUTOR'){
			$data['SOL_DS_TUT_NOMBRE'] = '';
			$data['SOL_DS_TUT_APELLIDO1'] = '';
			$data['SOL_DS_TUT_APELLIDO2'] = '';
			$data['SOL_DT_TUT_FECHA_NACIMIENTO'] = '';
			$data['SOL_DS_TUT_SEXO'] = '';
			$data['SOL_DS_TUT_TIPO_DOCUMENTO'] = '';
			$data['SOL_DS_TUT_DOCUMENTO'] = '';
			$data['SOL_DS_TUT_PAIS'] = '';
			$data['SOL_DS_TUT_PROVINCIA'] = '';
			$data['SOL_DS_TUT_POBLACION'] = '';
			$data['SOL_DS_TUT_DOMICILIO'] = '';
			$data['SOL_DS_TUT_COD_POSTAL'] = '';
			$data['SOL_DS_TUT_EMAIL'] = '';
			$data['SOL_DS_TUT_TELEFONO'] = '';
		}

		return $data;
	}

	private function getRedsysConfig(){
		$config = $this->config->item('redsys', 'redsys');
		$config = is_array($config) ? $config : array();

		$mode = isset($config['mode']) && $config['mode'] === 'production' ? 'production' : 'test';
		$gatewayUrl = ($mode === 'production')
			? 'https://sis.redsys.es/sis/realizarPago'
			: 'https://sis-t.redsys.es:25443/sis/realizarPago';

		return array(
			'enabled' => isset($config['enabled']) ? (bool)$config['enabled'] : false,
			'mode' => $mode,
			'gateway_url' => $gatewayUrl,
			'merchant_code' => isset($config['merchant_code']) ? trim((string)$config['merchant_code']) : '',
			'terminal' => isset($config['terminal']) ? trim((string)$config['terminal']) : '1',
			'currency' => isset($config['currency']) ? trim((string)$config['currency']) : '978',
			'transaction_type' => isset($config['transaction_type']) ? trim((string)$config['transaction_type']) : '0',
			'secret_key' => isset($config['secret_key']) ? trim((string)$config['secret_key']) : '',
			'merchant_url' => site_url('panel/pago/notificar'),
			'url_ok' => site_url('panel/pago/ok'),
			'url_ko' => site_url('panel/pago/ko'),
		);
	}

	private function processRedsysResponse($channel = 'notify'){
		$merchantParameters = (string)$this->input->post('Ds_MerchantParameters', TRUE);
		$signature = (string)$this->input->post('Ds_Signature', TRUE);
		$rawInput = (string)$this->input->raw_input_stream;

		if($merchantParameters === ''){
			$merchantParameters = (string)$this->input->get('Ds_MerchantParameters', TRUE);
			$signature = (string)$this->input->get('Ds_Signature', TRUE);
		}

		if($merchantParameters === '' || $signature === ''){
			$this->logRedsysAttempt($channel, 'EMPTY_PARAMS', array(
				'payload' => '',
				'raw' => $rawInput,
			));
			return array('processed' => false, 'solicitud_id' => 0);
		}

		$payload = $this->decodeRedsysMerchantParameters($merchantParameters);
		if($payload === false){
			$this->logRedsysAttempt($channel, 'INVALID_PAYLOAD', array(
				'payload' => '',
				'raw' => $rawInput,
			));
			return array('processed' => false, 'solicitud_id' => 0);
		}

		$order = isset($payload['Ds_Order']) ? (string)$payload['Ds_Order'] : '';
		if($order === '' && isset($payload['Ds_Merchant_Order'])){
			$order = (string)$payload['Ds_Merchant_Order'];
		}

		$redsys = $this->getRedsysConfig();
		if($redsys['secret_key'] === ''){
			$this->logRedsysAttempt($channel, 'MISSING_SECRET', array(
				'order' => $order,
				'payload' => json_encode($payload),
				'raw' => $rawInput,
			));
			return array('processed' => false, 'solicitud_id' => 0);
		}

		$expectedSignature = $this->createRedsysSignature($merchantParameters, $order, $redsys['secret_key']);
		if(!$this->sameRedsysSignature($expectedSignature, $signature)){
			$this->logRedsysAttempt($channel, 'INVALID_SIGNATURE', array(
				'order' => $order,
				'payload' => json_encode($payload),
				'raw' => $rawInput,
			));
			return array('processed' => false, 'solicitud_id' => 0);
		}

		$responseCode = isset($payload['Ds_Response']) ? (int)$payload['Ds_Response'] : 999;
		$solicitudId = $this->extractSolicitudIdFromMerchantData(isset($payload['Ds_MerchantData']) ? $payload['Ds_MerchantData'] : '');

		if($solicitudId <= 0 || $responseCode < 0 || $responseCode > 99){
			$this->logRedsysAttempt($channel, 'KO', array(
				'solicitud_id' => $solicitudId,
				'order' => $order,
				'response_code' => (string)$responseCode,
				'payload' => json_encode($payload),
				'raw' => $rawInput,
			));
			return array('processed' => true, 'solicitud_id' => $solicitudId);
		}

		$solicitud = $this->Solicitudmodel->getElementById($solicitudId);
		if($solicitud !== false && (int)$solicitud->ESO_CO_ID === 2){
			$updateData = array('ESO_CO_ID' => 3);
			if($this->hasPagoCompletedDateColumn()){
				$updateData['SOL_DT_PAGO_REALIZADO'] = date('Y-m-d H:i:s');
			}
			$this->Solicitudmodel->updateElement($updateData, $solicitudId);
		}

		$this->logRedsysAttempt($channel, 'OK', array(
			'solicitud_id' => $solicitudId,
			'order' => $order,
			'response_code' => (string)$responseCode,
			'payload' => json_encode($payload),
			'raw' => $rawInput,
		));

		return array('processed' => true, 'solicitud_id' => $solicitudId);
	}

	private function logRedsysAttempt($channel, $status, $context = array()){
		if(!$this->Redsysintentosmodel->canInsert()){
			return;
		}

		$data = array(
			'SOL_CO_ID' => isset($context['solicitud_id']) ? (int)$context['solicitud_id'] : null,
			'USR_CO_ID' => isset($context['user_id']) ? (int)$context['user_id'] : null,
			'RPI_DS_CANAL' => strtoupper(substr((string)$channel, 0, 20)),
			'RPI_DS_ESTADO' => strtoupper(substr((string)$status, 0, 30)),
			'RPI_DS_ORDER' => isset($context['order']) ? substr((string)$context['order'], 0, 20) : null,
			'RPI_DS_RESPONSE_CODE' => isset($context['response_code']) ? substr((string)$context['response_code'], 0, 8) : null,
			'RPI_DS_IP' => substr((string)$this->input->ip_address(), 0, 45),
			'RPI_TX_PAYLOAD' => isset($context['payload']) ? (string)$context['payload'] : null,
			'RPI_TX_RAW' => isset($context['raw']) ? (string)$context['raw'] : null,
			'RPI_DT_CREATE' => date('Y-m-d H:i:s'),
			'RPI_BL_ENABLE' => 1,
			'RPI_BL_DELETE' => 0,
		);

		$this->Redsysintentosmodel->insertAttempt($data);
	}

	private function decodeRedsysMerchantParameters($merchantParameters){
		$json = base64_decode($merchantParameters, true);
		if($json === false){
			return false;
		}

		$data = json_decode($json, true);
		if(!is_array($data)){
			return false;
		}

		foreach($data as $key => $value){
			if(is_string($value)){
				$data[$key] = rawurldecode($value);
			}
		}

		return $data;
	}

	private function createRedsysSignature($merchantParameters, $order, $secretKeyBase64){
		$key = base64_decode($secretKeyBase64, true);
		if($key === false){
			return '';
		}

		$order = strtoupper(trim((string)$order));
		$orderPadded = $order;
		$mod = strlen($orderPadded) % 8;
		if($mod !== 0){
			$orderPadded .= str_repeat("\0", 8 - $mod);
		}

		$derivedKey = openssl_encrypt($orderPadded, 'des-ede3-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, "\0\0\0\0\0\0\0\0");
		if($derivedKey === false){
			return '';
		}

		$hmac = hash_hmac('sha256', $merchantParameters, $derivedKey, true);
		return base64_encode($hmac);
	}

	private function sameRedsysSignature($expected, $received){
		$expected = $this->normalizeRedsysSignature($expected);
		$received = $this->normalizeRedsysSignature($received);

		if($expected === '' || $received === ''){
			return false;
		}

		if(function_exists('hash_equals')){
			return hash_equals($expected, $received);
		}

		return $expected === $received;
	}

	private function normalizeRedsysSignature($value){
		$value = trim((string)$value);
		$value = str_replace(array('-', '_'), array('+', '/'), $value);

		$mod = strlen($value) % 4;
		if($mod > 0){
			$value .= str_repeat('=', 4 - $mod);
		}

		return $value;
	}

	private function extractSolicitudIdFromMerchantData($merchantData){
		$raw = trim((string)$merchantData);
		if($raw === ''){
			return 0;
		}

		$raw = rawurldecode($raw);
		$raw = str_replace(array('-', '_'), array('+', '/'), $raw);
		$mod = strlen($raw) % 4;
		if($mod > 0){
			$raw .= str_repeat('=', 4 - $mod);
		}

		$decoded = base64_decode($raw, true);
		if($decoded === false){
			return 0;
		}

		$data = json_decode($decoded, true);
		if(!is_array($data) || !isset($data['solicitud_id'])){
			return 0;
		}

		return (int)$data['solicitud_id'];
	}

	private function formatRedsysAmount($importe){
		$raw = trim((string)$importe);
		if($raw === ''){
			return 0;
		}

		$raw = str_replace(' ', '', $raw);
		if(strpos($raw, ',') !== false && strpos($raw, '.') !== false){
			$raw = str_replace('.', '', $raw);
			$raw = str_replace(',', '.', $raw);
		}else if(strpos($raw, ',') !== false){
			$raw = str_replace(',', '.', $raw);
		}

		$normalized = $raw;
		if(!preg_match('/^\d+(\.\d{1,2})?$/', $normalized)){
			return 0;
		}

		$numeric = (float)$normalized;
		if($numeric <= 0){
			return 0;
		}

		return (int)round($numeric * 100);
	}

	private function buildRedsysOrder($solicitudId){
		$dayOfYear = str_pad((string)date('z'), 3, '0', STR_PAD_LEFT);
		$solicitudPart = str_pad((string)((int)$solicitudId), 7, '0', STR_PAD_LEFT);
		$randomPart = str_pad((string)mt_rand(0, 99), 2, '0', STR_PAD_LEFT);

		return $dayOfYear . $solicitudPart . $randomPart;
	}

	private function calculateAge($birthDate){
		$birthDate = trim((string)$birthDate);
		if($birthDate === ''){
			return -1;
		}

		$dt = DateTime::createFromFormat('Y-m-d', $birthDate);
		if(!$dt || $dt->format('Y-m-d') !== $birthDate){
			return -1;
		}

		$today = new DateTime('today');
		if($dt > $today){
			return -1;
		}

		$diff = $today->diff($dt);
		return (int)$diff->y;
	}

	private function getSolicitudUploadDirectory($solicitudId){
		return rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploadDocumentation' . DIRECTORY_SEPARATOR . (int)$solicitudId;
	}

	private function sanitizeUploadName($name){
		$name = trim((string)$name);
		$name = str_replace(array("\r", "\n", "\t"), ' ', $name);
		$name = preg_replace('/[^A-Za-z0-9._ -]/', '_', $name);
		if($name === ''){
			return 'archivo';
		}

		return substr($name, 0, 250);
	}

	private function buildStoredUploadName($solicitudId, $index, $extension){
		$base = 'sol' . (int)$solicitudId . '_' . date('YmdHis') . '_' . (int)$index . '_' . substr(sha1(uniqid((string)$solicitudId, true)), 0, 10);
		$ext = trim((string)$extension) !== '' ? '.' . trim((string)$extension) : '';
		return $base . $ext;
	}

	private function processZipUploadFile($zipPath, $zipOriginalName, $solicitudId, $userId, $maxBytes, $allowedExtractedExtensions, &$uploaded, &$uploadedItems, &$errors){
		if(!class_exists('ZipArchive')){
			$errors[] = 'No se puede procesar el ZIP ' . html_escape($zipOriginalName) . ': ZipArchive no esta disponible en el servidor';
			return 0;
		}

		$zip = new ZipArchive();
		$openResult = $zip->open($zipPath);
		if($openResult !== true){
			$errors[] = 'No se pudo abrir el ZIP ' . html_escape($zipOriginalName);
			return 0;
		}

		$uploadDir = $this->getSolicitudUploadDirectory($solicitudId);
		$processed = 0;
		$entryCounter = 0;
		$allowedExtractedExtensions = is_array($allowedExtractedExtensions) ? $allowedExtractedExtensions : array();

		for($entryIndex = 0; $entryIndex < $zip->numFiles; $entryIndex++){
			$stat = $zip->statIndex($entryIndex);
			if($stat === false || !isset($stat['name'])){
				continue;
			}

			$entryName = str_replace('\\', '/', (string)$stat['name']);
			if($entryName === '' || substr($entryName, -1) === '/'){
				continue;
			}

			$baseName = basename($entryName);
			if($baseName === '' || $baseName === '.' || $baseName === '..'){
				continue;
			}

			if(substr($baseName, 0, 1) === '.' || strpos($baseName, '._') === 0){
				continue;
			}

			$extension = strtolower(pathinfo($baseName, PATHINFO_EXTENSION));
			$hasAllowedExtension = in_array($extension, $allowedExtractedExtensions, true);

			$stream = $zip->getStream($stat['name']);
			if($stream === false){
				$errors[] = 'No se pudo leer una entrada del ZIP ' . html_escape($zipOriginalName);
				continue;
			}

			$tempPath = $uploadDir . DIRECTORY_SEPARATOR . 'tmp_zip_' . uniqid((string)$solicitudId, true);
			$tempHandle = @fopen($tempPath, 'wb');
			if($tempHandle === false){
				@fclose($stream);
				$errors[] = 'No se pudo crear temporal para procesar ZIP ' . html_escape($zipOriginalName);
				continue;
			}

			$currentSize = 0;
			$sizeExceeded = false;
			while(!feof($stream)){
				$buffer = fread($stream, 8192);
				if($buffer === false){
					$buffer = '';
				}
				if($buffer === ''){
					continue;
				}

				$currentSize += strlen($buffer);
				if($currentSize > $maxBytes){
					$sizeExceeded = true;
					break;
				}

				fwrite($tempHandle, $buffer);
			}

			@fclose($stream);
			@fclose($tempHandle);

			if($sizeExceeded || $currentSize <= 0){
				@unlink($tempPath);
				$errors[] = 'Tamano no valido para archivo dentro del ZIP: ' . html_escape($baseName);
				continue;
			}

			$isDicomWithoutExtension = false;
			if(!$hasAllowedExtension){
				if($extension === '' && $this->isDicomFileByContent($tempPath)){
					$isDicomWithoutExtension = true;
					$extension = 'dcm';
				}else{
					@unlink($tempPath);
					continue;
				}
			}

			$safeOriginal = $this->sanitizeUploadName($baseName);
			$storedName = $this->buildStoredUploadName($solicitudId, $entryCounter, $extension);
			$entryCounter++;
			$targetPath = $uploadDir . DIRECTORY_SEPARATOR . $storedName;

			if(!@rename($tempPath, $targetPath)){
				@unlink($tempPath);
				$errors[] = 'No se pudo mover archivo extraido del ZIP: ' . html_escape($baseName);
				continue;
			}

			$relativePath = 'uploadDocumentation/' . $solicitudId . '/' . $storedName;
			$insertData = array(
				'SOL_CO_ID' => $solicitudId,
				'USR_CO_ID' => $userId,
				'SAR_DS_NOMBRE_ORIGINAL' => $safeOriginal,
				'SAR_DS_NOMBRE_GUARDADO' => $storedName,
				'SAR_DS_RUTA' => $relativePath,
				'SAR_NM_TAM_BYTES' => (int)$currentSize,
				'SAR_DS_EXTENSION' => $extension,
				'SAR_DT_CREATE' => date('Y-m-d H:i:s'),
				'SAR_BL_ENABLE' => 1,
				'SAR_BL_DELETE' => 0,
			);

			if($this->Solicitudarchivosmodel->insertArchivo($insertData) === false){
				@unlink($targetPath);
				$errors[] = 'No se pudo registrar el archivo extraido del ZIP: ' . html_escape($baseName);
				continue;
			}

			$uploaded++;
			$processed++;
			$uploadedItems[] = array(
				'nombre_original' => $safeOriginal,
				'extension' => $isDicomWithoutExtension ? 'DICOM' : strtoupper($extension),
				'tam_bytes' => (int)$currentSize,
				'fecha' => $this->formatDate(date('Y-m-d H:i:s')),
			);
		}

		$zip->close();

		return $processed;
	}

	private function processStoredFileUpload($sourcePath, $originalName, $solicitudId, $userId, $maxBytes, $allowedExtensions, $allowedExtractedExtensions, &$uploaded, &$uploadedItems, &$errors, $index){
		$path = (string)$sourcePath;
		if($path === '' || !is_file($path)){
			$errors[] = 'No se pudo localizar el archivo temporal ' . html_escape($originalName);
			return false;
		}

		$size = (int)@filesize($path);
		if($size <= 0 || $size > $maxBytes){
			$errors[] = 'Tamano no valido para ' . html_escape($originalName);
			return false;
		}

		$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
		$isDicomWithoutExtension = false;
		if(!in_array($extension, $allowedExtensions, true)){
			if($extension === '' && $this->isDicomFileByContent($path)){
				$isDicomWithoutExtension = true;
				$extension = 'dcm';
			}else{
				$errors[] = 'Extension no permitida: ' . html_escape($originalName);
				return false;
			}
		}

		$uploadDir = $this->getSolicitudUploadDirectory($solicitudId);
		if(!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true)){
			$errors[] = 'No se pudo preparar el directorio de subida';
			return false;
		}

		if($extension === 'zip'){
			$zipStoredName = $this->buildStoredUploadName($solicitudId, (int)$index, 'zip');
			$zipPath = $uploadDir . DIRECTORY_SEPARATOR . $zipStoredName;
			if(!@rename($path, $zipPath)){
				$errors[] = 'No se pudo mover el ZIP ' . html_escape($originalName);
				return false;
			}

			$processedInZip = $this->processZipUploadFile(
				$zipPath,
				$originalName,
				$solicitudId,
				$userId,
				$maxBytes,
				$allowedExtractedExtensions,
				$uploaded,
				$uploadedItems,
				$errors
			);

			@unlink($zipPath);
			if($processedInZip <= 0){
				$errors[] = 'El ZIP no contiene ficheros validos procesables: ' . html_escape($originalName);
				return false;
			}

			return true;
		}

		$safeOriginal = $this->sanitizeUploadName($originalName);
		$storedName = $this->buildStoredUploadName($solicitudId, (int)$index, $extension);
		$targetPath = $uploadDir . DIRECTORY_SEPARATOR . $storedName;

		if(!@rename($path, $targetPath)){
			$errors[] = 'No se pudo mover el archivo ' . html_escape($originalName);
			return false;
		}

		$relativePath = 'uploadDocumentation/' . $solicitudId . '/' . $storedName;
		$insertData = array(
			'SOL_CO_ID' => $solicitudId,
			'USR_CO_ID' => $userId,
			'SAR_DS_NOMBRE_ORIGINAL' => $safeOriginal,
			'SAR_DS_NOMBRE_GUARDADO' => $storedName,
			'SAR_DS_RUTA' => $relativePath,
			'SAR_NM_TAM_BYTES' => $size,
			'SAR_DS_EXTENSION' => $extension,
			'SAR_DT_CREATE' => date('Y-m-d H:i:s'),
			'SAR_BL_ENABLE' => 1,
			'SAR_BL_DELETE' => 0,
		);

		if($this->Solicitudarchivosmodel->insertArchivo($insertData) === false){
			@unlink($targetPath);
			$errors[] = 'No se pudo registrar el archivo ' . html_escape($originalName);
			return false;
		}

		$uploaded++;
		$uploadedItems[] = array(
			'nombre_original' => $safeOriginal,
			'extension' => $isDicomWithoutExtension ? 'DICOM' : strtoupper($extension),
			'tam_bytes' => (int)$size,
			'fecha' => $this->formatDate(date('Y-m-d H:i:s')),
		);

		return true;
	}

	private function getChunkTempDirectory($solicitudId){
		return rtrim(APPPATH, '/\\') . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'chunks' . DIRECTORY_SEPARATOR . (int)$solicitudId;
	}

	private function notifyAdminEstudioSubido($solicitudId, $nombreCliente){
		$to = defined('EMAIL_ADMIN') ? EMAIL_ADMIN : '';
		if(trim((string)$to) === ''){
			return;
		}

		$this->load->library('Emailtemplate');
		$this->emailtemplate->sendEstudioSubidoAdmin($to, array(
			'request_code' => $solicitudId,
			'nombre' => $nombreCliente,
			'files_count' => $this->countSolicitudFiles($solicitudId),
			'url_panel' => site_url('admin/cA003_solicitudes'),
		), array(
			'from_email' => EMAIL_CONTACT,
			'from_name' => 'Portal 2OP',
			'reply_to' => EMAIL_REPLY,
		));
	}

	private function countSolicitudFiles($solicitudId){
		$result = $this->Solicitudarchivosmodel->getArchivosBySolicitud($solicitudId);
		if($result === false){
			return 0;
		}

		return (int)$result->num_rows();
	}

	private function applyLargeUploadRuntimeLimits(){
		@ini_set('upload_max_filesize', '1024M');
		@ini_set('post_max_size', '1600M');
		@ini_set('max_file_uploads', '2000');
		@ini_set('max_execution_time', '1800');
		@ini_set('max_input_time', '1800');
	}

	private function buildUploadErrorMessage($originalName, $errorCode){
		$safeName = html_escape((string)$originalName);
		$code = (int)$errorCode;

		if($code === 1){
			return 'Error al subir ' . $safeName . ': supera upload_max_filesize (' . ini_get('upload_max_filesize') . ')';
		}

		if($code === 2){
			return 'Error al subir ' . $safeName . ': supera MAX_FILE_SIZE definido en el formulario';
		}

		if($code === 3){
			return 'Error al subir ' . $safeName . ': subida parcial (UPLOAD_ERR_PARTIAL)';
		}

		if($code === 6){
			return 'Error al subir ' . $safeName . ': falta carpeta temporal en servidor (UPLOAD_ERR_NO_TMP_DIR)';
		}

		if($code === 7){
			return 'Error al subir ' . $safeName . ': no se pudo escribir en disco (UPLOAD_ERR_CANT_WRITE). ' . $this->buildUploadTempDiagnostic();
		}

		if($code === 8){
			return 'Error al subir ' . $safeName . ': extension PHP detuvo la subida (UPLOAD_ERR_EXTENSION)';
		}

		return 'Error al subir ' . $safeName . ': codigo ' . $code . '. Revisa upload_max_filesize=' . ini_get('upload_max_filesize') . ' y post_max_size=' . ini_get('post_max_size');
	}

	private function buildUploadTempDiagnostic(){
		$tempDir = $this->getUploadTempDir();
		$exists = is_dir($tempDir);
		$writable = $exists ? is_writable($tempDir) : false;
		$freeBytes = $exists ? @disk_free_space($tempDir) : false;
		$freeMb = is_numeric($freeBytes) ? (int)floor(((float)$freeBytes) / 1048576) : -1;

		$parts = array();
		$parts[] = 'tmp_dir=' . $tempDir;
		$parts[] = 'existe=' . ($exists ? 'si' : 'no');
		$parts[] = 'escribible=' . ($writable ? 'si' : 'no');
		if($freeMb >= 0){
			$parts[] = 'espacio_libre_mb=' . $freeMb;
		}

		return implode(', ', $parts);
	}

	private function getUploadTempDir(){
		$uploadTmpDir = trim((string)ini_get('upload_tmp_dir'));
		if($uploadTmpDir !== ''){
			return $uploadTmpDir;
		}

		return (string)sys_get_temp_dir();
	}

	private function iniSizeToBytes($value){
		$raw = trim((string)$value);
		if($raw === ''){
			return 0;
		}

		$unit = strtolower(substr($raw, -1));
		$number = (float)$raw;
		if($number <= 0){
			return 0;
		}

		if($unit === 'g'){
			$number *= 1024;
			$unit = 'm';
		}

		if($unit === 'm'){
			$number *= 1024;
			$unit = 'k';
		}

		if($unit === 'k'){
			$number *= 1024;
		}

		return (int)round($number);
	}

	private function getMaxUploadFileBytes(){
		$fromConfig = $this->config->item('upload_max_file_bytes');
		if(is_numeric($fromConfig)){
			$value = (int)$fromConfig;
			if($value > 0){
				return $value;
			}
		}

		return (int)(4 * 1024 * 1024 * 1024); // 4GB por archivo
	}

	private function isDicomFileByContent($filePath){
		$path = (string)$filePath;
		if($path === '' || !is_file($path) || !is_readable($path)){
			return false;
		}

		$handle = @fopen($path, 'rb');
		if($handle === false){
			return false;
		}

		$header = fread($handle, 132);
		@fclose($handle);

		if($header === false || strlen($header) < 132){
			return false;
		}

		return substr($header, 128, 4) === 'DICM';
	}

}

/* End of file cC001_panelControl.php */
/* Location: ./application/controllers/cliente/cC001_panelControl.php */
