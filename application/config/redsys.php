<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['redsys'] = array(
    // Activado para pruebas con claves de desarrollo.
    'enabled' => true,

    // Valores permitidos: 'test' o 'production'.
    'mode' => 'test',

    // FUC de desarrollo Redsys.
    'merchant_code' => '999008881',

    // Terminal habitual: 1.
    'terminal' => '1',

    // EUR = 978.
    'currency' => '978',

    // Compra/autorizacion = 0.
    'transaction_type' => '0',

    // Clave secreta de desarrollo Redsys (entorno test).
    'secret_key' => 'sq7HjrUOBfKmC576ILgskD5srU870gJ7',
);

//Tarjeta de prueba Redsys: 4548812049400004
//Fecha de caducidad: 12/2030
//CVV: 123 
//Para pruebas de autorizacion sin fondos, usar la tarjeta 1111111111111111