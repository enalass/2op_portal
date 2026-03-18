<?php if ( ! defined('BASEPATH')) exit('No se permite el acceso directo al script');
class GeneraTabla{

	public function generaTablaTipo1($cabecera, $datos, $pie=FALSE,$acciones=FALSE, $filtros = FALSE){


		$tabla = "<table class='table table-bordered datatable' id='table-1'>\n";
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
					$campo = $value["campo"];
					if($campo=="USR_BL_ACEPTADO"){
						if($row->$campo == 1){
							$tabla .= "<td>SI</td>\n";
						}else{
							$tabla .= "<td>NO</td>\n";
						}
					}else if($campo=="USR_CO_IDCOMERCIAL"){
						if($row->$campo == 2){
							$tabla .= "<td>Fernando Arbizu</td>\n";
						}else if($row->$campo == 3){
							$tabla .= "<td>Javier Parra</td>\n";
						}else{
							$tabla .= "<td>Sin asignar</td>\n";
						}
					}else{
						$tabla .= "<td>" . $row->$campo . "</td>\n";
					}
					
				}
				if($acciones!=FALSE){
					$tabla .= $this->generaBotonesAccion($acciones, $row);
				}
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
		$tabla .= $this->scriptTablaT1($filtros);

		return $tabla;
	}

	private function generaBotonesAccion($acciones,$row){
		$tabla = "<td>";
		if(isset($acciones["EDIT"])){
			$opcion_modal = ($acciones["EDIT"]["MODAL"]==TRUE)?"is-modal-button" : "";
			$accion = $acciones["EDIT"]["COD"];
			$tabla .= 	"<a href='". $acciones["EDIT"]["URL"] . $row->$accion ."' class='btn btn-default btn-sm btn-icon icon-left " . $opcion_modal ."' title='" . $acciones["EDIT"]["TITULO"] . "'>
							<i class='entypo-pencil'></i>
							Editar
						</a> ";
		}
		if(isset($acciones["DELETE"])){
			$opcion_modal = ($acciones["DELETE"]["MODAL"]==TRUE)?"is-modal-button" : "";
			$accion = $acciones["DELETE"]["COD"];
			$tabla .= 	"<a href='". $acciones["DELETE"]["URL"] . $row->$accion ."' class='btn btn-danger btn-sm btn-icon icon-left " . $opcion_modal ."' title='" . $acciones["DELETE"]["TITULO"] . "'>
							<i class='entypo-cancel'></i>
							Borrar
						</a> ";
		}
		if(isset($acciones["INFO"])){
			$opcion_modal = ($acciones["INFO"]["MODAL"]==TRUE)?"is-modal-button" : "";
			$accion = $acciones["INFO"]["COD"];
			$tabla .= 	"<a href='". $acciones["INFO"]["URL"] . $row->$accion ."' class='btn btn-info btn-sm btn-icon icon-left " . $opcion_modal ."' title='" . $acciones["INFO"]["TITULO"] . "'>
							<i class='entypo-info'></i>
							Ficha
						</a> ";
		}
		if(isset($acciones["PROCESA"])){
			$opcion_modal = ($acciones["PROCESA"]["MODAL"]==TRUE)?"is-modal-button" : "";
			$accion = $acciones["PROCESA"]["COD"];
			$tabla .= 	"<a href='". $acciones["PROCESA"]["URL"] . $row->$accion ."' class='btn btn-info btn-sm btn-icon icon-left " . $opcion_modal ."' title='" . $acciones["PROCESA"]["TITULO"] . "'>
							<i class='entypo-info'></i>
							Procesar
						</a> ";
		}
		if(isset($acciones["PDF"])){
			$opcion_modal = ($acciones["PDF"]["MODAL"]==TRUE)?"is-modal-button" : "";
			$tabla .= 	"<a href='". $acciones["PDF"]["URL"] . $row->$acciones["PDF"]["COD"] ."' class='btn btn-orange btn-sm btn-icon icon-left " . $opcion_modal ."' title='" . $acciones["PDF"]["TITULO"] . "' target='_blank'>
							<i class='entypo-eye'></i>
							PDF
						</a> ";
		}
		if(isset($acciones["POSEER"])){
			$opcion_modal = ($acciones["POSEER"]["MODAL"]==TRUE)?"is-modal-button" : "";
			$accion = $acciones["POSEER"]["COD"];
			$tabla .= 	"<a href='". $acciones["POSEER"]["URL"] . $row->$accion ."' class='btn btn-gold btn-sm btn-icon icon-left " . $opcion_modal ."' title='" . $acciones["POSEER"]["TITULO"] . "'>
							<i class='entypo-user'></i>
							Poseer
						</a> ";
		}

		$tabla .= "</td>\n";

		return $tabla;
	}

	private function scriptTablaT1($filtros=FALSE){
		$script=<<<EOD
<script type="text/javascript">
var responsiveHelper;
var breakpointDefinition = {
    tablet: 1024,
    phone : 480
};
var tableContainer;

	jQuery(document).ready(function($)
	{
		tableContainer = $("#table-1");
		
		var table = tableContainer.dataTable({
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
			    "sLoadingRecords": "Cargando...",
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
			"sPaginationType": "bootstrap",
			"aLengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
			"bStateSave": true,
			

		    // Responsive Settings
		    bAutoWidth     : false,
		    fnPreDrawCallback: function () {
		        // Initialize the responsive datatables helper once.
		        if (!responsiveHelper) {
		            responsiveHelper = new ResponsiveDatatablesHelper(tableContainer, breakpointDefinition);
		        }
		    },
		    fnRowCallback  : function (nRow, aData, iDisplayIndex, iDisplayIndexFull) {
		        responsiveHelper.createExpandIcon(nRow);
		    },
		    fnDrawCallback : function (oSettings) {
		        responsiveHelper.respond();
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
			$cadena_filtros="table.columnFilter({
				'sPlaceHolder' : 'head:after'
				,'aoColumns': " . $filtros ."
			});";
		}

		$script = str_replace("@FILTROS_COLUMNA", $cadena_filtros, $script);

		return $script;
	}
}
?>