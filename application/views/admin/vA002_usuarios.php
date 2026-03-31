<?PHP
  if(($this->session->userdata('logged'))==TRUE) {
    if(($this->session->userdata('acceso'))>=100){
?>
  <!--begin::Container-->
  <style>
    .gruposUsuarios li{
      font-size: 11px;
      list-style: none;
      text-align: left;
    }
    .gruposUsuarios li::before{
      content: '· ';
    }
  </style>
  <div class="container" style="padding: 0px 5px;">
    
    <!--begin::Card-->
    <div class="card card-custom">
      <div class="card-header flex-wrap border-0 pt-6 pb-0">
        <div class="card-title">
          <h3 class="card-label">Listado de usuarios</h3>
        </div>
        <div class="card-toolbar">
          
          <!--begin::Button-->
          <a href="#" onclick="return false;" class="btn btn-primary font-weight-bolder" id="buttonAddUsuario">
            <span class="svg-icon svg-icon-md">
              <!--begin::Svg Icon | path:assets/media/svg/icons/Design/Flatten.svg-->
              <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                  <rect x="0" y="0" width="24" height="24" />
                  <circle fill="#000000" cx="9" cy="15" r="6" />
                  <path d="M8.8012943,7.00241953 C9.83837775,5.20768121 11.7781543,4 14,4 C17.3137085,4 20,6.6862915 20,10 C20,12.2218457 18.7923188,14.1616223 16.9975805,15.1987057 C16.9991904,15.1326658 17,15.0664274 17,15 C17,10.581722 13.418278,7 9,7 C8.93357256,7 8.86733422,7.00080962 8.8012943,7.00241953 Z" fill="#000000" opacity="0.3" />
                </g>
              </svg>
              <!--end::Svg Icon-->
            </span>Nuevo usuario
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
  <div class="modal fade" id="usuarioModal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="usuarioModalLabel">Título</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                      <i aria-hidden="true" class="ki ki-close"></i>
                  </button>
              </div>
              <div class="modal-body" id="usuarioModalBody">
                  Contenido
              </div>
              <div class="modal-footer">
                  
                  <button type="button" class="btn btn-light-primary font-weight-bold" data-dismiss="modal">Cerrar</button>
                  <button type="button" class="btn btn-primary font-weight-bold" id="saveUsuarioButton">Save</button>
              </div>
          </div>
      </div>
  </div>
  <!-- end::modal -->

  
  <script src="<?php echo base_url(); ?>assetsM/js/jquery-1.11.0.min.js"></script>
  <!-- begin::GeneralScript -->
  <script>
    jQuery(document).ready(function() {
        jQuery("#buttonAddUsuario").on("click",function(e){
          e.preventDefault();
          jQuery("#usuarioModalLabel").html("Nuevo Usuario");
          jQuery("#saveUsuarioButton").html("Añadir");
        jQuery("#saveUsuarioButton").removeClass("btn-danger");
        jQuery("#saveUsuarioButton").addClass("btn-primary");

          jQuery.ajax({
          url: '<?php echo base_url(); ?>index.php/admin/cA002_users/nuevoUsuario',
          success: function(response)
          {
            jQuery("#usuarioModalBody").html(response);
          }
        });
          jQuery("#usuarioModal").modal();
        });

        jQuery("#saveUsuarioButton").on("click", function(e){
          e.preventDefault();

            var data = $('#formModalUser').serialize();
          jQuery.ajax({
          url: jQuery('#formModalUser').attr('action'),
          method: 'POST',
          dataType: 'json',
          data: data,
          success: function(response)
          {
            console.log(response)
            if(response.status=="success"){
              $("#erroresForm").hide();
              jQuery("#usuarioModal").modal('hide');
              jQuery("#kt_datatable").KTDatatable().reload();
            }else{
              $("#erroresForm").show();
              $("#erroresForm").html(response.msg);
              $("input[name='" + response.token + "']").val(response.hash)
            }
          }
        });
        });

        

        jQuery(document).on("click",".buttonEditUsuario",function(e){
          e.preventDefault();
          jQuery("#usuarioModalLabel").html("Editar Usuario");
          var id = jQuery(this).attr("idclass");
          jQuery("#saveUsuarioButton").html("Guardar");
        jQuery("#saveUsuarioButton").removeClass("btn-danger");
        jQuery("#saveUsuarioButton").addClass("btn-primary");

          jQuery.ajax({
          url: '<?php echo base_url(); ?>index.php/admin/cA002_users/editaUsuario/' + id,
          success: function(response)
          {
            jQuery("#usuarioModalBody").html(response);
          }
        });
          jQuery("#usuarioModal").modal();
          //KTDatatableDirecciones.init();
        });

        jQuery(document).on("click",".buttonDeleteUsuario",function(e){
          e.preventDefault();
          jQuery("#usuarioModalLabel").html("Borrar Usuario");
          var id = jQuery(this).attr("idclass");
          jQuery("#saveUsuarioButton").html("Borrar");
        jQuery("#saveUsuarioButton").removeClass("btn-primary");
        jQuery("#saveUsuarioButton").addClass("btn-danger");
          jQuery.ajax({
          url: '<?php echo base_url(); ?>index.php/admin/cA002_users/borrarUsuario/' + id,
          success: function(response)
          {
            
            jQuery("#usuarioModalBody").html(response);
          }
        });
          jQuery("#usuarioModal").modal();
        });


    });

    function eventGroupToUser(action,value,user){
      //console.log('Evento ' + action + ' ' + value + ' to ' + user);
      var data = {'event':action,'value':value,'to':user};
          jQuery.ajax({
            url: '<?php echo base_url(); ?>index.php/admin/cA002_users/gestionaGrupos',
            method: 'POST',
            dataType: 'json',
            data: data,
            success: function(response)
            {
              console.log(response)
              if(response.status=="success"){
                jQuery("#kt_datatable").KTDatatable().reload();
              }
            }
          });

    }

    function eventDepartmentToUser(action,value,app){
      //console.log('Evento ' + action + ' ' + value + ' to ' + app);
      var token = jQuery("input[name='<?php echo $this->security->get_csrf_token_name(); ?>']").val();
      var data = {'event':action,'value':value,'to':app,'<?php echo $this->security->get_csrf_token_name(); ?>':token};
      jQuery.ajax({
        url: '<?php echo base_url(); ?>index.php/admin/cA002_users/manageDepartments',
        method: 'POST',
        dataType: 'json',
        data: data,
        success: function(response)
        {
          //console.log(response)
          $("input[name='" + response.token + "']").val(response.hash);
        }
      });

    }
  </script>
  <!-- end::GeneralScript -->

  <!-- begin::DataTableScript -->
  <script>
    var KTDatatableRemoteAjax = function() {
        // Private functions

        <?php 
                $csrf = array(
                        'name' => $this->security->get_csrf_token_name(),
                        'hash' => $this->security->get_csrf_hash()
                ); 
              ?>

        var launch = function() {

            var datatable = $('#kt_datatable').KTDatatable({
                // datasource definition
                data: {
                    type: 'remote',
                    source: {
                        read: {
                          url: '<?php echo base_url(); ?>index.php/admin/cA002_users/getUsuarios',
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
                      return row.Name + ' ' + row.Surname
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
                        return '\
                            <a href="javascript:;" class="btn btn-sm btn-clean btn-icon mr-2 buttonEditUsuario" title="Editar usuario" idclass="' + row.RecordID + '">\
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
                            <a href="javascript:;" class="btn btn-sm btn-clean btn-icon buttonDeleteUsuario" title="Borrar usuario" idclass="' + row.RecordID + '">\
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
          <h2>ACCESS DENIED, CONTACT YOUR ADMINISTRATOR</h2>
        </section>
      <?PHP
    }
  }
?>