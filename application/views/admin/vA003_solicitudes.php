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
          <h3 class="card-label">Listado de solicitudes</h3>
        </div>
        <div class="card-toolbar">
          
          <!--begin::Button-->
          <a href="#" onclick="return false;" class="btn btn-primary font-weight-bolder" id="buttonAddElement">
            <span class="svg-icon svg-icon-md">
              <!--begin::Svg Icon | path:assets/media/svg/icons/Design/Flatten.svg-->
              <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                  <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                      <polygon points="0 0 24 0 24 24 0 24"/>
                      <path d="M12,18 L7.91561963,20.1472858 C7.42677504,20.4042866 6.82214789,20.2163401 6.56514708,19.7274955 C6.46280801,19.5328351 6.42749334,19.309867 6.46467018,19.0931094 L7.24471742,14.545085 L3.94038429,11.3241562 C3.54490071,10.938655 3.5368084,10.3055417 3.92230962,9.91005817 C4.07581822,9.75257453 4.27696063,9.65008735 4.49459766,9.61846284 L9.06107374,8.95491503 L11.1032639,4.81698575 C11.3476862,4.32173209 11.9473121,4.11839309 12.4425657,4.36281539 C12.6397783,4.46014562 12.7994058,4.61977315 12.8967361,4.81698575 L14.9389263,8.95491503 L19.5054023,9.61846284 C20.0519472,9.69788046 20.4306287,10.2053233 20.351211,10.7518682 C20.3195865,10.9695052 20.2170993,11.1706476 20.0596157,11.3241562 L16.7552826,14.545085 L17.5353298,19.0931094 C17.6286908,19.6374458 17.263103,20.1544017 16.7187666,20.2477627 C16.5020089,20.2849396 16.2790408,20.2496249 16.0843804,20.1472858 L12,18 Z" fill="#000000"/>
                  </g>
              </svg>
              <!--end::Svg Icon-->
            </span>Añadir solicitud
          </a>
          <!--end::Button-->
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
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
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
            jQuery("#elementModalLabel").html("Añadir solicitud");
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
          jQuery("#elementModalLabel").html("Editar solicitud");
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
            jQuery("#elementModalLabel").html("Borrar solicitud");
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
                },
                {
                    field: 'State',
                    title: 'Estado',
                    type: 'text',
                    autoHide: false,
                    textAlign: 'left',
                    template: function(row) {
                      return row.State 
                    },
                },
                {
                    field: 'Origin',
                    title: 'Origen',
                    type: 'text',
                    autoHide: false,
                    textAlign: 'left',
                    template: function(row) {
                      return row.Origin 
                    },
                }, 
                {
                    field: 'Actions',
                    title: 'Acciones',
                    sortable: false,
                    width: 125,
                    overflow: 'visible',
                    autoHide: false,
                    template: function(row) {
                        return '\
                            <a href="javascript:;" class="btn btn-sm btn-clean btn-icon mr-2 buttonEditElement" title="Editar solicitud" idclass="' + row.RecordID + '">\
                                <span class="svg-icon svg-icon-md">\
                                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">\
                                        <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">\
                                            <rect x="0" y="0" width="24" height="24"/>\
                                            <path d="M8,17.9148182 L8,5.96685884 C8,5.56391781 8.16211443,5.17792052 8.44982609,4.89581508 L10.965708,2.42895648 C11.5426798,1.86322723 12.4640974,1.85620921 13.0496196,2.41308426 L15.5337377,4.77566479 C15.8314604,5.0588212 16,5.45170806 16,5.86258077 L16,17.9148182 C16,18.7432453 15.3284271,19.4148182 14.5,19.4148182 L9.5,19.4148182 C8.67157288,19.4148182 8,18.7432453 8,17.9148182 Z" fill="#000000" fill-rule="nonzero"\ transform="translate(12.000000, 10.707409) rotate(-135.000000) translate(-12.000000, -10.707409) "/>\
                                            <rect fill="#000000" opacity="0.3" x="5" y="20" width="15" height="2" rx="1"/>\
                                        </g>\
                                    </svg>\
                                </span>\
                            </a>\
                            <a href="javascript:;" class="btn btn-sm btn-clean btn-icon buttonDeleteElement" title="Borrar solicitud" idclass="' + row.RecordID + '">\
                                <span class="svg-icon svg-icon-md">\
                                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">\
                                        <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">\
                                            <rect x="0" y="0" width="24" height="24"/>\
                                            <path d="M6,8 L6,20.5 C6,21.3284271 6.67157288,22 7.5,22 L16.5,22 C17.3284271,22 18,21.3284271 18,20.5 L18,8 L6,8 Z" fill="#000000" fill-rule="nonzero"/>\
                                            <path d="M14,4.5 L14,4 C14,3.44771525 13.5522847,3 13,3 L11,3 C10.4477153,3 10,3.44771525 10,4 L10,4.5 L5.5,4.5 C5.22385763,4.5 5,4.72385763 5,5 L5,5.5 C5,5.77614237 5.22385763,6 5.5,6 L18.5,6 C18.7761424,6 19,5.77614237 19,5.5 L19,5 C19,4.72385763 18.7761424,4.5 18.5,4.5 L14,4.5 Z" fill="#000000" opacity="0.3"/>\
                                        </g>\
                                    </svg>\
                                </span>\
                            </a>\
                        ';
                    },
                }],
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

            $('#filtro_dep').on('change', function() {
              var depFilter = [];
              $.each($(this).select2('data'), function(){
                  depFilter.push($(this)[0].text);
              });
              console.log(depFilter);
              datatable.search(depFilter,"Department");
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