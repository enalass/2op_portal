<?php if ( ! defined('BASEPATH')) exit('No se permite el acceso directo al script');
class Generatablaajaxsource{

	public function generaTablaTipo1($cabecera, $datos, $pie=FALSE,$acciones=FALSE, $filtros = FALSE, $id=1, $php){


		$tabla = "<table class='table table-bordered datatable' id='table-".$id."'>\n";
		$tabla .= "<thead>\n";
		if ($filtros==TRUE){
			$tabla .= "<tr class='replace-inputs'>\n";
			foreach ($cabecera as $key => $value) {
				$tabla .= "<th>". $value["titulo"]. "</th>\n";
			}
			if($acciones!=FALSE){
				$tabla .= "<th>Acciones</th>\n";
			}
			$tabla .= "</tr>\n";
		}
		$tabla .= "<tr>\n";
		foreach ($cabecera as $key => $value) {
			$tabla .= "<th>". $value["titulo"]. "</th>\n";
		}
		if($acciones!=FALSE){
			$tabla .= "<th>Acciones</th>\n";
		}
		$tabla .= "</tr>\n";
		$tabla .= "</thead>\n";
		$tabla .= "<tbody>\n";
		if($datos!=FALSE){
			foreach ($datos->result() as $row) {
				$tabla .= "<tr>\n";
				foreach ($cabecera as $key => $value) {
					
					$tabla .= "<td>" . $row->$value["campo"] . "</td>\n";
					
				}
				// if($acciones!=FALSE){
				// 	$tabla .= $this->generaBotonesAccion($acciones, $row);
				// }
				$tabla .= "</tr>\n";
			}
		}
		$tabla .= "</tbody>\n";
		if($pie==TRUE){
			$tabla .= "<tfoot>\n";
			$tabla .= "</tr>\n";
			foreach ($cabecera as $key => $value) {
				$tabla .= "<td>". $value["titulo"]. "</td>\n";
			}
			if($acciones!=FALSE){
				$tabla .= "<td>Acciones</td>\n";
			}
			$tabla .= "</tr>\n";
			$tabla .= "</tfoot>\n";
		}
		$tabla .= "</table>\n";
		$tabla .= $this->scriptTablaT1($filtros, $id, $php);

		return $tabla;
	}
	
