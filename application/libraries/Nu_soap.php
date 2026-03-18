<?php
if ( ! defined('BASEPATH')) exit('No se permite el acceso directo a las p&aacute;ginas de este sitio.');

class Nu_soap {

   function  __construct() {
      // Por si se ejecuta en un servidor Windows
      // require_once(str_replace("\\", "/", APPPATH).'libraries/NuSOAP/lib/nusoap'.EXT);
      require_once('NuSOAP/lib/nusoap.php');
   } // end Constructor

   function index($no_cache) {
   }  // end function

} // end Class

/* End of file Nu_soap.php */
   