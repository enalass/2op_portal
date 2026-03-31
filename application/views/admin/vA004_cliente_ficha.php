<?php
$cliente = isset($cliente) ? $cliente : null;
$clienteId = isset($clienteId) ? (int)$clienteId : 0;
$estudios = isset($estudios) && is_array($estudios) ? $estudios : array('items'=>array(),'page'=>1,'totalPages'=>1,'totalItems'=>0);
$pagos = isset($pagos) && is_array($pagos) ? $pagos : array('items'=>array(),'page'=>1,'totalPages'=>1,'totalItems'=>0);
?>

<div class="container" style="padding: 0px 5px;">
    <div class="card card-custom mb-6">
        <div class="card-header">
            <div class="card-title">
                <h3 class="card-label">Ficha de cliente</h3>
            </div>
            <div class="card-toolbar">
                <a href="<?php echo site_url('admin/cA004_cliente'); ?>" class="btn btn-light-primary">Volver al listado</a>
            </div>
        </div>
    </div>

    <div class="card card-custom mb-6">
        <div class="card-header">
            <div class="card-title"><h3 class="card-label">Datos del usuario</h3></div>
        </div>
        <div class="card-body">
            <?php if ($cliente === null): ?>
                <div class="alert alert-warning mb-0">No se encontraron datos del cliente.</div>
            <?php else: ?>
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="text-muted font-size-sm">ID Usuario</div>
                        <div class="font-weight-bold"><?php echo (int)$cliente->USR_CO_ID; ?></div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="text-muted font-size-sm">Nombre</div>
                        <div class="font-weight-bold"><?php echo html_escape($cliente->USR_DS_NOMBRE); ?></div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="text-muted font-size-sm">Apellidos</div>
                        <div class="font-weight-bold"><?php echo html_escape($cliente->USR_DS_APELLIDOS); ?></div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="text-muted font-size-sm">Email</div>
                        <div class="font-weight-bold"><?php echo html_escape($cliente->USR_DS_MAIL); ?></div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="text-muted font-size-sm">Perfil</div>
                        <div class="font-weight-bold"><?php echo (int)$cliente->PER_CO_ID; ?></div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="text-muted font-size-sm">Ultimo acceso</div>
                        <div class="font-weight-bold"><?php echo !empty($cliente->USR_DT_ULTIMOACCESO) ? html_escape($cliente->USR_DT_ULTIMOACCESO) : '-'; ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card card-custom mb-6">
        <div class="card-header">
            <div class="card-title"><h3 class="card-label">Estudios</h3></div>
        </div>
        <div class="card-body p-0" id="ficha-estudios-wrap">
            <?php $this->load->view('admin/partials/vA004_cliente_ficha_estudios.php', array('clienteId' => $clienteId, 'estudios' => $estudios, 'pagos' => $pagos)); ?>
        </div>
    </div>

    <div class="card card-custom mb-6">
        <div class="card-header">
            <div class="card-title"><h3 class="card-label">Pagos</h3></div>
        </div>
        <div class="card-body p-0" id="ficha-pagos-wrap">
            <?php $this->load->view('admin/partials/vA004_cliente_ficha_pagos.php', array('clienteId' => $clienteId, 'estudios' => $estudios, 'pagos' => $pagos)); ?>
        </div>
    </div>
</div>