	public function generaBotonesAccion($acciones,$row){
		$tabla = "<td>";
		// if(isset($acciones["EDIT"])){
		// 	$opcion_modal = ($acciones["EDIT"]["MODAL"]==TRUE)?"is-modal-button" : "";
		// 	$tabla .= 	"<a href='". $acciones["EDIT"]["URL"] . $row->$acciones["EDIT"]["COD"] ."' class='btn btn-default btn-sm btn-icon icon-left " . $opcion_modal ."' title='" . $acciones["EDIT"]["TITULO"] . "'>
		// 					<i class='entypo-pencil'></i>
		// 					Editar
		// 				</a> ";
		// }

		if(isset($acciones["EDIT"])){
			$opcion_modal = ($acciones["EDIT"]["MODAL"]==TRUE)?"is-modal-button" : "";
			if ($acciones["EDIT"]["MODAL"]==TRUE){
				$tabla .= 	"<a href='". $acciones["EDIT"]["URL"] . $row->$acciones["EDIT"]["COD"] ."' onclick='jQuery(\"#modal-7\").modal(\"show\", {backdrop: \"static\"});jQuery(\".modal-title\").html(jQuery(this).attr(\"title\"));jQuery.ajax({url: jQuery(this).attr(\"href\"),success: function(response){jQuery(\"#modal-7 .modal-body\").html(response);}});return false;' class='btn btn-default btn-sm btn-icon icon-left " . $opcion_modal ."' title='" . $acciones["EDIT"]["TITULO"] . "'>
							<i class='entypo-pencil'></i>
							Editar
						</a> ";
			}else{
				$tabla .= 	"<a href='". $acciones["EDIT"]["URL"] . $row->$acciones["EDIT"]["COD"] ."' class='btn btn-default btn-sm btn-icon icon-left " . $opcion_modal ."' title='" . $acciones["EDIT"]["TITULO"] . "'>
							<i class='entypo-pencil'></i>
							Editar
						</a> ";
			}
			
		}

		if(isset($acciones["DELETE"])){
			$opcion_modal = ($acciones["DELETE"]["MODAL"]==TRUE)?"is-modal-button" : "";
			if ($acciones["DELETE"]["MODAL"]==TRUE){
				$tabla .= 	"<a href='". $acciones["DELETE"]["URL"] . $row->$acciones["DELETE"]["COD"] ."' onclick='jQuery(\"#modal-7\").modal(\"show\", {backdrop: \"static\"});jQuery(\".modal-title\").html(jQuery(this).attr(\"title\"));jQuery.ajax({url: jQuery(this).attr(\"href\"),success: function(response){jQuery(\"#modal-7 .modal-body\").html(response);}});return false;' class='btn btn-danger btn-sm btn-icon icon-left " . $opcion_modal ."' title='" . $acciones["DELETE"]["TITULO"] . "'>
							<i class='entypo-cancel'></i>
							Borrar
						</a> ";
			}else{
				$tabla .= 	"<a href='". $acciones["DELETE"]["URL"] . $row->$acciones["DELETE"]["COD"] ."' class='btn btn-danger btn-sm btn-icon icon-left " . $opcion_modal ."' title='" . $acciones["DELETE"]["TITULO"] . "'>
							<i class='entypo-cancel'></i>
							Borrar
						</a> ";
			}
			
		}
		if(isset($acciones["INFO"])){
			$opcion_modal = ($acciones["INFO"]["MODAL"]==TRUE)?"is-modal-button" : "";
			if ($acciones["INFO"]["MODAL"]==TRUE){
				$tabla .= 	"<a href='". $acciones["INFO"]["URL"] . $row->$acciones["INFO"]["COD"] ."' onclick='jQuery(\"#modal-7\").modal(\"show\", {backdrop: \"static\"});jQuery(\".modal-title\").html(jQuery(this).attr(\"title\"));jQuery.ajax({url: jQuery(this).attr(\"href\"),success: function(response){jQuery(\"#modal-7 .modal-body\").html(response);}});return false;' class='btn btn-info btn-sm btn-icon icon-left " . $opcion_modal ."' title='" . $acciones["INFO"]["TITULO"] . "'>
							<i class='entypo-info'></i>
							Info
						</a> ";
			}else{
				$tabla .= 	"<a href='". $acciones["INFO"]["URL"] . $row->$acciones["INFO"]["COD"] ."' class='btn btn-info btn-sm btn-icon icon-left " . $opcion_modal ."' title='" . $acciones["INFO"]["TITULO"] . "'>
							<i class='entypo-info'></i>
							Info
						</a> ";
			}
			
		}
		if(isset($acciones["PDF"])){
			$opcion_modal = ($acciones["PDF"]["MODAL"]==TRUE)?"is-modal-button" : "";
			if ($acciones["PDF"]["MODAL"]==TRUE){
				$tabla .= 	"<a href='". $acciones["PDF"]["URL"] . $row->$acciones["PDF"]["COD"] ."' onclick='jQuery(\"#modal-7\").modal(\"show\", {backdrop: \"static\"});jQuery(\".modal-title\").html(jQuery(this).attr(\"title\"));jQuery.ajax({url: jQuery(this).attr(\"href\"),success: function(response){jQuery(\"#modal-7 .modal-body\").html(response);}});return false;' class='btn btn-default btn-sm btn-icon icon-left " . $opcion_modal ."' title='" . $acciones["PDF"]["TITULO"] . "'>
							<i class='entypo-doc-text'></i>
							PDF
						</a> ";
			}else{
				$tabla .= 	"<a href='". $acciones["PDF"]["URL"] . $row->$acciones["PDF"]["COD"] ."' target='_blank' class='btn btn-default btn-sm btn-icon icon-left " . $opcion_modal ."' title='" . $acciones["PDF"]["TITULO"] . "'>
							<i class='entypo-doc-text'></i>
							PDF
						</a> ";
			}
			
		}

		$tabla .= "</td>\n";
		// $tabla="hola";

		return $tabla;
	}

