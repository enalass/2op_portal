<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Email Templates Registry
|--------------------------------------------------------------------------
| Define here all email types used by the application.
| - key: internal type identifier
| - subject: subject text (dynamic placeholders like {name} are supported)
| - view: view file inside application/views/email_templates/
*/
$config['email_templates'] = array(
    'alta_usuario' => array(
        'subject' => 'Alta de usuario completada - {app_name}',
        'view' => 'alta_usuario'
    ),
    'solicitud_pago' => array(
        'subject' => 'Solicitud de pago recibida - {request_code}',
        'view' => 'solicitud_pago'
    ),
    'solicitud_informacion' => array(
        'subject' => 'Hemos recibido tu solicitud de informacion',
        'view' => 'solicitud_informacion'
    ),
    'nuevo_leed_admin' => array(
        'subject' => 'Nuevo leed recibido #{lead_id} - {app_name}',
        'view' => 'nuevo_leed_admin'
    )
);
