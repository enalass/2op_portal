<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PublicController extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Página de inicio
     */
    public function index()
    {
        $this->load->view('public/home');
    }

    /**
     * Respuesta JSON genérica
     */
    protected function json_response($data, $status = 200)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_status_header($status)
            ->set_output(json_encode($data));
    }

    /**
     * Validar entrada POST
     */
    protected function validate_input($rules)
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules($rules);
        return $this->form_validation->run();
    }

    /**
     * Obtener mensaje de error de validación
     */
    protected function get_validation_errors()
    {
        return $this->form_validation->error_array();
    }

    /**
     * Redirección segura
     */
    protected function safe_redirect($url = '', $method = 'location', $http_response_code = 302)
    {
        redirect($url, $method, $http_response_code);
    }
}