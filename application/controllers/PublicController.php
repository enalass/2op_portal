<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PublicController extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Solicitudmodel');
        $this->load->library('form_validation');
    }

    /**
     * Página de inicio
     */
    public function index()
    {
        $this->load->view('public/home');
    }

    /**
     * Página de gracias
     */
    public function gracias()
    {
        $this->load->view('public/gracias');
    }

    public function enviarSolicitud()
    {
        if (strtoupper($this->input->method()) !== 'POST') {
            return $this->json_response(array(
                'status' => 'unsuccess',
                'msg'    => 'Metodo no permitido',
                'hash'   => $this->security->get_csrf_hash(),
                'token'  => $this->security->get_csrf_token_name(),
            ), 405);
        }

        $this->form_validation->set_rules('nombre', 'nombre', 'required|trim|min_length[3]|max_length[150]');
        $this->form_validation->set_rules('telefono', 'telefono', 'required|trim|min_length[6]|max_length[30]');
        $this->form_validation->set_rules('email', 'email', 'required|trim|valid_email|max_length[150]');
        $this->form_validation->set_rules('mensaje', 'mensaje', 'required|trim|min_length[10]|max_length[2000]');
        $this->form_validation->set_rules('acepto_privacidad', 'politica de privacidad', 'required|in_list[1]');

        if ($this->form_validation->run() === FALSE) {
            return $this->json_response(array(
                'status' => 'unsuccess',
                'msg'    => validation_errors(),
                'errors' => $this->form_validation->error_array(),
                'hash'   => $this->security->get_csrf_hash(),
                'token'  => $this->security->get_csrf_token_name(),
            ), 422);
        }

        $data = array(
            'SOL_DS_NOMBRE'       => $this->input->post('nombre', TRUE),
            'SOL_DS_ADQ_TELEFONO' => $this->input->post('telefono', TRUE),
            'SOL_DS_ADQ_MAIL'     => $this->input->post('email', TRUE),
            'SOL_DS_ADQ_MOTIVO'   => $this->input->post('mensaje', TRUE),
            'ESO_CO_ID'           => 1,
            'FSO_CO_ID'           => 1,
        );

        $id = $this->Solicitudmodel->insertElement($data);

        if (!$id) {
            return $this->json_response(array(
                'status' => 'unsuccess',
                'msg'    => 'No se pudo registrar la solicitud',
                'hash'   => $this->security->get_csrf_hash(),
                'token'  => $this->security->get_csrf_token_name(),
            ), 500);
        }

        $this->notifyAdminNewLead($id, $data);

        return $this->json_response(array(
            'status'   => 'success',
            'msg'      => 'Solicitud enviada correctamente',
            'id'       => $id,
            'redirect' => '/gracias',
            'hash'     => $this->security->get_csrf_hash(),
            'token'    => $this->security->get_csrf_token_name(),
        ));
    }

    protected function notifyAdminNewLead($leadId, $data)
    {
        $adminRecipients = $this->getAdminRecipients();

        if (empty($adminRecipients)) {
            log_message('error', 'No hay destinatarios configurados en EMAIL_ADMIN para notificar nuevo leed.');
            return FALSE;
        }

        $this->load->library('Emailtemplate');

        $payload = array(
            'lead_id'  => $leadId,
            'nombre'   => isset($data['SOL_DS_NOMBRE']) ? $data['SOL_DS_NOMBRE'] : '',
            'telefono' => isset($data['SOL_DS_ADQ_TELEFONO']) ? $data['SOL_DS_ADQ_TELEFONO'] : '',
            'email'    => isset($data['SOL_DS_ADQ_MAIL']) ? $data['SOL_DS_ADQ_MAIL'] : '',
            'mensaje'  => isset($data['SOL_DS_ADQ_MOTIVO']) ? $data['SOL_DS_ADQ_MOTIVO'] : '',
        );

        $sent = $this->emailtemplate->sendByType('nuevo_leed_admin', $adminRecipients, $payload, array(
            'from_email' => EMAIL_CONTACT,
            'from_name'  => 'Portal 2OP',
            'reply_to'   => EMAIL_REPLY,
        ));

        if (!$sent) {
            log_message('error', 'Error enviando email de nuevo leed al administrador: ' . $this->emailtemplate->getLastError());
            return FALSE;
        }

        return TRUE;
    }

    protected function getAdminRecipients()
    {
        $raw = defined('EMAIL_ADMIN') ? EMAIL_ADMIN : '';

        if (!is_string($raw) || $raw === '') {
            return array();
        }

        $parts = preg_split('/[;,]+/', $raw);
        $parts = array_map('trim', $parts);
        $parts = array_filter($parts, function($email){
            return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL);
        });

        return array_values(array_unique($parts));
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