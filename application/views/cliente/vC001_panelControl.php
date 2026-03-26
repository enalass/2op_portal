<?php
$solicitudes = isset($solicitudes) && is_array($solicitudes) ? $solicitudes : array();
$selectedSolicitud = isset($selectedSolicitud) && is_array($selectedSolicitud) ? $selectedSolicitud : null;
$selectedSolicitudData = isset($selectedSolicitudData) && is_array($selectedSolicitudData) ? $selectedSolicitudData : array();
$selectedSolicitudFiles = isset($selectedSolicitudFiles) && is_array($selectedSolicitudFiles) ? $selectedSolicitudFiles : array();
$uploadWarningMessage = isset($uploadWarningMessage) ? $uploadWarningMessage : '';
$warningMessage = isset($warningMessage) ? $warningMessage : '';
$csrfTokenName = $this->security->get_csrf_token_name();
$csrfTokenHash = $this->security->get_csrf_hash();
?>

<div class="card card-custom mb-6">
    <div class="card-header">
        <div class="card-title">
            <h3 class="card-label">Mi panel de solicitudes</h3>
        </div>
    </div>
    <div class="card-body">
        <?php if ($warningMessage !== ''): ?>
            <div class="alert alert-warning"><?php echo html_escape($warningMessage); ?></div>
        <?php endif; ?>

        <?php if (empty($solicitudes)): ?>
            <div class="alert alert-info mb-0">
                Aun no tienes solicitudes disponibles para visualizar. Las solicitudes se mostraran a partir del estado "Solicitado Pago".
            </div>
        <?php else: ?>
            <?php if (count($solicitudes) > 1): ?>
                <div class="row mb-5">
                    <?php foreach ($solicitudes as $item): ?>
                        <?php $isActive = ($selectedSolicitud && (int)$selectedSolicitud['id'] === (int)$item['id']); ?>
                        <div class="col-xl-4 col-md-6 mb-4">
                            <a href="<?php echo site_url('panel'); ?>?solicitud=<?php echo (int)$item['id']; ?>" class="card card-custom card-stretch text-decoration-none <?php echo $isActive ? 'border border-primary' : ''; ?>">
                                <div class="card-body">
                                    <div class="font-size-sm text-muted mb-2"><?php echo html_escape(isset($item['codigo_solicitud']) ? $item['codigo_solicitud'] : ('2OP-0000' . str_pad((string)$item['id'], 5, '0', STR_PAD_LEFT))); ?></div>
                                    <div class="font-weight-bolder text-dark mb-2"><?php echo html_escape($item['nombre']); ?></div>
                                    <div class="text-muted mb-2">Estado: <?php echo html_escape($item['estado_cliente_nombre']); ?></div>
                                    <div class="text-muted">Fecha: <?php echo html_escape($item['fecha_solicitud']); ?></div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($selectedSolicitud): ?>
                <div class="card card-custom bg-light">
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                            <div>
                                <h4 class="mb-1"><?php echo html_escape(isset($selectedSolicitud['codigo_solicitud']) ? $selectedSolicitud['codigo_solicitud'] : ('2OP-0000' . str_pad((string)$selectedSolicitud['id'], 5, '0', STR_PAD_LEFT))); ?></h4>
                                <div class="text-muted"><?php echo html_escape($selectedSolicitud['nombre']); ?></div>
                            </div>
                            <span class="badge badge-primary font-size-sm px-4 py-2"><?php echo html_escape($selectedSolicitud['estado_cliente_nombre']); ?></span>
                        </div>

                        <div class="row mb-5">
                            <div class="col-md-6 mb-3">
                                <div class="text-muted font-size-sm">Importe</div>
                                <div class="font-weight-bold"><?php echo html_escape($selectedSolicitud['importe'] !== '' ? $selectedSolicitud['importe'] : '-'); ?></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="text-muted font-size-sm">Fecha solicitud</div>
                                <div class="font-weight-bold"><?php echo html_escape($selectedSolicitud['fecha_solicitud']); ?></div>
                            </div>
                        </div>

                        <?php if ((int)$selectedSolicitud['estado_cliente_id'] === 2): ?>
                            <div class="alert bg-white border mb-0">
                                <strong>Pago pendiente:</strong> hemos procesado tu solicitud y debes completar el pago desde esta pantalla.
                                <br>
                                <a class="btn btn-warning mt-3" href="<?php echo site_url('panel/pago/iniciar'); ?>?solicitud=<?php echo (int)$selectedSolicitud['id']; ?>">Pagar con tarjeta (Redsys)</a>
                                <div class="small mt-2">Seras redirigido a la pasarela segura de Redsys para completar el pago.</div>
                            </div>
                        <?php elseif ((int)$selectedSolicitud['estado_cliente_id'] === 4): ?>
                            <div class="card border mb-0 bg-white">
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <strong>Informacion requerida:</strong> completa tus datos para continuar con la solicitud.
                                    </div>

                                    <div id="clientDataErrors" class="alert alert-danger" style="display:none;"></div>
                                    <div id="minorRuleNotice" class="alert alert-warning" style="display:none;">Paciente menor de 18 anos: el tipo solicitante debe ser TUTOR.</div>

                                    <form id="clientDataForm" method="post" action="javascript:void(0);">
                                        <input type="hidden" name="ele_id" value="<?php echo (int)$selectedSolicitud['id']; ?>">
                                        <input type="hidden" id="clientDataCsrf" name="<?php echo html_escape($csrfTokenName); ?>" value="<?php echo html_escape($csrfTokenHash); ?>">

                                        <div class="form-group">
                                            <label for="ele_solicitante_tipo">Tipo solicitante</label>
                                            <select class="form-control" id="ele_solicitante_tipo" name="ele_solicitante_tipo">
                                                <option value="PACIENTE" <?php echo (isset($selectedSolicitudData['solicitante_tipo']) && $selectedSolicitudData['solicitante_tipo'] === 'PACIENTE') ? 'selected' : ''; ?>>Paciente</option>
                                                <option value="TUTOR" <?php echo (isset($selectedSolicitudData['solicitante_tipo']) && $selectedSolicitudData['solicitante_tipo'] === 'TUTOR') ? 'selected' : ''; ?>>Tutor</option>
                                            </select>
                                        </div>

                                        <h5 class="mb-3">Datos del paciente</h5>
                                        <div class="row">
                                            <div class="col-md-4 form-group">
                                                <label>Nombre</label>
                                                <input type="text" class="form-control" name="ele_pac_nombre" value="<?php echo html_escape(isset($selectedSolicitudData['pac_nombre']) ? $selectedSolicitudData['pac_nombre'] : ''); ?>">
                                            </div>
                                            <div class="col-md-4 form-group">
                                                <label>Primer apellido</label>
                                                <input type="text" class="form-control" name="ele_pac_apellido1" value="<?php echo html_escape(isset($selectedSolicitudData['pac_apellido1']) ? $selectedSolicitudData['pac_apellido1'] : ''); ?>">
                                            </div>
                                            <div class="col-md-4 form-group">
                                                <label>Segundo apellido</label>
                                                <input type="text" class="form-control" name="ele_pac_apellido2" value="<?php echo html_escape(isset($selectedSolicitudData['pac_apellido2']) ? $selectedSolicitudData['pac_apellido2'] : ''); ?>">
                                            </div>
                                            <div class="col-md-3 form-group">
                                                <label>Fecha nacimiento</label>
                                                <input type="date" class="form-control" name="ele_pac_fecha_nacimiento" value="<?php echo html_escape(isset($selectedSolicitudData['pac_fecha_nacimiento']) ? $selectedSolicitudData['pac_fecha_nacimiento'] : ''); ?>">
                                            </div>
                                            <div class="col-md-3 form-group">
                                                <label>Sexo</label>
                                                <select class="form-control" name="ele_pac_sexo">
                                                    <option value="">Seleccionar</option>
                                                    <option value="M" <?php echo (isset($selectedSolicitudData['pac_sexo']) && $selectedSolicitudData['pac_sexo'] === 'M') ? 'selected' : ''; ?>>Masculino</option>
                                                    <option value="F" <?php echo (isset($selectedSolicitudData['pac_sexo']) && $selectedSolicitudData['pac_sexo'] === 'F') ? 'selected' : ''; ?>>Femenino</option>
                                                    <option value="O" <?php echo (isset($selectedSolicitudData['pac_sexo']) && $selectedSolicitudData['pac_sexo'] === 'O') ? 'selected' : ''; ?>>Otro</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3 form-group">
                                                <label>Tipo documento</label>
                                                <select class="form-control" name="ele_pac_tipo_documento">
                                                    <option value="">Seleccionar</option>
                                                    <option value="DNI" <?php echo (isset($selectedSolicitudData['pac_tipo_documento']) && $selectedSolicitudData['pac_tipo_documento'] === 'DNI') ? 'selected' : ''; ?>>DNI</option>
                                                    <option value="NIE" <?php echo (isset($selectedSolicitudData['pac_tipo_documento']) && $selectedSolicitudData['pac_tipo_documento'] === 'NIE') ? 'selected' : ''; ?>>NIE</option>
                                                    <option value="PASAPORTE" <?php echo (isset($selectedSolicitudData['pac_tipo_documento']) && $selectedSolicitudData['pac_tipo_documento'] === 'PASAPORTE') ? 'selected' : ''; ?>>Pasaporte</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3 form-group">
                                                <label>Documento</label>
                                                <input type="text" class="form-control" name="ele_pac_documento" value="<?php echo html_escape(isset($selectedSolicitudData['pac_documento']) ? $selectedSolicitudData['pac_documento'] : ''); ?>">
                                            </div>
                                            <div class="col-md-4 form-group">
                                                <label>Pais</label>
                                                <input type="text" class="form-control" name="ele_pac_pais" value="<?php echo html_escape(isset($selectedSolicitudData['pac_pais']) ? $selectedSolicitudData['pac_pais'] : ''); ?>">
                                            </div>
                                            <div class="col-md-4 form-group">
                                                <label>Provincia</label>
                                                <input type="text" class="form-control" name="ele_pac_provincia" value="<?php echo html_escape(isset($selectedSolicitudData['pac_provincia']) ? $selectedSolicitudData['pac_provincia'] : ''); ?>">
                                            </div>
                                            <div class="col-md-4 form-group">
                                                <label>Poblacion</label>
                                                <input type="text" class="form-control" name="ele_pac_poblacion" value="<?php echo html_escape(isset($selectedSolicitudData['pac_poblacion']) ? $selectedSolicitudData['pac_poblacion'] : ''); ?>">
                                            </div>
                                            <div class="col-md-6 form-group">
                                                <label>Domicilio</label>
                                                <input type="text" class="form-control" name="ele_pac_domicilio" value="<?php echo html_escape(isset($selectedSolicitudData['pac_domicilio']) ? $selectedSolicitudData['pac_domicilio'] : ''); ?>">
                                            </div>
                                            <div class="col-md-2 form-group">
                                                <label>Cod. postal</label>
                                                <input type="text" class="form-control" name="ele_pac_cp" value="<?php echo html_escape(isset($selectedSolicitudData['pac_cp']) ? $selectedSolicitudData['pac_cp'] : ''); ?>">
                                            </div>
                                            <div class="col-md-4 form-group">
                                                <label>Telefono</label>
                                                <input type="text" class="form-control" name="ele_pac_telefono" value="<?php echo html_escape(isset($selectedSolicitudData['pac_telefono']) ? $selectedSolicitudData['pac_telefono'] : ''); ?>">
                                            </div>
                                            <div class="col-md-6 form-group">
                                                <label>Email</label>
                                                <input type="email" class="form-control" name="ele_pac_email" value="<?php echo html_escape(isset($selectedSolicitudData['pac_email']) ? $selectedSolicitudData['pac_email'] : ''); ?>">
                                            </div>
                                        </div>

                                        <div id="tutorDataBlock" style="display:none;">
                                            <hr>
                                            <h5 class="mb-3">Datos del tutor</h5>
                                            <div class="row">
                                                <div class="col-md-4 form-group">
                                                    <label>Nombre tutor</label>
                                                    <input type="text" class="form-control" name="ele_tut_nombre" value="<?php echo html_escape(isset($selectedSolicitudData['tut_nombre']) ? $selectedSolicitudData['tut_nombre'] : ''); ?>">
                                                </div>
                                                <div class="col-md-4 form-group">
                                                    <label>Primer apellido tutor</label>
                                                    <input type="text" class="form-control" name="ele_tut_apellido1" value="<?php echo html_escape(isset($selectedSolicitudData['tut_apellido1']) ? $selectedSolicitudData['tut_apellido1'] : ''); ?>">
                                                </div>
                                                <div class="col-md-4 form-group">
                                                    <label>Segundo apellido tutor</label>
                                                    <input type="text" class="form-control" name="ele_tut_apellido2" value="<?php echo html_escape(isset($selectedSolicitudData['tut_apellido2']) ? $selectedSolicitudData['tut_apellido2'] : ''); ?>">
                                                </div>
                                                <div class="col-md-3 form-group">
                                                    <label>Fecha nacimiento tutor</label>
                                                    <input type="date" class="form-control" name="ele_tut_fecha_nacimiento" value="<?php echo html_escape(isset($selectedSolicitudData['tut_fecha_nacimiento']) ? $selectedSolicitudData['tut_fecha_nacimiento'] : ''); ?>">
                                                </div>
                                                <div class="col-md-3 form-group">
                                                    <label>Sexo tutor</label>
                                                    <select class="form-control" name="ele_tut_sexo">
                                                        <option value="">Seleccionar</option>
                                                        <option value="M" <?php echo (isset($selectedSolicitudData['tut_sexo']) && $selectedSolicitudData['tut_sexo'] === 'M') ? 'selected' : ''; ?>>Masculino</option>
                                                        <option value="F" <?php echo (isset($selectedSolicitudData['tut_sexo']) && $selectedSolicitudData['tut_sexo'] === 'F') ? 'selected' : ''; ?>>Femenino</option>
                                                        <option value="O" <?php echo (isset($selectedSolicitudData['tut_sexo']) && $selectedSolicitudData['tut_sexo'] === 'O') ? 'selected' : ''; ?>>Otro</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3 form-group">
                                                    <label>Tipo documento tutor</label>
                                                    <select class="form-control" name="ele_tut_tipo_documento">
                                                        <option value="">Seleccionar</option>
                                                        <option value="DNI" <?php echo (isset($selectedSolicitudData['tut_tipo_documento']) && $selectedSolicitudData['tut_tipo_documento'] === 'DNI') ? 'selected' : ''; ?>>DNI</option>
                                                        <option value="NIE" <?php echo (isset($selectedSolicitudData['tut_tipo_documento']) && $selectedSolicitudData['tut_tipo_documento'] === 'NIE') ? 'selected' : ''; ?>>NIE</option>
                                                        <option value="PASAPORTE" <?php echo (isset($selectedSolicitudData['tut_tipo_documento']) && $selectedSolicitudData['tut_tipo_documento'] === 'PASAPORTE') ? 'selected' : ''; ?>>Pasaporte</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3 form-group">
                                                    <label>Documento tutor</label>
                                                    <input type="text" class="form-control" name="ele_tut_documento" value="<?php echo html_escape(isset($selectedSolicitudData['tut_documento']) ? $selectedSolicitudData['tut_documento'] : ''); ?>">
                                                </div>
                                                <div class="col-md-4 form-group">
                                                    <label>Pais tutor</label>
                                                    <input type="text" class="form-control" name="ele_tut_pais" value="<?php echo html_escape(isset($selectedSolicitudData['tut_pais']) ? $selectedSolicitudData['tut_pais'] : ''); ?>">
                                                </div>
                                                <div class="col-md-4 form-group">
                                                    <label>Provincia tutor</label>
                                                    <input type="text" class="form-control" name="ele_tut_provincia" value="<?php echo html_escape(isset($selectedSolicitudData['tut_provincia']) ? $selectedSolicitudData['tut_provincia'] : ''); ?>">
                                                </div>
                                                <div class="col-md-4 form-group">
                                                    <label>Poblacion tutor</label>
                                                    <input type="text" class="form-control" name="ele_tut_poblacion" value="<?php echo html_escape(isset($selectedSolicitudData['tut_poblacion']) ? $selectedSolicitudData['tut_poblacion'] : ''); ?>">
                                                </div>
                                                <div class="col-md-6 form-group">
                                                    <label>Domicilio tutor</label>
                                                    <input type="text" class="form-control" name="ele_tut_domicilio" value="<?php echo html_escape(isset($selectedSolicitudData['tut_domicilio']) ? $selectedSolicitudData['tut_domicilio'] : ''); ?>">
                                                </div>
                                                <div class="col-md-2 form-group">
                                                    <label>Cod. postal tutor</label>
                                                    <input type="text" class="form-control" name="ele_tut_cp" value="<?php echo html_escape(isset($selectedSolicitudData['tut_cp']) ? $selectedSolicitudData['tut_cp'] : ''); ?>">
                                                </div>
                                                <div class="col-md-4 form-group">
                                                    <label>Telefono tutor</label>
                                                    <input type="text" class="form-control" name="ele_tut_telefono" value="<?php echo html_escape(isset($selectedSolicitudData['tut_telefono']) ? $selectedSolicitudData['tut_telefono'] : ''); ?>">
                                                </div>
                                                <div class="col-md-6 form-group">
                                                    <label>Email tutor</label>
                                                    <input type="email" class="form-control" name="ele_tut_email" value="<?php echo html_escape(isset($selectedSolicitudData['tut_email']) ? $selectedSolicitudData['tut_email'] : ''); ?>">
                                                </div>
                                            </div>
                                        </div>

                                        <button type="button" class="btn btn-primary" id="buttonGuardarDatosCliente">Guardar datos</button>
                                    </form>
                                </div>
                            </div>
                        <?php elseif ((int)$selectedSolicitud['estado_cliente_id'] === 5): ?>
                            <div class="card border mb-0 bg-white">
                                <div class="card-body">
                                    <div class="alert alert-primary">
                                        <strong>Solicitud de estudio:</strong> sube uno o varios archivos de estudio (DICOM .dcm/.dicom o .zip).
                                    </div>

                                    <?php if ($uploadWarningMessage !== ''): ?>
                                        <div class="alert alert-warning"><?php echo html_escape($uploadWarningMessage); ?></div>
                                    <?php endif; ?>

                                    <div id="studyUploadErrors" class="alert alert-danger" style="display:none;"></div>
                                    <div id="studyUploadSuccess" class="alert alert-success" style="display:none;"></div>

                                    <form id="studyUploadForm" method="post" action="javascript:void(0);" enctype="multipart/form-data">
                                        <input type="hidden" name="ele_id" value="<?php echo (int)$selectedSolicitud['id']; ?>">
                                        <input type="hidden" id="studyUploadCsrf" name="<?php echo html_escape($csrfTokenName); ?>" value="<?php echo html_escape($csrfTokenHash); ?>">

                                        <div class="form-group mb-3">
                                            <label for="estudiosFiles">Selecciona archivos</label>
                                            <input type="file" class="form-control" id="estudiosFiles" name="estudios[]" multiple accept=".dcm,.dicom,.zip">
                                            <small class="form-text text-muted">Tamano maximo por archivo: 300MB.</small>
                                        </div>

                                        <button type="button" class="btn btn-primary" id="buttonSubirEstudios">Subir estudios</button>
                                    </form>

                                    <hr>
                                    <h5 class="mb-3">Archivos ya subidos</h5>
                                    <?php if (empty($selectedSolicitudFiles)): ?>
                                        <div class="text-muted">Todavia no hay archivos subidos.</div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Archivo</th>
                                                        <th>Extension</th>
                                                        <th>Tamano</th>
                                                        <th>Fecha</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($selectedSolicitudFiles as $fileItem): ?>
                                                        <tr>
                                                            <td><?php echo html_escape($fileItem['nombre_original']); ?></td>
                                                            <td><?php echo html_escape(strtoupper($fileItem['extension'])); ?></td>
                                                            <td><?php echo number_format(((int)$fileItem['tam_bytes']) / 1024 / 1024, 2, ',', '.'); ?> MB</td>
                                                            <td><?php echo html_escape($fileItem['fecha']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php elseif ((int)$selectedSolicitud['estado_cliente_id'] === 6): ?>
                            <div class="alert alert-secondary mb-0">
                                Hemos recibido tus archivos. Tu solicitud esta en revision por el equipo medico.
                            </div>
                        <?php elseif ((int)$selectedSolicitud['estado_cliente_id'] === 7): ?>
                            <div class="alert alert-success mb-0">
                                Tu informe ha sido generado. En breve estara disponible para consulta.
                            </div>
                        <?php elseif ((int)$selectedSolicitud['estado_cliente_id'] === 8): ?>
                            <div class="alert alert-success mb-0">
                                Solicitud finalizada. Gracias por confiar en nosotros.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-light mb-0">
                                Estado actual: <?php echo html_escape($selectedSolicitud['estado_cliente_nombre']); ?>.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($selectedSolicitud && (int)$selectedSolicitud['estado_cliente_id'] === 4): ?>
<script type="text/javascript">
    (function(){
        function byId(id){
            return document.getElementById(id);
        }

        function refreshCsrf(payload){
            var csrfInput = byId('clientDataCsrf');
            if(!csrfInput || !payload || !payload.token || !payload.hash){
                return;
            }

            csrfInput.setAttribute('name', payload.token);
            csrfInput.value = payload.hash;
        }

        function toggleTutorBlock(){
            var select = byId('ele_solicitante_tipo');
            var block = byId('tutorDataBlock');
            if(!select || !block){
                return;
            }

            block.style.display = (select.value === 'TUTOR') ? 'block' : 'none';
        }

        function calculateAgeFromInput(value){
            if(!value){
                return -1;
            }

            var parts = value.split('-');
            if(parts.length !== 3){
                return -1;
            }

            var year = parseInt(parts[0], 10);
            var month = parseInt(parts[1], 10) - 1;
            var day = parseInt(parts[2], 10);
            var birth = new Date(year, month, day);
            if(isNaN(birth.getTime())){
                return -1;
            }

            var today = new Date();
            var age = today.getFullYear() - birth.getFullYear();
            var m = today.getMonth() - birth.getMonth();
            if(m < 0 || (m === 0 && today.getDate() < birth.getDate())){
                age--;
            }

            return age;
        }

        function enforceMinorTutorRule(){
            var birthInput = document.querySelector('input[name="ele_pac_fecha_nacimiento"]');
            var select = byId('ele_solicitante_tipo');
            var notice = byId('minorRuleNotice');
            if(!birthInput || !select || !notice){
                return;
            }

            var age = calculateAgeFromInput(birthInput.value);
            var isMinor = age >= 0 && age < 18;
            if(isMinor){
                notice.style.display = 'block';
                if(select.value !== 'TUTOR'){
                    select.value = 'TUTOR';
                }
            }else{
                notice.style.display = 'none';
            }

            toggleTutorBlock();
        }

        function showError(message){
            var errors = byId('clientDataErrors');
            if(!errors){
                return;
            }

            errors.innerHTML = message || 'No se pudieron guardar los datos.';
            errors.style.display = 'block';
        }

        function clearError(){
            var errors = byId('clientDataErrors');
            if(!errors){
                return;
            }

            errors.innerHTML = '';
            errors.style.display = 'none';
        }

        function submitClientData(){
            var form = byId('clientDataForm');
            var button = byId('buttonGuardarDatosCliente');
            if(!form || !button){
                return;
            }

            clearError();
            enforceMinorTutorRule();

            button.disabled = true;
            button.textContent = 'Guardando...';

            var body = new URLSearchParams(new FormData(form));

            fetch('<?php echo site_url('panel/datos/guardar'); ?>', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: body,
                credentials: 'same-origin'
            }).then(function(response){
                return response.json();
            }).then(function(result){
                refreshCsrf(result || {});

                if(result && result.status === 'success'){
                    window.location.href = '<?php echo site_url('panel'); ?>?solicitud=<?php echo (int)$selectedSolicitud['id']; ?>';
                    return;
                }

                showError((result && result.msg) ? result.msg : 'No se pudieron guardar los datos.');
                button.disabled = false;
                button.textContent = 'Guardar datos';
            }).catch(function(){
                showError('No se pudieron guardar los datos. Intentalo de nuevo.');
                button.disabled = false;
                button.textContent = 'Guardar datos';
            });
        }

        document.addEventListener('DOMContentLoaded', function(){
            var select = byId('ele_solicitante_tipo');
            var birthInput = document.querySelector('input[name="ele_pac_fecha_nacimiento"]');
            var button = byId('buttonGuardarDatosCliente');
            var form = byId('clientDataForm');

            if(select){
                select.addEventListener('change', function(){
                    toggleTutorBlock();
                    enforceMinorTutorRule();
                });
            }

            if(birthInput){
                birthInput.addEventListener('change', enforceMinorTutorRule);
            }

            if(button){
                button.addEventListener('click', function(e){
                    e.preventDefault();
                    submitClientData();
                });
            }

            if(form){
                form.addEventListener('submit', function(e){
                    e.preventDefault();
                    submitClientData();
                });
            }

            toggleTutorBlock();
            enforceMinorTutorRule();
        });
    })();
