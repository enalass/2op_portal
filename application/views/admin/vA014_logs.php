<?PHP
  if(($this->session->userdata('logged'))==TRUE) {
    if(($this->session->userdata('acceso'))>=100){
?>
  <!--begin::Container-->
  <div class="container" style="padding: 0px 5px;">
    
    <!--begin::Card-->
    <div class="card card-custom">
      <div class="card-header flex-wrap border-0 pt-6 pb-0">
        <div class="card-title">
          <h3 class="card-label">Logs acceso herramienta</h3>
        </div>
        <div class="card-toolbar">
          
        </div>
      </div>
      <div class="card-body">
        <!--begin: Search Form-->
        <!--begin::Search Form-->
        <div class="mb-7">
          <div class="row align-items-center">
            <div class="col-lg-9 col-xl-8">
              <div class="row align-items-center">
                <div class="col-md-4 my-2 my-md-0">
                  <div class="input-icon">
                    <input type="text" class="form-control" placeholder="Search..." id="kt_datatable_search_query" />
                    <span>
                      <i class="flaticon2-search-1 text-muted"></i>
                    </span>
                  </div>
                </div>

                

              </div>
            </div>
          </div>
        </div>

        
        <!--end::Search Form-->
        <!--end: Search Form-->
        <!--begin: Datatable-->
        <div class="datatable datatable-bordered datatable-head-custom" id="kt_datatable"></div>
        <!--end: Datatable-->
      </div>
    </div>
    <!--end::Card-->
  </div>
  <!--end::Container -->

  <!-- begin::modal -->
  <div class="modal fade" id="elementModal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="elementModalLabel">Título</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                      <i aria-hidden="true" class="ki ki-close"></i>
                  </button>
              </div>
              <div class="modal-body" id="elementModalBody">
                  Contenido
              </div>
              <div class="modal-footer">
                  <button type="button" class="btn btn-light-primary font-weight-bold" data-dismiss="modal">Cerrar</button>
                  <button type="button" class="btn btn-primary font-weight-bold" id="saveElementButton">Guardar</button>
              </div>
          </div>
      </div>
  </div>
  <!-- end::modal -->

  
  <script src="<?php echo base_url(); ?>assetsM/js/jquery-1.11.0.min.js"></script>
  <!-- begin::GeneralScript -->
  <script>
    jQuery(document).ready(function() {

        jQuery("#buttonAddElement").on("click",function(e){
            e.preventDefault();
            jQuery("#elementModalLabel").html("Añadir Tarifa");
            jQuery("#saveElementButton").html("Crear");
            jQuery("#saveElementButton").removeClass("btn-danger");
            jQuery("#saveElementButton").addClass("btn-primary");

              jQuery.ajax({
              url: '<?php echo base_url(); ?>index.php/admin/<?php echo $controller; ?>/newElement',
              success: function(response)
              {
                jQuery("#elementModalBody").html(response);
              }
            });
              jQuery("#elementModal").modal();
        });

        jQuery("#saveElementButton").on("click", function(e){
          e.preventDefault();

          var data = $('#formModalElement').serialize();
          jQuery.ajax({
            url: jQuery('#formModalElement').attr('action'),
            method: 'POST',
            dataType: 'json',
            data: data,
            success: function(response)
            {
              console.log(response)
              if(response.status=="success"){
                $("#erroresForm").hide();
                jQuery("#elementModal").modal('hide');
                jQuery("#kt_datatable").KTDatatable().reload();
              }else{
                $("#erroresForm").show();
                $("#erroresForm").html(response.msg);
                $("input[name='" + response.token + "']").val(response.hash);

              }
            }
          });
        });

        

        jQuery(document).on("click",".buttonEditElement",function(e){
          e.preventDefault();
          jQuery("#elementModalLabel").html("Editar Tarifa");
          var id = jQuery(this).attr("idclass");
          jQuery("#saveElementButton").html("Guardar");
        jQuery("#saveElementButton").removeClass("btn-danger");
        jQuery("#saveElementButton").addClass("btn-primary");
        
          jQuery.ajax({
          url: '<?php echo base_url(); ?>index.php/admin/<?php echo $controller; ?>/editElement/' + id,
          success: function(response)
          {
            jQuery("#elementModalBody").html(response);
          }
        });
          jQuery("#elementModal").modal();
          //KTDatatableDirecciones.init();
        });


        jQuery(document).on("click",".buttonDeleteElement",function(e){
            e.preventDefault();
            jQuery("#elementModalLabel").html("Borrar Tarifa");
            var id = jQuery(this).attr("idclass");
            jQuery("#saveElementButton").html("Borrar");
            jQuery("#saveElementButton").removeClass("btn-primary");
            jQuery("#saveElementButton").addClass("btn-danger");
              jQuery.ajax({
              url: '<?php echo base_url(); ?>index.php/admin/<?php echo $controller; ?>/deleteElement/' + id,
              success: function(response)
              {
                
                jQuery("#elementModalBody").html(response);
              }
            });
              jQuery("#elementModal").modal();
        });



    });

  </script>
  <!-- end::GeneralScript -->

  <!-- begin::DataTableScript -->
  <script>
    var KTDatatableRemoteAjax = function() {
        // Private functions

        

        var launch = function() {

            var datatable = $('#kt_datatable').KTDatatable({
                // datasource definition
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

                // layout definition
                layout: {
                    scroll: false,
                    footer: false,
                },

                // column sorting
                sortable: true,

                pagination: true,

                search: {
                    input: $('#kt_datatable_search_query'),
                    key: 'generalSearch'
                },

                // columns definition
                columns: [
                {
                    field: 'Name',
                    title: 'Nombre',
                    type: 'text',
                    autoHide: false,
                    textAlign: 'left',
                    template: function(row) {
                      return row.Name 
                    },
                },{
                    field: 'Perfil',
                    title: 'Perfil',
                    type: 'text',
                    autoHide: false,
                    textAlign: 'center',
                    template: function(row) {
                      return row.Perfil 
                    },
                },{
                    field: 'Date',
                    title: 'Fecha acceso',
                    type: 'text',
                    autoHide: false,
                    textAlign: 'center',
                    template: function(row) {
                      return row.Date 
                    },
                }
                ],
                translate: {
                    records: {
                        processing: 'Cargando...',
                        noRecords: 'No se encontrarón registros',
                    },
                    toolbar: {
                        pagination: {
                            items: {
                                default: {
                                    first: 'Primero',
                                    prev: 'Anterior',
                                    next: 'Siguiente',
                                    last: 'Último',
                                    more: 'Más páginas',
                                    input: 'Número de página',
                                    select: 'Seleccionar tamaño de página',
                                },
                                info: 'Viendo {{start}} - {{end}} de {{total}} registros',
                            },
                        },
                    },
                },

            });
        
        };

        return {
            // public functions
            init: function() {
                launch();
            },
        };
    }();

    jQuery(document).ready(function() {
        KTDatatableRemoteAjax.init();
    });
  </script>
  <!-- end::DataTableScript -->
<?PHP
    }else{
      ?>
        <section>
          <h2>ACCESO DENEGADO, CONTACTE CON SU ADMINISTRADOR</h2>
        </section>
      <?PHP
    }
  }
?>