<?PHP
  if(($this->session->userdata('logged'))==TRUE) {
    if(($this->session->userdata('acceso'))>=100){
?>
  <div class="container" style="padding: 0px 5px;">
    <div class="card card-custom">
      <div class="card-header flex-wrap border-0 pt-6 pb-0">
        <div class="card-title">
          <h3 class="card-label">Listado de pagos</h3>
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

  <script src="<?php echo base_url(); ?>assetsM/js/jquery-1.11.0.min.js"></script>
  <script>
    var KTDatatableRemoteAjax = function() {
      var launch = function() {
        var datatable = jQuery('#kt_datatable').KTDatatable({
          data: {
            type: 'remote',
            source: {
              read: {
                url: '<?php echo base_url(); ?>index.php/admin/<?php echo $controller; ?>/getList',
                method: 'GET',
              },
            },
            pageSize: 10,
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
            field: 'SolicitudClienteID',
            title: 'ID Solicitud',
            type: 'text',
            width: 140,
            autoHide: false,
            textAlign: 'center',
            template: function(row) {
              return row.SolicitudClienteID ? row.SolicitudClienteID : '-';
            },
          }, {
            field: 'SolicitudID',
            title: 'Solicitud',
            type: 'number',
            autoHide: false,
            textAlign: 'center',
            template: function(row) {
              return row.SolicitudID > 0 ? ('#' + row.SolicitudID + ' - ' + row.Solicitud) : '-';
            },
          }, {
            field: 'Cliente',
            title: 'Cliente asociado',
            type: 'text',
            autoHide: false,
            textAlign: 'left',
          }, {
            field: 'PagoEstado',
            title: 'Estado del pago',
            type: 'text',
            autoHide: false,
            textAlign: 'center',
          }, {
            field: 'Canal',
            title: 'Canal',
            type: 'text',
            width: 90,
            autoHide: false,
            textAlign: 'center',
          }, {
            field: 'CodigoPagoRedsys',
            title: 'Codigo pago Redsys',
            type: 'text',
            width: 170,
            autoHide: false,
            textAlign: 'center',
          }, {
            field: 'Respuesta',
            title: 'Codigo',
            type: 'text',
            width: 80,
            autoHide: false,
            textAlign: 'center',
          }, {
            field: 'FechaPago',
            title: 'Fecha',
            type: 'text',
            autoHide: false,
            textAlign: 'center',
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
