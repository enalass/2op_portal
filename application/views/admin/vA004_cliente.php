<?PHP
  if(($this->session->userdata('logged'))==TRUE) {
    if(($this->session->userdata('acceso'))>=100){
?>
  <div class="container" style="padding: 0px 5px;">
    <div class="card card-custom">
      <div class="card-header flex-wrap border-0 pt-6 pb-0">
        <div class="card-title">
          <h3 class="card-label">Listado de clientes</h3>
        </div>
        <div class="card-toolbar">
          <a href="#" onclick="return false;" class="btn btn-primary font-weight-bolder" id="buttonAddCliente">
            <span class="svg-icon svg-icon-md">
              <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                  <rect x="0" y="0" width="24" height="24" />
                  <circle fill="#000000" cx="9" cy="15" r="6" />
                  <path d="M8.8012943,7.00241953 C9.83837775,5.20768121 11.7781543,4 14,4 C17.3137085,4 20,6.6862915 20,10 C20,12.2218457 18.7923188,14.1616223 16.9975805,15.1987057 C16.9991904,15.1326658 17,15.0664274 17,15 C17,10.581722 13.418278,7 9,7 C8.93357256,7 8.86733422,7.00080962 8.8012943,7.00241953 Z" fill="#000000" opacity="0.3" />
                </g>
              </svg>
            </span>Nuevo cliente
          </a>
        </div>
      </div>
      <div class="card-body">
        <div class="mb-7">
          <div class="row align-items-center">
            <div class="col-lg-9 col-xl-8">
              <div class="row align-items-center">
                <div class="col-md-4 my-2 my-md-0">
                  <div class="input-icon">
                    <input type="text" class="form-control" placeholder="Buscar..." id="kt_datatable_search_query" />
                    <span>
                      <i class="flaticon2-search-1 text-muted"></i>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="datatable datatable-bordered datatable-head-custom" id="kt_datatable"></div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="clienteModal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="clienteModalLabel">Titulo</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                      <i aria-hidden="true" class="ki ki-close"></i>
                  </button>
              </div>
              <div class="modal-body" id="clienteModalBody">Contenido</div>
              <div class="modal-footer">
                  <button type="button" class="btn btn-light-primary font-weight-bold" data-dismiss="modal">Cerrar</button>
                  <button type="button" class="btn btn-primary font-weight-bold" id="saveClienteButton">Save</button>
              </div>
          </div>
      </div>
  </div>

    <div class="modal fade" id="solicitudClienteModal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="solicitudClienteModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="solicitudClienteModalLabel">Solicitud</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
              <i aria-hidden="true" class="ki ki-close"></i>
            </button>
          </div>
          <div class="modal-body" id="solicitudClienteModalBody">Cargando...</div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light-primary font-weight-bold" data-dismiss="modal">Cerrar</button>
            <button type="button" class="btn btn-primary font-weight-bold" id="saveSolicitudClienteButton">Guardar solicitud</button>
          </div>
        </div>
      </div>
    </div>

  <script src="<?php echo base_url(); ?>assetsM/js/jquery-1.11.0.min.js"></script>
  <script>
    jQuery(document).ready(function() {
      jQuery("#buttonAddCliente").on("click",function(e){
        e.preventDefault();
        jQuery("#clienteModalLabel").html("Nuevo Cliente");
        jQuery("#saveClienteButton").html("Añadir");
        jQuery("#saveClienteButton").removeClass("btn-danger");
        jQuery("#saveClienteButton").addClass("btn-primary");

        jQuery.ajax({
          url: '<?php echo base_url(); ?>index.php/admin/cA004_cliente/nuevoCliente',
          success: function(response){
            jQuery("#clienteModalBody").html(response);
          }
        });
        jQuery("#clienteModal").modal();
      });

      jQuery("#saveClienteButton").on("click", function(e){
        e.preventDefault();
        var data = jQuery('#formModalUser').serialize();
        jQuery.ajax({
          url: jQuery('#formModalUser').attr('action'),
          method: 'POST',
          dataType: 'json',
          data: data,
          success: function(response){
            if(response.status=="success"){
              jQuery("#erroresForm").hide();
              jQuery("#clienteModal").modal('hide');
              jQuery("#kt_datatable").KTDatatable().reload();
            }else{
              jQuery("#erroresForm").show();
              jQuery("#erroresForm").html(response.msg);
              jQuery("input[name='" + response.token + "']").val(response.hash);
            }
          }
        });
      });

      jQuery(document).on("click",".buttonEditCliente",function(e){
        e.preventDefault();
        jQuery("#clienteModalLabel").html("Editar Cliente");
        var id = jQuery(this).attr("idclass");
        jQuery("#saveClienteButton").html("Guardar");
        jQuery("#saveClienteButton").removeClass("btn-danger");
        jQuery("#saveClienteButton").addClass("btn-primary");

        jQuery.ajax({
          url: '<?php echo base_url(); ?>index.php/admin/cA004_cliente/editaCliente/' + id,
          success: function(response){
            jQuery("#clienteModalBody").html(response);
          }
        });
        jQuery("#clienteModal").modal();
      });

      jQuery(document).on("click",".buttonDeleteCliente",function(e){
        e.preventDefault();
        jQuery("#clienteModalLabel").html("Borrar Cliente");
        var id = jQuery(this).attr("idclass");
        jQuery("#saveClienteButton").html("Borrar");
        jQuery("#saveClienteButton").removeClass("btn-primary");
        jQuery("#saveClienteButton").addClass("btn-danger");

        jQuery.ajax({
          url: '<?php echo base_url(); ?>index.php/admin/cA004_cliente/borrarCliente/' + id,
          success: function(response){
            jQuery("#clienteModalBody").html(response);
          }
        });
        jQuery("#clienteModal").modal();
      });

      jQuery(document).on("click", ".buttonOpenSolicitud", function(e){
        e.preventDefault();
        var solicitudId = jQuery(this).data("sol-id");
        jQuery("#solicitudClienteModalLabel").html("Solicitud #" + solicitudId);
        jQuery("#solicitudClienteModalBody").html("Cargando...");

        jQuery.ajax({
          url: '<?php echo base_url(); ?>index.php/admin/cA003_solicitudes/editElement/' + solicitudId,
          method: 'GET',
          success: function(response){
            jQuery("#solicitudClienteModalBody").html(response);
          },
          error: function(){
            jQuery("#solicitudClienteModalBody").html('<div class="alert alert-danger mb-0">No se pudo cargar la solicitud.</div>');
          }
        });

        jQuery("#solicitudClienteModal").modal();
      });

      jQuery("#saveSolicitudClienteButton").on("click", function(e){
        e.preventDefault();

        if(jQuery("#formModalElement").length === 0){
          return;
        }

        var data = jQuery('#formModalElement').serialize();
        jQuery.ajax({
          url: jQuery('#formModalElement').attr('action'),
          method: 'POST',
          dataType: 'json',
          data: data,
          success: function(response){
            if(response.status=="success"){
              jQuery("#solicitudClienteModal").modal('hide');
            }else{
              jQuery("#erroresForm").show();
              jQuery("#erroresForm").html(response.msg);
              if(response.token && response.hash){
                jQuery("input[name='" + response.token + "']").val(response.hash);
              }
            }
          },
          error: function(){
            if(jQuery("#erroresForm").length){
              jQuery("#erroresForm").show();
              jQuery("#erroresForm").html('No se pudo guardar la solicitud.');
            }
          }
        });
      });
    });
  </script>

  <script>
    var KTDatatableRemoteAjax = function() {
      var launch = function() {
        var datatable = jQuery('#kt_datatable').KTDatatable({
          data: {
            type: 'remote',
            source: {
              read: {
                url: '<?php echo base_url(); ?>index.php/admin/cA004_cliente/getClientes',
                method: 'GET',
              },
            },
            pageSize: 5,
            serverPaging: false,
            serverFiltering: false,
            serverSorting: false,
          },
          layout: {
            scroll: false,
            footer: false,
          },
          sortable: true,
          pagination: true,
          search: {
            input: jQuery('#kt_datatable_search_query'),
            key: 'generalSearch'
          },
          columns: [{
            field: 'Mail',
            title: 'Email',
            type: 'text',
            sortable: 'asc',
            autoHide: false,
            textAlign: 'center',
          },{
            field: '',
            title: 'Nombre',
            type: 'text',
            autoHide: false,
            textAlign: 'center',
            template: function(row) {
              return row.Name + ' ' + row.Surname;
            },
          }, {
            field: 'Perfil',
            title: 'Perfil',
            type: 'text',
            autoHide: false,
            textAlign: 'center',
          }, {
            field: 'Actions',
            title: 'Acciones',
            sortable: false,
            width: 125,
            overflow: 'visible',
            autoHide: false,
            template: function(row) {
              return '<a href="<?php echo base_url(); ?>index.php/admin/cA004_cliente/ficha/' + row.RecordID + '" class="btn btn-sm btn-clean btn-icon mr-2" title="Ver ficha cliente"><i class="la la-eye"></i></a>' +
                '<a href="javascript:;" class="btn btn-sm btn-clean btn-icon mr-2 buttonEditCliente" title="Editar cliente" idclass="' + row.RecordID + '"><i class="la la-edit"></i></a>' +
                '<a href="javascript:;" class="btn btn-sm btn-clean btn-icon buttonDeleteCliente" title="Borrar cliente" idclass="' + row.RecordID + '"><i class="la la-trash"></i></a>';
            },
          }],
          translate: {
            records: {
              processing: 'Cargando...',
              noRecords: 'No se encontraron registros',
            },
            toolbar: {
              pagination: {
                items: {
                  default: {
                    first: 'Primero',
                    prev: 'Anterior',
                    next: 'Siguiente',
                    last: 'Ultimo',
                    more: 'Mas paginas',
                    input: 'Numero de pagina',
                    select: 'Seleccionar tamano de pagina',
                  },
                  info: 'Viendo {{start}} - {{end}} de {{total}} registros',
                },
              },
            },
          },
        });
      };

      return {
        init: function() {
          launch();
        },
      };
    }();

    jQuery(document).ready(function() {
      KTDatatableRemoteAjax.init();
    });
  </script>
<?PHP
    }else{
?>
  <section>
    <h2>ACCESS DENIED, CONTACT YOUR ADMINISTRATOR</h2>
  </section>
<?PHP
    }
  }else{
?>
  <section>
    <h2>ACCESS DENIED, CONTACT YOUR ADMINISTRATOR</h2>
  </section>
<?PHP
  }
?>
