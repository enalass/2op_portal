<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CC002_perfil extends CI_Controller {

	function __construct(){
		parent::__construct();
		$this->load->model('Clienteperfilmodel');
	}

	public function index(){
		if($this->session->userdata('logged')!=TRUE || (int)$this->session->userdata('perfil')!==4){
			redirect('index.php/cerbero','refresh');
			return;
		}

		$userId = (int)$this->session->userdata('id');
		$cliente = $this->Clienteperfilmodel->getClienteById($userId);
		if($cliente === false){
			redirect(site_url('panel'));
			return;
		}

		$data = array(
			'content' => 'cliente/vC002_perfil.php',
			'titulo' => 'Perfil cliente',
			'javascriptMenu' => "$('#menuPerfil').addClass('menu-item-active');",
			'perfilNombre' => isset($cliente->USR_DS_NOMBRE) ? (string)$cliente->USR_DS_NOMBRE : '',
			'perfilEmail' => isset($cliente->USR_DS_MAIL) ? (string)$cliente->USR_DS_MAIL : '',
			'perfilError' => $this->session->flashdata('perfil_error') ? (string)$this->session->flashdata('perfil_error') : '',
			'perfilSuccess' => $this->session->flashdata('perfil_success') ? (string)$this->session->flashdata('perfil_success') : '',
			'csrfTokenName' => $this->security->get_csrf_token_name(),
			'csrfTokenHash' => $this->security->get_csrf_hash(),
		);

		$this->load->view('layout_cliente', $data);
	}

	public function guardar(){
		if($this->session->userdata('logged')!=TRUE || (int)$this->session->userdata('perfil')!==4){
			redirect('index.php/cerbero','refresh');
			return;
		}

		$userId = (int)$this->session->userdata('id');
		$cliente = $this->Clienteperfilmodel->getClienteById($userId);
		if($cliente === false){
			redirect(site_url('panel'));
			return;
		}

		$nombre = trim((string)$this->input->post('perfil_nombre', TRUE));
		$password = (string)$this->input->post('perfil_password', TRUE);
		$repassword = (string)$this->input->post('perfil_repassword', TRUE);

		if($nombre === ''){
			$this->session->set_flashdata('perfil_error', 'Debes indicar el nombre.');
			redirect(site_url('panel/perfil'));
			return;
		}

		$updateData = array(
			'USR_DS_NOMBRE' => $nombre,
		);

		if($password !== '' || $repassword !== ''){
			if($password === '' || $repassword === ''){
				$this->session->set_flashdata('perfil_error', 'Debes completar ambas casillas de contraseña.');
				redirect(site_url('panel/perfil'));
				return;
			}

			if($password !== $repassword){
				$this->session->set_flashdata('perfil_error', 'Las contraseñas no coinciden.');
				redirect(site_url('panel/perfil'));
				return;
			}

			if(strlen($password) < 6){
				$this->session->set_flashdata('perfil_error', 'La contraseña debe tener al menos 6 caracteres.');
				redirect(site_url('panel/perfil'));
				return;
			}

			$updateData['USR_DS_PASSWORD'] = password_hash($password, PASSWORD_DEFAULT, array('cost' => 10));
		}

		if($this->Clienteperfilmodel->updateClientePerfil($userId, $updateData)){
			$this->session->set_flashdata('perfil_success', 'Perfil actualizado correctamente.');
		}else{
			$this->session->set_flashdata('perfil_error', 'No se pudo actualizar el perfil.');
		}

		redirect(site_url('panel/perfil'));
	}
}