	private function scriptTablaT1($filtros=FALSE, $id, $php){
		$script=<<<EOD
<script type="text/javascript">
var responsiveHelper$id;
var breakpointDefinition = {
    tablet: 1024,
    phone : 480
};
var tableContainer$id;

	jQuery(document).ready(function($)
	{
		tableContainer$id = $("#table-$id");
		
		var table$id = tableContainer$id.dataTable({
			//---Añadimos el array oLanguage para poner los textos en español
			"oLanguage": {
				"sProcessing":     "Procesando...",
			    "sLengthMenu":     "Mostrar _MENU_ registros",
			    "sZeroRecords":    "No se encontraron resultados",
			    "sEmptyTable":     "Ningún dato disponible en esta tabla",
			    "sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
			    "sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0 registros",
			    "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
			    "sInfoPostFix":    "",
			    "sSearch":         "Buscar:",
			    "sUrl":            "",
			    "sInfoThousands":  ",",
			    // "sLoadingRecords": "Cargando...",
			    "oPaginate": {
			        "sFirst":    "Primero",
			        "sLast":     "Último",
			        "sNext":     "Siguiente",
			        "sPrevious": "Anterior"
			    },
			    "oAria": {
			        "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
			        "sSortDescending": ": Activar para ordenar la columna de manera descendente"
			    }
			},
			//
			"sAjaxSource": "$php",
			"bJQueryUI": true,
			"sPaginationType": "bootstrap",
			"aLengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
			"bStateSave": true,
			

		    // Responsive Settings
		    bAutoWidth     : false,
		    fnPreDrawCallback: function () {
		        // Initialize the responsive datatables helper once.
		        if (!responsiveHelper$id) {
		            responsiveHelper$id = new ResponsiveDatatablesHelper(tableContainer$id, breakpointDefinition);
		        }
		    },
		    fnRowCallback  : function (nRow, aData, iDisplayIndex, iDisplayIndexFull) {
		        responsiveHelper$id.createExpandIcon(nRow);
		    },
		    fnDrawCallback : function (oSettings) {
		        responsiveHelper$id.respond();
		    }
		});
	
		//Decimos si se filtra por columna
		@FILTROS_COLUMNA
		
		$(".dataTables_wrapper select").select2({
			minimumResultsForSearch: -1
		});
	});
</script>	
EOD;
		
		//Tipos de Filtro: text=>{type:'text'}, select>=>{type:'select',values:['valores','opcionales','si no se pasa este parámetro filtra con los valores de la tabla']}
		// number-range=>{type:'number-range'}, checkbox, number
		$cadena_filtros="";
		if($filtros!=FALSE){
			$cadena_filtros="$('#table-$id').dataTable().columnFilter({
				'sPlaceHolder' : 'head:after'
				,'aoColumns': " . $filtros ."
			});";
		}

		$script = str_replace("@FILTROS_COLUMNA", $cadena_filtros, $script);

		return $script;
	}

	public function generaTablaTipo2($cabecera, $datos, $pie=FALSE,$acciones=FALSE, $filtros = FALSE, $id=1, $php){


		$tabla = "<table class='table table-bordered datatable' id='table-".$id."'>\n";
		$tabla .= "<thead>\n";
		if ($filtros==TRUE){
			$tabla .= "<tr class='replace-inputs'>\n";
			foreach ($cabecera as $key => $value) {
				$tabla .= "<th>". $value["titulo"]. "</th>\n";
			}
			if($acciones!=FALSE){
				$tabla .= "<th>Acciones</th>\n";
			}
			$tabla .= "</tr>\n";
		}
		$tabla .= "<tr>\n";
		foreach ($cabecera as $key => $value) {
			$tabla .= "<th>". $value["titulo"]. "</th>\n";
		}
		if($acciones!=FALSE){
			$tabla .= "<th>Acciones</th>\n";
		}
		$tabla .= "</tr>\n";
		$tabla .= "</thead>\n";
		$tabla .= "<tbody>\n";
		if($datos!=FALSE){
			foreach ($datos->result() as $row) {
				$tabla .= "<tr>\n";
				foreach ($cabecera as $key => $value) {
					
					$tabla .= "<td>" . $row->$value["campo"] . "</td>\n";
					
				}
				// if($acciones!=FALSE){
				// 	$tabla .= $this->generaBotonesAccion($acciones, $row);
				// }
				$tabla .= "</tr>\n";
			}
		}
		$tabla .= "</tbody>\n";
		if($pie==TRUE){
			$tabla .= "<tfoot>\n";
			$tabla .= "</tr>\n";
			foreach ($cabecera as $key => $value) {
				$tabla .= "<td>". $value["titulo"]. "</td>\n";
			}
			if($acciones!=FALSE){
				$tabla .= "<td>Acciones</td>\n";
			}
			$tabla .= "</tr>\n";
			$tabla .= "</tfoot>\n";
		}
		$tabla .= "</table>\n";
		$tabla .= $this->scriptTablaT2($filtros, $id, $php);

		return $tabla;
	}

	private function scriptTablaT2($filtros=FALSE, $id, $php){
		$script=<<<EOD
<script type="text/javascript">
var responsiveHelper$id;
var breakpointDefinition = {
    tablet: 1024,
    phone : 480
};
var tableContainer$id;

	jQuery(document).ready(function($)
	{
		tableContainer$id = $("#table-$id");
		
		var table$id = tableContainer$id.dataTable({
			//---Añadimos el array oLanguage para poner los textos en español
			"oLanguage": {
				"sProcessing":     "Procesando...",
			    "sLengthMenu":     "Mostrar _MENU_ registros",
			    "sZeroRecords":    "No se encontraron resultados",
			    "sEmptyTable":     "Ningún dato disponible en esta tabla",
			    "sInfo":           "Registros del _START_ al _END_ de  _TOTAL_ registros",
			    "sInfoEmpty":      "Ningún registro",
			    "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
			    "sInfoPostFix":    "",
			    "sSearch":         "Buscar:",
			    "sUrl":            "",
			    "sInfoThousands":  ",",
			    // "sLoadingRecords": "Cargando...",
			    "oPaginate": {
			        "sFirst":    "Primero",
			        "sLast":     "Último",
			        "sNext":     "Siguiente",
			        "sPrevious": "Anterior"
			    },
			    "oAria": {
			        "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
			        "sSortDescending": ": Activar para ordenar la columna de manera descendente"
			    }
			},
			//
			"sAjaxSource": "$php",
			"bJQueryUI": true,
			"sPaginationType": "bootstrap",
			"aLengthMenu": [[5, 10, 25, -1], [5, 10, 25, "Todos"]],
			"bStateSave": true,
			"bPaginate": true,
			

		    // Responsive Settings
		    bAutoWidth     : false,
		    fnPreDrawCallback: function () {
		        // Initialize the responsive datatables helper once.
		        if (!responsiveHelper$id) {
		            responsiveHelper$id = new ResponsiveDatatablesHelper(tableContainer$id, breakpointDefinition);
		        }
		    },
		    fnRowCallback  : function (nRow, aData, iDisplayIndex, iDisplayIndexFull) {
		        responsiveHelper$id.createExpandIcon(nRow);
		    },
		    fnDrawCallback : function (oSettings) {
		        responsiveHelper$id.respond();
		    }
		});
	
		//Decimos si se filtra por columna
		@FILTROS_COLUMNA
		
		$(".dataTables_wrapper select").select2({
			minimumResultsForSearch: -1
		});
	});
</script>	
EOD;
		
		//Tipos de Filtro: text=>{type:'text'}, select>=>{type:'select',values:['valores','opcionales','si no se pasa este parámetro filtra con los valores de la tabla']}
		// number-range=>{type:'number-range'}, checkbox, number
		$cadena_filtros="";
		if($filtros!=FALSE){
			$cadena_filtros="$('#table-$id').dataTable().columnFilter({
				'sPlaceHolder' : 'head:after'
				,'aoColumns': " . $filtros ."
			});";
		}

		$script = str_replace("@FILTROS_COLUMNA", $cadena_filtros, $script);

		return $script;
	}

	public function generaTablaTipo3($cabecera, $datos, $pie=FALSE,$acciones=FALSE, $filtros = FALSE, $id=1, $php){


		$tabla = "<table class='table table-bordered datatable' id='table-".$id."'>\n";
		$tabla .= "<thead>\n";
		if ($filtros==TRUE){
			$tabla .= "<tr class='replace-inputs'>\n";
			foreach ($cabecera as $key => $value) {
				$tabla .= "<th>". $value["titulo"]. "</th>\n";
			}
			if($acciones!=FALSE){
				$tabla .= "<th>Acciones</th>\n";
			}
			$tabla .= "</tr>\n";
		}
		$tabla .= "<tr>\n";
		foreach ($cabecera as $key => $value) {
			$tabla .= "<th>". $value["titulo"]. "</th>\n";
		}
		if($acciones!=FALSE){
			$tabla .= "<th>Acciones</th>\n";
		}
		$tabla .= "</tr>\n";
		$tabla .= "</thead>\n";
		$tabla .= "<tbody>\n";
		if($datos!=FALSE){
			foreach ($datos->result() as $row) {
				$tabla .= "<tr>\n";
				foreach ($cabecera as $key => $value) {
					
					$tabla .= "<td>" . $row->$value["campo"] . "</td>\n";
					
				}
				// if($acciones!=FALSE){
				// 	$tabla .= $this->generaBotonesAccion($acciones, $row);
				// }
				$tabla .= "</tr>\n";
			}
		}
		$tabla .= "</tbody>\n";
		if($pie==TRUE){
			$tabla .= "<tfoot>\n";
			$tabla .= "</tr>\n";
			foreach ($cabecera as $key => $value) {
				$tabla .= "<td>". $value["titulo"]. "</td>\n";
			}
			if($acciones!=FALSE){
				$tabla .= "<td>Acciones</td>\n";
			}
			$tabla .= "</tr>\n";
			$tabla .= "</tfoot>\n";
		}
		$tabla .= "</table>\n";
		$tabla .= $this->scriptTablaT3($filtros, $id, $php);

		return $tabla;
	}
	public function generaTablaTipo4($cabecera, $datos, $pie=FALSE, $filtros = FALSE, $id=1, $php){


		$tabla = "<table class='table table-bordered datatable' id='table-".$id."'>\n";
		$tabla .= "<thead>\n";
		if ($filtros==TRUE){
			$tabla .= "<tr class='replace-inputs'>\n";
			foreach ($cabecera as $key => $value) {
				$tabla .= "<th>". $value["titulo"]. "</th>\n";
			}
			
			$tabla .= "<th>Acciones</th>\n";
			
			$tabla .= "</tr>\n";
		}
		$tabla .= "<tr>\n";
		foreach ($cabecera as $key => $value) {
			$tabla .= "<th>". $value["titulo"]. "</th>\n";
		}
		
		$tabla .= "<th>Acciones</th>\n";
		
		$tabla .= "</tr>\n";
		$tabla .= "</thead>\n";
		$tabla .= "<tbody>\n";
		if($datos!=FALSE){
			foreach ($datos->result() as $row) {
				$tabla .= "<tr>\n";
				foreach ($cabecera as $key => $value) {
					
					$tabla .= "<td>" . $row->$value["campo"] . "</td>\n";
					
				}
				//$tabla .="<td>" .$this->generaBotonesAccion(array(
			//"PROCESA"=>array("URL"=>base_url()."index.php/almacen/cA002_pedidos/procesaPedido/","COD"=>"PED_CO_ID","MODAL"=>FALSE,"TITULO"=>"Procesar pedido"), $row)). "</td>";

				// if($acciones!=FALSE){
				//$tabla .= $this->generaBotonesAccion($acciones, $row);
				// }

				//$tabla .="<td>hola</td>\n";
				$tabla .= "</tr>\n";
			}
		}
		$tabla .= "</tbody>\n";
		if($pie==TRUE){
			$tabla .= "<tfoot>\n";
			$tabla .= "</tr>\n";
			foreach ($cabecera as $key => $value) {
				$tabla .= "<td>". $value["titulo"]. "</td>\n";
			}
			
			$tabla .= "<td>Acciones</td>\n";
			
			$tabla .= "</tr>\n";
			$tabla .= "</tfoot>\n";
		}
		$tabla .= "</table>\n";
		$tabla .= $this->scriptTablaT3($filtros, $id, $php);

		return $tabla;
	}

	private function scriptTablaT3($filtros=FALSE, $id, $php){
		$script=<<<EOD
<script type="text/javascript">
var responsiveHelper$id;
var breakpointDefinition = {
    tablet: 1024,
    phone : 480
};
var tableContainer$id;

	jQuery(document).ready(function($)
	{
		tableContainer$id = $("#table-$id");
		
		var table$id = tableContainer$id.dataTable({
			//---Añadimos el array oLanguage para poner los textos en español
			"oLanguage": {
				"sProcessing":     "Procesando...",
			    "sLengthMenu":     "Mostrar _MENU_ registros",
			    "sZeroRecords":    "No se encontraron resultados",
			    "sEmptyTable":     "Ningún dato disponible en esta tabla",
			    "sInfo":           "Registros del _START_ al _END_ de  _TOTAL_ registros",
			    "sInfoEmpty":      "Ningún registro",
			    "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
			    "sInfoPostFix":    "",
			    "sSearch":         "Buscar:",
			    "sUrl":            "",
			    "sInfoThousands":  ",",
			    // "sLoadingRecords": "Cargando...",
			    "oPaginate": {
			        "sFirst":    "Primero",
			        "sLast":     "Último",
			        "sNext":     "Siguiente",
			        "sPrevious": "Anterior"
			    },
			    "oAria": {
			        "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
			        "sSortDescending": ": Activar para ordenar la columna de manera descendente"
			    }
			},
			//
			"sAjaxSource": "$php",
			"bJQueryUI": true,
			"sPaginationType": "bootstrap",
			"aLengthMenu": [[-1, -1, -1, -1], [5, 10, 25, "Todos"]],
			"bStateSave": true,
			"bPaginate": false,
			"bInfo": false,
			

		    // Responsive Settings
		    bAutoWidth     : false,
		    fnPreDrawCallback: function () {
		        // Initialize the responsive datatables helper once.
		        if (!responsiveHelper$id) {
		            responsiveHelper$id = new ResponsiveDatatablesHelper(tableContainer$id, breakpointDefinition);
		        }
		    },
		    fnRowCallback  : function (nRow, aData, iDisplayIndex, iDisplayIndexFull) {
		        responsiveHelper$id.createExpandIcon(nRow);
		    },
		    fnDrawCallback : function (oSettings) {
		        responsiveHelper$id.respond();
		    }
		});
	
		//Decimos si se filtra por columna
		@FILTROS_COLUMNA
		
		$(".dataTables_wrapper select").select2({
			minimumResultsForSearch: -1
		});
	});
</script>	
EOD;
		
		//Tipos de Filtro: text=>{type:'text'}, select>=>{type:'select',values:['valores','opcionales','si no se pasa este parámetro filtra con los valores de la tabla']}
		// number-range=>{type:'number-range'}, checkbox, number
		$cadena_filtros="";
		if($filtros!=FALSE){
			$cadena_filtros="$('#table-$id').dataTable().columnFilter({
				'sPlaceHolder' : 'head:after'
				,'aoColumns': " . $filtros ."
			});";
		}

		$script = str_replace("@FILTROS_COLUMNA", $cadena_filtros, $script);

		return $script;
	}
	


	public function generaTablaTipoServerSide($cabecera, $datos, $pie=FALSE,$acciones=FALSE, $filtros = FALSE, $id=1, $php){


		$tabla = "<table class='table table-bordered datatable' id='table-".$id."'>\n";
		$tabla .= "<thead>\n";
		if ($filtros==TRUE){
			$tabla .= "<tr class='replace-inputs'>\n";
			foreach ($cabecera as $key => $value) {
				$tabla .= "<th>". $value["titulo"]. "</th>\n";
			}
			if($acciones!=FALSE){
				$tabla .= "<th>Acciones</th>\n";
			}
			$tabla .= "</tr>\n";
		}
		$tabla .= "<tr>\n";
		foreach ($cabecera as $key => $value) {
			$tabla .= "<th>". $value["titulo"]. "</th>\n";
		}
		if($acciones!=FALSE){
			$tabla .= "<th>Acciones</th>\n";
		}
		$tabla .= "</tr>\n";
		$tabla .= "</thead>\n";
		$tabla .= "<tbody>\n";
		if($datos!=FALSE){
			foreach ($datos->result() as $row) {
				$tabla .= "<tr>\n";
				foreach ($cabecera as $key => $value) {
					
					$tabla .= "<td>" . $row->$value["campo"] . "</td>\n";
					
				}
				// if($acciones!=FALSE){
				// 	$tabla .= $this->generaBotonesAccion($acciones, $row);
				// }
				$tabla .= "</tr>\n";
			}
		}
		$tabla .= "</tbody>\n";
		if($pie==TRUE){
			$tabla .= "<tfoot>\n";
			$tabla .= "</tr>\n";
			foreach ($cabecera as $key => $value) {
				$tabla .= "<td>". $value["titulo"]. "</td>\n";
			}
			if($acciones!=FALSE){
				$tabla .= "<td>Acciones</td>\n";
			}
			$tabla .= "</tr>\n";
			$tabla .= "</tfoot>\n";
		}
		$tabla .= "</table>\n";
		$tabla .= $this->scriptTablaServerSide($filtros, $id, $php);

		return $tabla;
	}

	private function scriptTablaServerSide($filtros=FALSE, $id, $php){
		$script=<<<EOD
<script type="text/javascript">
var responsiveHelper$id;
var breakpointDefinition = {
    tablet: 1024,
    phone : 480
};
var tableContainer$id;

	jQuery(document).ready(function($)
	{
		tableContainer$id = $("#table-$id");
		
		var table$id = tableContainer$id.dataTable({
			//---Añadimos el array oLanguage para poner los textos en español
			"oLanguage": {
				"sProcessing":     "Procesando...",
			    "sLengthMenu":     "Mostrar _MENU_ registros",
			    "sZeroRecords":    "No se encontraron resultados",
			    "sEmptyTable":     "Ningún dato disponible en esta tabla",
			    "sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
			    "sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0 registros",
			    "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
			    "sInfoPostFix":    "",
			    "sSearch":         "Buscar:",
			    "sUrl":            "",
			    "sInfoThousands":  ",",
			    // "sLoadingRecords": "Cargando...",
			    "oPaginate": {
			        "sFirst":    "Primero",
			        "sLast":     "Último",
			        "sNext":     "Siguiente",
			        "sPrevious": "Anterior"
			    },
			    "oAria": {
			        "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
			        "sSortDescending": ": Activar para ordenar la columna de manera descendente"
			    }
			},
			//
			"processing": true,
			"serverSide": true,
			"ajax": {
				url: "$php",
				type: "GET",
				data: function (d) {
	                //console.log(d); // display all properties to console
	                d.sSearch = d.search.value;
					d.sortDir = d.order[0].dir;
					d.sortCol = d.order[0].column;
	            }
			},
			"bJQueryUI": true,
			"sPaginationType": "bootstrap",
			"aLengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
			"bStateSave": true,
			

		    // Responsive Settings
		    bAutoWidth     : false,
		    fnPreDrawCallback: function () {
		        // Initialize the responsive datatables helper once.
		        if (!responsiveHelper$id) {
		            responsiveHelper$id = new ResponsiveDatatablesHelper(tableContainer$id, breakpointDefinition);
		        }
		    },
		    fnRowCallback  : function (nRow, aData, iDisplayIndex, iDisplayIndexFull) {
		        responsiveHelper$id.createExpandIcon(nRow);
		    },
		    fnDrawCallback : function (oSettings) {
		        responsiveHelper$id.respond();
		    }
		});
	
		//Decimos si se filtra por columna
		@FILTROS_COLUMNA
		
		$(".dataTables_wrapper select").select2({
			minimumResultsForSearch: -1
		});
	});
</script>	
EOD;
		
		//Tipos de Filtro: text=>{type:'text'}, select>=>{type:'select',values:['valores','opcionales','si no se pasa este parámetro filtra con los valores de la tabla']}
		// number-range=>{type:'number-range'}, checkbox, number
		$cadena_filtros="";
		if($filtros!=FALSE){
			$cadena_filtros="$('#table-$id').dataTable().columnFilter({
				'sPlaceHolder' : 'head:after'
				,'aoColumns': " . $filtros ."
			});";
		}

		$script = str_replace("@FILTROS_COLUMNA", $cadena_filtros, $script);

		return $script;
	}
}
?>