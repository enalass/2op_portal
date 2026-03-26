<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Solicitudmodel extends CI_Model {

	private static $TABLE = "t_sol_solicitudes";
	private static $PREFIX = "SOL";

	function getElementsList(){
		$this->db->where( self::$PREFIX . "_BL_DELETE",0);
		$this->db->from(self::$TABLE);
		$this->db->join("m_fso_fuentesolicitud mfs","mfs.FSO_CO_ID = " . self::$TABLE . ".FSO_CO_ID","left");
		$this->db->join("m_eso_estadosolicitud eso","eso.ESO_CO_ID = " . self::$TABLE . ".ESO_CO_ID","left");

		$data = $this->db->get();
		
		if ($data->num_rows() > 0){
			return $data;
		} else {
			$data = "No data";
			return false;
		}
	}

	function getElementById($id){
		$this->db->where( self::$PREFIX . "_CO_ID",$id );
		$this->db->from( self::$TABLE );
		$this->db->join("m_fso_fuentesolicitud mfs","mfs.FSO_CO_ID = " . self::$TABLE . ".FSO_CO_ID","left");
		$this->db->join("m_eso_estadosolicitud eso","eso.ESO_CO_ID = " . self::$TABLE . ".ESO_CO_ID","left");

		$data = $this->db->get();
		
		if ($data->num_rows() > 0){
			foreach ($data->result() as $row) {
				return $row;
			}
		} else {
			$data = "No data";
			return false;
		}
	}

	function getEstadosSolicitudActivos(){
		$this->db->where('ESO_BL_ENABLE', 1);
		$this->db->where('ESO_BL_DELETE', 0);
		$this->db->from('m_eso_estadosolicitud');
		$this->db->order_by('ESO_DS_NAME', 'ASC');

		$data = $this->db->get();

		if ($data->num_rows() > 0){
			return $data;
		}

		return false;
	}

	function getFuentesSolicitudActivas(){
		$this->db->where('FSO_BL_ENABLE', 1);
		$this->db->where('FSO_BL_DELETE', 0);
		$this->db->from('m_fso_fuentesolicitud');
		$this->db->order_by('FSO_DS_NAME', 'ASC');

		$data = $this->db->get();

		if ($data->num_rows() > 0){
			return $data;
		}

		return false;
	}

	function getIdiomasActivos(){
		$this->db->where('IDI_BL_ENABLE', 1);
		$this->db->where('IDI_BL_DELETE', 0);
		$this->db->from('m_idi_idiomas');
		$this->db->order_by('IDI_DS_NOMBRE', 'ASC');

		$data = $this->db->get();

		if ($data->num_rows() > 0){
			return $data;
		}

		return false;
	}

	function hasSolicitudUserColumn(){
		return $this->db->field_exists('USR_CO_ID', self::$TABLE);
	}

	function getClientVisibleSolicitudes($userId, $minEstado = 2){
		if(!$this->hasSolicitudUserColumn()){
			return false;
		}

		$this->db->where(self::$TABLE . '.USR_CO_ID', (int)$userId);
		$this->db->where(self::$TABLE . '.' . self::$PREFIX . '_BL_DELETE', 0);
		$this->db->where(self::$TABLE . '.ESO_CO_ID >=', (int)$minEstado);
		$this->db->from(self::$TABLE);
		$this->db->join('m_eso_estadosolicitud eso', 'eso.ESO_CO_ID = ' . self::$TABLE . '.ESO_CO_ID', 'left');
		$this->db->join('m_fso_fuentesolicitud mfs', 'mfs.FSO_CO_ID = ' . self::$TABLE . '.FSO_CO_ID', 'left');
		$this->db->order_by(self::$TABLE . '.SOL_DT_CREATE', 'DESC');

		$data = $this->db->get();

		if ($data->num_rows() > 0){
			return $data;
		}

		return false;
	}

	function getClientSolicitudById($userId, $solicitudId){
		if(!$this->hasSolicitudUserColumn()){
			return false;
		}

		$this->db->where(self::$TABLE . '.USR_CO_ID', (int)$userId);
		$this->db->where(self::$TABLE . '.SOL_CO_ID', (int)$solicitudId);
		$this->db->where(self::$TABLE . '.' . self::$PREFIX . '_BL_DELETE', 0);
		$this->db->from(self::$TABLE);

		$data = $this->db->get();

		if ($data->num_rows() > 0){
			foreach($data->result() as $row){
				return $row;
			}
		}

		return false;
	}

	function insertElement($data){
		$this->db->insert( self::$TABLE ,$data);
		return $this->db->insert_id();
	}

	function updateElement($data,$id){
		$this->db->where( self::$PREFIX . '_CO_ID',$id);
		$this->db->update( self::$TABLE ,$data);
		
	}

	function deleteElement($id){
		$this->db->where( self::$PREFIX . '_CO_ID',$id);
		$this->db->update( self::$TABLE ,array( self::$PREFIX . '_BL_DELETE' => 1));
		
	}

}

/* End of file Solicitudmodel.php */
/* Location: ./application/models/Solicitudmodel.php */