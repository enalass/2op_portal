<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Solicitudarchivosmodel extends CI_Model {

	private static $TABLE = 't_sar_solicitudarchivos';
	private static $PK = 'SAR_CO_ID';
	private $tableChecked = false;
	private $tableExists = false;
	private $pacsColumnsChecked = false;

	const PACS_STATUS_PENDING = 0;
	const PACS_STATUS_OK = 1;
	const PACS_STATUS_ERROR = 2;
	const PACS_STATUS_PROCESSING = 3;

	private function ensureTableChecked(){
		if($this->tableChecked){
			return;
		}

		$this->tableExists = $this->db->table_exists(self::$TABLE);
		$this->tableChecked = true;
	}

	public function canUse(){
		$this->ensureTableChecked();
		return $this->tableExists;
	}

	public function ensurePacsColumns(){
		if(!$this->canUse()){
			return false;
		}

		if($this->pacsColumnsChecked){
			return true;
		}

		$columns = array(
			'SAR_NM_PACS_STATUS' => "ALTER TABLE " . self::$TABLE . " ADD COLUMN SAR_NM_PACS_STATUS TINYINT(4) NOT NULL DEFAULT 0",
			'SAR_DS_PACS_ERROR' => "ALTER TABLE " . self::$TABLE . " ADD COLUMN SAR_DS_PACS_ERROR TEXT NULL",
			'SAR_DT_PACS_PROCESADO' => "ALTER TABLE " . self::$TABLE . " ADD COLUMN SAR_DT_PACS_PROCESADO DATETIME NULL",
			'SAR_DS_PACS_PATIENT_ID' => "ALTER TABLE " . self::$TABLE . " ADD COLUMN SAR_DS_PACS_PATIENT_ID VARCHAR(32) NULL",
			'SAR_DS_PACS_DICOM_RUTA' => "ALTER TABLE " . self::$TABLE . " ADD COLUMN SAR_DS_PACS_DICOM_RUTA VARCHAR(500) NULL",
		);

		foreach($columns as $column => $sql){
			if(!$this->db->field_exists($column, self::$TABLE)){
				$this->db->query($sql);
			}
		}

		$this->pacsColumnsChecked = true;
		return true;
	}

	public function insertArchivo($data){
		if(!$this->canUse()){
			return false;
		}

		$this->db->insert(self::$TABLE, $data);
		return $this->db->insert_id();
	}

	public function getArchivosBySolicitud($solicitudId){
		if(!$this->canUse()){
			return false;
		}

		$this->db->where('SOL_CO_ID', (int)$solicitudId);
		$this->db->where('SAR_BL_DELETE', 0);
		$this->db->from(self::$TABLE);
		$this->db->order_by('SAR_DT_CREATE', 'DESC');

		$data = $this->db->get();
		if($data->num_rows() > 0){
			return $data;
		}

		return false;
	}

	public function getPacsProgressBySolicitud($solicitudId){
		if(!$this->canUse()){
			return false;
		}

		$this->ensurePacsColumns();

		$this->db->select('COUNT(*) AS total', false);
		$this->db->select('SUM(CASE WHEN SAR_NM_PACS_STATUS = ' . self::PACS_STATUS_OK . ' THEN 1 ELSE 0 END) AS ok_count', false);
		$this->db->select('SUM(CASE WHEN SAR_NM_PACS_STATUS = ' . self::PACS_STATUS_ERROR . ' THEN 1 ELSE 0 END) AS error_count', false);
		$this->db->select('SUM(CASE WHEN SAR_NM_PACS_STATUS = ' . self::PACS_STATUS_PROCESSING . ' THEN 1 ELSE 0 END) AS processing_count', false);
		$this->db->select('SUM(CASE WHEN SAR_NM_PACS_STATUS = ' . self::PACS_STATUS_PENDING . ' OR SAR_NM_PACS_STATUS IS NULL THEN 1 ELSE 0 END) AS pending_count', false);
		$this->db->from(self::$TABLE);
		$this->db->where('SOL_CO_ID', (int)$solicitudId);
		$this->db->where('SAR_BL_DELETE', 0);

		$query = $this->db->get();
		if($query->num_rows() <= 0){
			return array(
				'total' => 0,
				'ok' => 0,
				'error' => 0,
				'pending' => 0,
				'processing' => 0,
			);
		}

		$row = $query->row();
		return array(
			'total' => (int)$row->total,
			'ok' => (int)$row->ok_count,
			'error' => (int)$row->error_count,
			'pending' => (int)$row->pending_count,
			'processing' => (int)$row->processing_count,
		);
	}

	public function getNextPendingArchivoBySolicitud($solicitudId){
		if(!$this->canUse()){
			return false;
		}

		$this->ensurePacsColumns();

		$this->db->from(self::$TABLE);
		$this->db->where('SOL_CO_ID', (int)$solicitudId);
		$this->db->where('SAR_BL_DELETE', 0);
		$this->db->group_start();
		$this->db->where('SAR_NM_PACS_STATUS', self::PACS_STATUS_PENDING);
		$this->db->or_where('SAR_NM_PACS_STATUS IS NULL', null, false);
		$this->db->group_end();
		$this->db->order_by('SAR_DT_CREATE', 'ASC');
		$this->db->order_by(self::$PK, 'ASC');
		$this->db->limit(1);

		$query = $this->db->get();
		if($query->num_rows() <= 0){
			return false;
		}

		return $query->row();
	}

	public function markArchivoProcessing($archivoId){
		if(!$this->canUse()){
			return false;
		}

		$this->ensurePacsColumns();

		$this->db->where(self::$PK, (int)$archivoId);
		$this->db->where('SAR_BL_DELETE', 0);
		$this->db->update(self::$TABLE, array(
			'SAR_NM_PACS_STATUS' => self::PACS_STATUS_PROCESSING,
			'SAR_DS_PACS_ERROR' => null,
			'SAR_DT_PACS_PROCESADO' => null,
		));

		return $this->db->affected_rows() > 0;
	}

	public function markArchivoResult($archivoId, $status, $errorMessage = '', $processedAt = '', $patientId = '', $dicomPath = ''){
		if(!$this->canUse()){
			return false;
		}

		$this->ensurePacsColumns();

		$processedAt = trim((string)$processedAt);
		if($processedAt === ''){
			$processedAt = date('Y-m-d H:i:s');
		}

		$update = array(
			'SAR_NM_PACS_STATUS' => (int)$status,
			'SAR_DS_PACS_ERROR' => $errorMessage !== '' ? (string)$errorMessage : null,
			'SAR_DT_PACS_PROCESADO' => $processedAt,
			'SAR_DS_PACS_PATIENT_ID' => $patientId !== '' ? (string)$patientId : null,
			'SAR_DS_PACS_DICOM_RUTA' => $dicomPath !== '' ? (string)$dicomPath : null,
		);

		$this->db->where(self::$PK, (int)$archivoId);
		$this->db->where('SAR_BL_DELETE', 0);
		$this->db->update(self::$TABLE, $update);

		return $this->db->affected_rows() >= 0;
	}

	public function listPacsResultFilesBySolicitud($solicitudId, $limit = 20){
		if(!$this->canUse()){
			return array();
		}

		$this->ensurePacsColumns();

		$this->db->from(self::$TABLE);
		$this->db->where('SOL_CO_ID', (int)$solicitudId);
		$this->db->where('SAR_BL_DELETE', 0);
		$this->db->where_in('SAR_NM_PACS_STATUS', array(self::PACS_STATUS_OK, self::PACS_STATUS_ERROR));
		$this->db->order_by('SAR_DT_PACS_PROCESADO', 'DESC');
		$this->db->order_by(self::$PK, 'DESC');
		$this->db->limit((int)$limit);

		$query = $this->db->get();
		if($query->num_rows() <= 0){
			return array();
		}

		return $query->result();
	}
}

/* End of file Solicitudarchivosmodel.php */
/* Location: ./application/models/Solicitudarchivosmodel.php */
