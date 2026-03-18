<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Email_Create class
 *
 * Metodos y funciones para crear emails
 *
 */
class Emailcreate
{

    private static $ERROR_EMPTY_VALUES = 4;
    private static $ERROR_USER_NOT_FOUND = 2;
    private static $ERROR_SENDING_EMAIL = 1;
    private static $SUCCESS = 0;
    private $_CI;

    public function __construct()
    {
        $this->_CI =& get_instance();
        // $this->_CI->load->model('admins_model', 'admin');
        $this->_CI->load->model('recoverpass');
        $this->_CI->load->model('usersmodel');
        $this->_CI->load->library('encrypt');

        $config = $this->_emailConfigArray();

        $this->_CI->load->library('email', $config);
        date_default_timezone_set('Europe/Madrid');
    }

    /**
     * Envia el mensaje por email al usuario para recuperar su contraseña
     *
     * @access    public
     */
    public function add_replyToPassRecover($email)
    {

        // $email = $this->_CI->input->post('recover_email');
        if (empty($email)) {
            return self::$ERROR_EMPTY_VALUES;
        }

        $user = $this->_CI->recoverpass->getUserByMail($email);
        if ($user == false) {
            return self::$ERROR_USER_NOT_FOUND;
        }

        $url = $email . '|' . $user->USR_CO_ID . '|' . time();
        $url = $this->_CI->encrypt->encode($url, CIPHERPASS);
        $register = new stdClass();
        $register->USR_CO_ID = $user->USR_CO_ID;
        $register->url = base64_encode($url); //rtrim(base64_encode($url), '=');
        // guardamos el registroe n BBDD

        $this->_CI->recoverpass->createRegisterToken($register);

        $subject = 'Recuperación de contraseña PIOB';


        $config = $this->_emailConfigArray();
        $this->_CI->load->library('email', $config);
        // $this->email->initialize($config);


        $this->_CI->email->from(EMAIL_CONTACT, 'PIOB');
        $this->_CI->email->reply_to(EMAIL_CONTACT); // responder A
        $this->_CI->email->to($email); // correo destinov
        $this->_CI->email->subject($subject);

        $data['url']            = urlencode($url);
        $data['response_mail']  = EMAIL_CONTACT;
        $data['user_login']     = $user->USR_DS_LOGIN;

        $html = $this->_CI->load->view('emails/recover_pass', $data, TRUE);
        $this->_CI->email->message($html);

        if (!$this->_CI->email->send()) {
            // Generate error
            $this->_CI->recoverpass->removeRegister($register);
            return self::$ERROR_SENDING_EMAIL;
        } else {
            return self::$SUCCESS;
        }
    }

    public function add_welcomeEmail($idUser = 0)
    {

        // $email = $this->_CI->input->post('recover_email');
        if ($idUser == 0) {
            return self::$ERROR_EMPTY_VALUES;
        }

        $user = $this->_CI->usersmodel->getUserByIdUser($idUser);
        if ($user == false) {
            return self::$ERROR_USER_NOT_FOUND;
        }

        $subject = 'Bienvenido a la plataforma de PIOB';


        $config = $this->_emailConfigArray();
        $this->_CI->load->library('email', $config);
        // $this->email->initialize($config);


        $this->_CI->email->from(EMAIL_CONTACT, 'No reply PIOB');
        $this->_CI->email->reply_to(EMAIL_CONTACT); // responder A
        $this->_CI->email->to($user->USR_DS_MAIL); // correo destinov
        $this->_CI->email->subject($subject);

        $data['response_mail']  = EMAIL_REPLY;
        $data['user_login']     = $user->USR_DS_LOGIN;
        $data['user_password']  = $user->USR_DS_PASSWORD;

        $html = $this->_CI->load->view('emails/welcome', $data, TRUE);
        $this->_CI->email->message($html);

        if (!$this->_CI->email->send()) {
            // Generate error
            $data["error"] = $this->_CI->email->print_debugger();
            return self::$ERROR_SENDING_EMAIL;
        } else {
            return self::$SUCCESS;
        }
    }

    

    /**
     * Genera un array de configuracion para enviar el email
     *
     * @access    public
     */
    private function _emailConfigArray()
    {
        $config['protocol'] = 'smtp'; // mail, sendmail, or smtp    The mail sending protocol.
        $config['smtp_host'] = SERVER_CONTACT; // SMTP Server Address.
        $config['smtp_user'] = USER_CONTACT; // SMTP Username.
        $config['smtp_pass'] = PASS_CONTACT; // SMTP Password.
        $config['smtp_crypto'] = 'ssl';
        $config['smtp_port'] = '465'; // SMTP Port.
        $config['smtp_timeout'] = '30'; // SMTP Timeout (in seconds).
        $config['wordwrap'] = TRUE; // TRUE or FALSE (boolean)    Enable word-wrap.
        $config['wrapchars'] = 76; // Character count to wrap at.
        $config['mailtype'] = 'html';
        $config['charset'] = 'utf-8'; // Character set (utf-8, iso-8859-1, etc.).
        $config['validate'] = TRUE; // TRUE or FALSE (boolean)    Whether to validate the email address.
        $config['priority'] = 3; // 1, 2, 3, 4, 5    Email Priority. 1 = highest. 5 = lowest. 3 = normal.
        $config['crlf'] = "\r\n"; // "\r\n" or "\n" or "\r" Newline character. (Use "\r\n" to comply with RFC 822).
        $config['newline'] = "\r\n"; // "\r\n" or "\n" or "\r"    Newline character. (Use "\r\n" to comply with RFC 822).
        $config['bcc_batch_mode'] = FALSE; // TRUE or FALSE (boolean)    Enable BCC Batch Mode.
        $config['bcc_batch_size'] = 200; // Number of emails in each BCC batch.

        return $config;
    }
}

/* End of file Email_Create.php */
