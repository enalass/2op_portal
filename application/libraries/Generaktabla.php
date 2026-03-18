<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Generaktabla
{
	protected $ci;

	public function __construct()
	{
        $this->ci =& get_instance();
	}

	public function generaTablaLocalEstandar(
						$cabecera, 			//Las columnas de la tabla
						$datos, 			//Los datos que se mostrarán
						$pie=FALSE,			//Si se muestra el pie de la tabla
						$acciones=FALSE, 	//Array de acciones
						$filtros = FALSE 	//Array con los filtros
					){

		


	}

	

}

/* End of file Generaktabla.php */
/* Location: ./application/libraries/Generaktabla.php */
