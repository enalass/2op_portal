<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class TestEmail extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('emailtemplate');
    }

    public function index()
    {
        $tipos = $this->emailtemplate->getTemplateTypes();

        header('Content-Type: text/html; charset=utf-8');
        echo '<h2>Test de emails</h2>';
        echo '<p>Acciones disponibles:</p>';
        echo '<ul>';
        foreach ($tipos as $tipo) {
            echo '<li>';
            echo '<strong>' . html_escape($tipo) . '</strong> | ';
            echo '<a href="' . base_url('index.php/util/TestEmail/preview/' . $tipo) . '">Preview</a> | ';
            echo '<a href="' . base_url('index.php/util/TestEmail/send/' . $tipo . '?to=') . urlencode(EMAIL_REPLY) . '">Enviar a ' . html_escape(EMAIL_REPLY) . '</a>';
            echo '</li>';
        }
        echo '</ul>';

        echo '<p>Tambien puedes enviar todos con: <a href="' . base_url('index.php/util/TestEmail/send_all?to=') . urlencode(EMAIL_REPLY) . '">send_all</a></p>';
    }

    public function preview($type = '')
    {
        $data = $this->sampleData($type);
        $rendered = $this->emailtemplate->renderByType($type, $data);

        if ($rendered === FALSE) {
            show_error($this->emailtemplate->getLastError(), 400);
            return;
        }

        header('Content-Type: text/html; charset=utf-8');
        echo '<h3>Preview: ' . html_escape($type) . '</h3>';
        echo '<p><strong>Asunto:</strong> ' . html_escape($rendered['subject']) . '</p>';
        echo '<hr>';
        echo $rendered['html'];
    }

    public function send($type = '')
    {
        $to = trim((string) $this->input->get('to', TRUE));
        if ($to === '') {
            show_error('Debes indicar destinatario con ?to=correo@dominio.com', 400);
            return;
        }

        $ok = $this->emailtemplate->sendByType($type, $to, $this->sampleData($type));

        $response = array(
            'type' => $type,
            'to' => $to,
            'success' => $ok,
            'error' => $ok ? '' : $this->emailtemplate->getLastError(),
        );

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    public function send_all()
    {
        $to = trim((string) $this->input->get('to', TRUE));
        if ($to === '') {
            show_error('Debes indicar destinatario con ?to=correo@dominio.com', 400);
            return;
        }

        $results = array();
        foreach ($this->emailtemplate->getTemplateTypes() as $type) {
            $ok = $this->emailtemplate->sendByType($type, $to, $this->sampleData($type));
            $results[] = array(
                'type' => $type,
                'success' => $ok,
                'error' => $ok ? '' : $this->emailtemplate->getLastError(),
            );
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array('to' => $to, 'results' => $results)));
    }

    private function sampleData($type)
    {
        $common = array(
            'app_name' => 'Segunda opinión radiológica - Portal de pruebas',
            'nombre' => 'Usuario de prueba',
        );

        if ($type === 'alta_usuario') {
            return array_merge($common, array(
                'usuario' => 'usuario.test',
                'email' => EMAIL_REPLY,
                'url_acceso' => base_url('index.php/cerbero'),
            ));
        }

        if ($type === 'solicitud_pago') {
            return array_merge($common, array(
                'request_code' => 'SOL-TEST-001',
                'importe' => '120,00 EUR',
                'observaciones' => 'Pago de prueba generado desde util/TestEmail',
            ));
        }

        if ($type === 'solicitud_informacion') {
            return array_merge($common, array(
                'asunto' => 'Informacion de servicios',
                'canal' => 'Web',
                'mensaje' => 'Mensaje de prueba para validar plantilla y entrega SMTP.',
            ));
        }

        return $common;
    }
}

/* End of file TestEmail.php */
/* Location: ./application/controllers/util/TestEmail.php */