<div class="modal fade" id="solicitudFichaModal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="solicitudFichaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="solicitudFichaModalLabel">Solicitud</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <i aria-hidden="true" class="ki ki-close"></i>
                </button>
            </div>
            <div class="modal-body" id="solicitudFichaModalBody">Cargando...</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light-primary font-weight-bold" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary font-weight-bold" id="saveSolicitudFichaButton">Guardar solicitud</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function(){
        var baseUrl = '<?php echo base_url(); ?>';

        function loadFichaPartial(url, section){
            var targetSelector = section === 'estudios' ? '#ficha-estudios-wrap' : '#ficha-pagos-wrap';
            var target = document.querySelector(targetSelector);
            if(!target){
                return;
            }

            var partialUrl = url + (url.indexOf('?') === -1 ? '?' : '&') + 'partial=' + encodeURIComponent(section);
            var xhr = new XMLHttpRequest();
            xhr.open('GET', partialUrl, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onreadystatechange = function(){
                if(xhr.readyState !== 4){
                    return;
                }
                if(xhr.status >= 200 && xhr.status < 300){
                    target.innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }

        function openSolicitudModal(solicitudId){
            var titleEl = document.getElementById('solicitudFichaModalLabel');
            var bodyEl = document.getElementById('solicitudFichaModalBody');
            if(titleEl){
                titleEl.innerHTML = 'Solicitud #' + solicitudId;
            }
            if(bodyEl){
                bodyEl.innerHTML = 'Cargando...';
            }

            var xhr = new XMLHttpRequest();
            xhr.open('GET', baseUrl + 'index.php/admin/cA003_solicitudes/editElement/' + encodeURIComponent(solicitudId), true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onreadystatechange = function(){
                if(xhr.readyState !== 4){
                    return;
                }
                if(xhr.status >= 200 && xhr.status < 300){
                    if(bodyEl){
                        bodyEl.innerHTML = xhr.responseText;
                    }
                }else if(bodyEl){
                    bodyEl.innerHTML = '<div class="alert alert-danger mb-0">No se pudo cargar la solicitud.</div>';
                }
            };
            xhr.send();

            if(window.jQuery){
                window.jQuery('#solicitudFichaModal').modal();
            }
        }

        function saveSolicitudFromModal(){
            var form = document.getElementById('formModalElement');
            if(!form){
                return;
            }

            var xhr = new XMLHttpRequest();
            xhr.open('POST', form.getAttribute('action'), true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
            xhr.onreadystatechange = function(){
                if(xhr.readyState !== 4){
                    return;
                }

                var response = null;
                try {
                    response = JSON.parse(xhr.responseText);
                } catch (err) {
                    response = null;
                }

                if(response && response.status === 'success'){
                    if(window.jQuery){
                        window.jQuery('#solicitudFichaModal').modal('hide');
                    }
                    return;
                }

                var errores = document.getElementById('erroresForm');
                if(errores){
                    errores.style.display = 'block';
                    errores.innerHTML = (response && response.msg) ? response.msg : 'No se pudo guardar la solicitud.';
                }

                if(response && response.token && response.hash){
                    var tokenInput = form.querySelector('input[name="' + response.token + '"]');
                    if(tokenInput){
                        tokenInput.value = response.hash;
                    }
                }
            };

            var pairs = [];
            var elements = form.elements;
            for(var i = 0; i < elements.length; i++){
                var el = elements[i];
                if(!el.name || el.disabled){
                    continue;
                }
                if((el.type === 'checkbox' || el.type === 'radio') && !el.checked){
                    continue;
                }
                pairs.push(encodeURIComponent(el.name) + '=' + encodeURIComponent(el.value));
            }
            xhr.send(pairs.join('&'));
        }

        document.addEventListener('click', function(e){
            var btn = e.target.closest('.js-ficha-page');
            if(btn){
                e.preventDefault();
                e.stopPropagation();

                var href = btn.getAttribute('data-url') || '';
                var section = btn.getAttribute('data-section') || '';

                if(href === '' || section === ''){
                    return false;
                }

                loadFichaPartial(href, section);
                return false;
            }

            var openBtn = e.target.closest('.buttonOpenSolicitud');
            if(openBtn){
                e.preventDefault();
                e.stopPropagation();
                var solicitudId = openBtn.getAttribute('data-sol-id') || '';
                if(solicitudId !== ''){
                    openSolicitudModal(solicitudId);
                }
                return false;
            }

            var saveBtn = e.target.closest('#saveSolicitudFichaButton');
            if(saveBtn){
                e.preventDefault();
                e.stopPropagation();
                saveSolicitudFromModal();
                return false;
            }
        }, true);
    })();
</script>
