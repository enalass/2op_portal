<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CA001_panelControl extends CI_Controller {

	function __construct(){
		parent::__construct();
	}

	public function index()
	{
		if($this->session->userdata('logged')==TRUE){
			$data = array(
				"content"	=> "admin/vA001_panelControl.php"
				,"titulo" 	=> "Dashboard"
				,"javascriptMenu"=>"$('#menuDashBoard').addClass('menu-item-active');"
				
			);
			$this->load->view('layout_admin',$data);
		}else{
			redirect('index.php/cerbero','refresh');
		}
	}

	

}

/* End of file cA01_panelControl.php */
/* Location: ./application/controllers/admin/cA01_panelControl.php */ 
?>