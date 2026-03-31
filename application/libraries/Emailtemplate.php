<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Emailtemplate
{
    private $CI;
    private $templateRegistry = array();
    private $lastError = '';

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->config('email_templates', TRUE);

        $registry = $this->CI->config->item('email_templates', 'email_templates');
        $this->templateRegistry = is_array($registry) ? $registry : array();

        $this->CI->load->library('email', $this->emailConfigArray());
        date_default_timezone_set('Europe/Madrid');
    }

    public function sendByType($type, $to, $data = array(), $options = array())
    {
        $this->lastError = '';

        if (empty($to)) {
            $this->lastError = 'Destinatario vacio.';
            return FALSE;
        }

        $rendered = $this->renderByType($type, $data);
        if ($rendered === FALSE) {
            return FALSE;
        }

        $fromEmail = isset($options['from_email']) ? $options['from_email'] : EMAIL_CONTACT;
        $fromName = isset($options['from_name']) ? $options['from_name'] : 'No Reply';
        $replyTo = isset($options['reply_to']) ? $options['reply_to'] : EMAIL_REPLY;

        $this->CI->email->clear(TRUE);
        $this->CI->email->from($fromEmail, $fromName);
        $this->CI->email->reply_to($replyTo);
        $this->CI->email->to($to);

        if (!empty($options['cc'])) {
            $this->CI->email->cc($options['cc']);
        }

        if (!empty($options['bcc'])) {
            $this->CI->email->bcc($options['bcc']);
        }

        $this->CI->email->subject($rendered['subject']);
        $this->CI->email->message($rendered['html']);

        if (!$this->CI->email->send()) {
            $this->lastError = $this->CI->email->print_debugger();
            return FALSE;
        }

        return TRUE;
    }

    public function renderByType($type, $data = array())
    {
        $this->lastError = '';

        if (empty($type)) {
            $this->lastError = 'Tipo de email vacio.';
            return FALSE;
        }

        if (!isset($this->templateRegistry[$type])) {
            $this->lastError = 'Tipo de email no registrado: ' . $type;
            return FALSE;
        }

        $template = $this->templateRegistry[$type];
        if (empty($template['view']) || empty($template['subject'])) {
            $this->lastError = 'Configuracion incompleta para el tipo: ' . $type;
            return FALSE;
        }

        $payload = is_array($data) ? $data : array();
        $payload['app_name'] = isset($payload['app_name']) ? $payload['app_name'] : 'Segunda opinión radiológica';
        $payload['current_year'] = date('Y');

        $subject = $this->replacePlaceholders($template['subject'], $payload);
        $bodyContent = $this->CI->load->view('email_templates/' . $template['view'], $payload, TRUE);

        $layoutData = $payload;
        $layoutData['subject'] = $subject;
        $layoutData['body_content'] = $bodyContent;
        $html = $this->CI->load->view('email_templates/layout', $layoutData, TRUE);

        return array(
            'subject' => $subject,
            'html' => $html,
            'payload' => $payload,
        );
    }

    public function getTemplateTypes()
    {
        return array_keys($this->templateRegistry);
    }

    public function sendAltaUsuario($to, $data = array(), $options = array())
    {
        return $this->sendByType('alta_usuario', $to, $data, $options);
    }

    public function sendSolicitudPago($to, $data = array(), $options = array())
    {
        return $this->sendByType('solicitud_pago', $to, $data, $options);
    }

    public function sendSolicitudInformacion($to, $data = array(), $options = array())
    {
        return $this->sendByType('solicitud_informacion', $to, $data, $options);
    }

    public function sendEstudioSubidoAdmin($to, $data = array(), $options = array())
    {
        return $this->sendByType('estudio_subido_admin', $to, $data, $options);
    }

    public function sendRecuperacionPassword($to, $data = array(), $options = array())
    {
        return $this->sendByType('recuperacion_password', $to, $data, $options);
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    private function replacePlaceholders($text, $data)
    {
        $replacements = array();

        foreach ($data as $key => $value) {
            if (!is_scalar($value)) {
                continue;
            }
            $replacements['{' . $key . '}'] = (string) $value;
        }

        return strtr($text, $replacements);
    }

    private function emailConfigArray()
    {
        $config = array();
        $config['protocol'] = 'smtp';
        $config['smtp_host'] = SERVER_CONTACT;
        $config['smtp_user'] = USER_CONTACT;
        $config['smtp_pass'] = PASS_CONTACT;
        $config['smtp_crypto'] = 'ssl';
        $config['smtp_port'] = '465';
        $config['smtp_timeout'] = '30';
        $config['wordwrap'] = TRUE;
        $config['wrapchars'] = 76;
        $config['mailtype'] = 'html';
        $config['charset'] = 'utf-8';
        $config['validate'] = TRUE;
        $config['priority'] = 3;
        $config['crlf'] = "\r\n";
        $config['newline'] = "\r\n";
        $config['bcc_batch_mode'] = FALSE;
        $config['bcc_batch_size'] = 200;

        return $config;
    }
}