</script>
<?php endif; ?>

<?php if ($selectedSolicitud && (int)$selectedSolicitud['estado_cliente_id'] === 5): ?>
<script type="text/javascript">
    jQuery(function(){
        function refreshCsrf(payload){
            if(payload && payload.token && payload.hash){
                jQuery('#studyUploadCsrf').attr('name', payload.token).val(payload.hash);
            }
        }

        jQuery(document).on('click', '#buttonSubirEstudios', function(e){
            e.preventDefault();

            var $button = jQuery(this);
            var $errors = jQuery('#studyUploadErrors');
            var $success = jQuery('#studyUploadSuccess');
            var fileInput = document.getElementById('estudiosFiles');

            $errors.hide().html('');
            $success.hide().html('');

            if(!fileInput || !fileInput.files || fileInput.files.length === 0){
                $errors.html('Debes seleccionar al menos un archivo.').show();
                return;
            }

            var formData = new FormData();
            formData.append('ele_id', '<?php echo (int)$selectedSolicitud['id']; ?>');

            var $csrfInput = jQuery('#studyUploadCsrf');
            formData.append($csrfInput.attr('name'), $csrfInput.val());

            for(var i = 0; i < fileInput.files.length; i++){
                formData.append('estudios[]', fileInput.files[i]);
            }

            $button.prop('disabled', true).text('Subiendo...');

            jQuery.ajax({
                method: 'POST',
                url: '<?php echo site_url('panel/estudio/subir'); ?>',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done(function(result){
                refreshCsrf(result || {});

                if(result && result.status === 'success'){
                    $success.html(result.msg || 'Subida completada.').show();
                    window.location.href = '<?php echo site_url('panel'); ?>?solicitud=<?php echo (int)$selectedSolicitud['id']; ?>';
                    return;
                }

                $errors.html((result && result.msg) ? result.msg : 'No se pudieron subir los archivos.').show();
                $button.prop('disabled', false).text('Subir estudios');
            }).fail(function(){
                $errors.html('No se pudieron subir los archivos. Intentalo de nuevo.').show();
                $button.prop('disabled', false).text('Subir estudios');
            });
        });
    });
</script>
<?php endif; ?>
