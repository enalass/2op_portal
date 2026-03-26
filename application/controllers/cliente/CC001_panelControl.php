<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CC001_panelControl extends CI_Controller {

	function __construct(){
		parent::__construct();
	}

	public function index()
	{
		if($this->session->userdata('logged')==TRUE && (int)$this->session->userdata('perfil')===4){
			$clienteView = FCPATH . 'application/views/cliente/vC001_panelControl.php';
			if (file_exists($clienteView)) {
				$data = array(
					"content" => "cliente/vC001_panelControl.php",
					"titulo" => "Panel cliente",
					"javascriptMenu" => "$('#menuDashBoard').addClass('menu-item-active');"
				);
				$this->load->view('layout_cliente',$data);
				return;
			}

			echo '<section><h2>Panel de cliente en construccion</h2></section>';
		}else{
			redirect('/cerbero','refresh');
		}
	}

}

/* End of file cC001_panelControl.php */
/* Location: ./application/controllers/cliente/cC001_panelControl.php */
