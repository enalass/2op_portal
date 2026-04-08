<?php
$solicitudes = isset($solicitudes) && is_array($solicitudes) ? $solicitudes : array();
$selectedSolicitud = isset($selectedSolicitud) && is_array($selectedSolicitud) ? $selectedSolicitud : null;
$selectedSolicitudData = isset($selectedSolicitudData) && is_array($selectedSolicitudData) ? $selectedSolicitudData : array();
$selectedSolicitudFiles = isset($selectedSolicitudFiles) && is_array($selectedSolicitudFiles) ? $selectedSolicitudFiles : array();
$uploadWarningMessage = isset($uploadWarningMessage) ? $uploadWarningMessage : '';
$uploadTechnicalLogEnabled = !empty($uploadTechnicalLogEnabled);
$warningMessage = isset($warningMessage) ? $warningMessage : '';
$csrfTokenName = $this->security->get_csrf_token_name();
$csrfTokenHash = $this->security->get_csrf_hash();
$estadoClienteActual = ($selectedSolicitud && isset($selectedSolicitud['estado_cliente_id'])) ? (int)$selectedSolicitud['estado_cliente_id'] : 0;
$estadoRealActual = ($selectedSolicitud && isset($selectedSolicitud['estado_real_id'])) ? (int)$selectedSolicitud['estado_real_id'] : 0;
$canUploadStudies = ($estadoRealActual >= 5);
$isLockedState = ($estadoClienteActual >= 7);
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
                        <?php elseif ((int)$selectedSolicitud['estado_cliente_id'] >= 4): ?>
                            <?php if ($isLockedState): ?>
                                <div class="alert alert-secondary mb-4">
                                    Estado <?php echo (int)$selectedSolicitud['estado_cliente_id']; ?>: datos personales y ficheros disponibles en modo solo lectura.
                                </div>
                            <?php endif; ?>

                            <details class="mb-4">
                                <summary class="font-weight-bold mb-3" style="cursor:pointer;">Datos personales</summary>
                            <div class="card border mb-0 bg-white">
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <strong>Datos personales:</strong> puedes actualizar tus datos cuando lo necesites.
                                    </div>

                                    <div id="clientDataErrors" class="alert alert-danger" style="display:none;"></div>
                                    <div id="minorRuleNotice" class="alert alert-warning" style="display:none;">Paciente menor de 18 anos: el tipo solicitante debe ser TUTOR.</div>

                                    <form id="clientDataForm" method="post" action="javascript:void(0);">
                                        <input type="hidden" name="ele_id" value="<?php echo (int)$selectedSolicitud['id']; ?>">
                                        <input type="hidden" id="clientDataCsrf" name="<?php echo html_escape($csrfTokenName); ?>" value="<?php echo html_escape($csrfTokenHash); ?>">
                                        <fieldset <?php echo $isLockedState ? 'disabled="disabled"' : ''; ?>>

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

                                        </fieldset>
                                        <?php if (!$isLockedState): ?>
                                            <button type="button" class="btn btn-primary" id="buttonGuardarDatosCliente">Guardar datos</button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                            </details>
                        <?php endif; ?>

                        <?php if ($canUploadStudies): ?>
                            <details class="mb-4">
                                <summary class="font-weight-bold mb-3" style="cursor:pointer;">Ficheros de estudio</summary>
                            <div class="card border mb-0 bg-white">
                                <div class="card-body">
                                    <div class="alert alert-primary">
                                        <strong>Solicitud de estudio:</strong> puedes subir nuevos archivos en cualquier momento (DICOM .dcm/.dicom, .zip, .pdf, .jpg o .png).
                                    </div>

                                    <?php if ($uploadWarningMessage !== ''): ?>
                                        <div class="alert alert-warning"><?php echo html_escape($uploadWarningMessage); ?></div>
                                    <?php endif; ?>

                                    <div id="studyUploadErrors" class="alert alert-danger" style="display:none;"></div>
                                    <div id="studyUploadSuccess" class="alert alert-success" style="display:none;"></div>

                                    <form id="studyUploadForm" method="post" action="javascript:void(0);" enctype="multipart/form-data">
                                        <input type="hidden" name="ele_id" value="<?php echo (int)$selectedSolicitud['id']; ?>">
                                        <input type="hidden" id="studyUploadCsrf" name="<?php echo html_escape($csrfTokenName); ?>" value="<?php echo html_escape($csrfTokenHash); ?>">

                                        <div class="row">
                                            <div class="col-lg-6 mb-5">
                                                <div id="studyDropzone" class="dropzone dropzone-default dropzone-primary border border-primary rounded p-8 text-center" style="cursor:<?php echo $isLockedState ? 'not-allowed' : 'pointer'; ?>; background:#f8fbff; <?php echo $isLockedState ? 'opacity:.7;' : ''; ?>">
                                                    <div class="dropzone-msg dz-message needsclick">
                                                        <h3 class="dropzone-msg-title">Arrastra tus archivos aqui</h3>
                                                        <span class="dropzone-msg-desc">o haz clic para seleccionar archivos</span>
                                                    </div>
                                                </div>
                                                <input type="file" class="d-none" id="estudiosFiles" name="estudios[]" multiple accept=".dcm,.dicom,.zip,.pdf,.jpg,.png" <?php echo $isLockedState ? 'disabled="disabled"' : ''; ?>>

                                                <div class="small text-muted mt-3">Limites: 1GB por archivo, hasta 1500 archivos por envio (max total 1.5GB).</div>
                                                <div id="studySelectedFiles" class="mt-3 small text-muted"></div>

                                                <div id="studyPendingSummary" class="mt-3" style="display:none;">
                                                    <h6 class="mb-2">Resumen antes de subir</h6>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-bordered">
                                                            <thead>
                                                                <tr>
                                                                    <th>Archivo</th>
                                                                    <th>Extension</th>
                                                                    <th>Tamano</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody id="studyPendingSummaryBody"></tbody>
                                                        </table>
                                                    </div>
                                                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-2">
                                                        <div id="studyPendingPaginationInfo" class="small text-muted mr-3 mb-2"></div>
                                                        <div class="mb-2">
                                                            <button type="button" class="btn btn-sm btn-light-primary mr-2" id="studyPendingPrevPage">Anterior</button>
                                                            <button type="button" class="btn btn-sm btn-light-primary" id="studyPendingNextPage">Siguiente</button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="progress mt-3" style="height: 12px; display:none;" id="studyUploadProgressWrap">
                                                    <div class="progress-bar progress-bar-striped progress-bar-animated" id="studyUploadProgressBar" role="progressbar" style="width: 0%">0%</div>
                                                </div>

                                                <?php if (!$isLockedState): ?>
                                                    <button type="button" class="btn btn-primary mt-4" id="buttonSubirEstudios">Subir estudios</button>
                                                <?php endif; ?>
                                            </div>

                                            <div class="col-lg-6">
                                                <h5 class="mb-3">Archivos ya subidos</h5>
                                                <div id="studyUploadedLoadingMessage" class="text-muted">Cargando archivos...</div>
                                                <div id="studyUploadedEmptyMessage" class="text-muted" style="display:none;">Todavia no hay archivos subidos.</div>
                                                <div class="table-responsive" id="studyUploadedTableWrap" style="display:none;">
                                                    <table class="table table-sm table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>Archivo</th>
                                                                <th>Extension</th>
                                                                <th>Tamano</th>
                                                                <th>Fecha</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="studyUploadedFilesBody">
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="d-flex flex-wrap justify-content-between align-items-center mt-3" id="studyUploadedPaginationWrap" style="display:none;">
                                                    <div id="studyPaginationInfo" class="small text-muted mr-3 mb-2"></div>
                                                    <div class="mb-2">
                                                        <button type="button" class="btn btn-sm btn-light-primary mr-2" id="studyPrevPage">Anterior</button>
                                                        <button type="button" class="btn btn-sm btn-light-primary" id="studyNextPage">Siguiente</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            </details>
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

<?php if ($selectedSolicitud && (int)$selectedSolicitud['estado_cliente_id'] >= 4): ?>
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

            var isLocked = <?php echo $isLockedState ? 'true' : 'false'; ?>;
            if(isLocked){
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

<?php if ($selectedSolicitud && (int)$selectedSolicitud['estado_cliente_id'] >= 5): ?>
<script type="text/javascript">
    (function(){
        function byId(id){ return document.getElementById(id); }
        var uploadTechnicalLogEnabled = <?php echo $uploadTechnicalLogEnabled ? 'true' : 'false'; ?>;

        function showError(msg){
            var e = byId('studyUploadErrors');
            var s = byId('studyUploadSuccess');
            if(s){ s.style.display = 'none'; s.innerHTML = ''; }
            if(e){ e.style.display = 'block'; e.innerHTML = msg; }
        }

        function showSuccess(msg){
            var e = byId('studyUploadErrors');
            var s = byId('studyUploadSuccess');
            if(e){ e.style.display = 'none'; e.innerHTML = ''; }
            if(s){ s.style.display = 'block'; s.innerHTML = msg; }
        }

        function maybeLogTechnical(context, technicalMsg){
            if(!uploadTechnicalLogEnabled){ return; }
            if(!technicalMsg){ return; }
            if(!window.console || typeof window.console.log !== 'function'){ return; }
            window.console.log('[Upload][Tecnico][' + context + '] ' + technicalMsg);
        }

        function buildUploadClientError(response, fallbackUserMessage){
            var technical = '';
            if(response && typeof response === 'object'){
                if(typeof response.technical_msg === 'string' && response.technical_msg !== ''){
                    technical = response.technical_msg;
                }else if(typeof response.msg === 'string' && response.msg !== ''){
                    technical = response.msg;
                }
            }

            return {
                userMsg: fallbackUserMessage,
                technicalMsg: technical
            };
        }

        function escapeHtml(text){
            var value = (text === null || typeof text === 'undefined') ? '' : String(text);
            return value
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function formatBytesToMb(bytes){
            var size = parseInt(bytes, 10);
            if(isNaN(size) || size < 0){ size = 0; }
            return (size / 1024 / 1024).toFixed(2) + ' MB';
        }

        function refreshCsrf(payload){
            var csrf = byId('studyUploadCsrf');
            if(!csrf || !payload || !payload.token || !payload.hash){ return; }
            csrf.setAttribute('name', payload.token);
            csrf.value = payload.hash;
        }

        function filesSummary(files){
            if(!files || files.length === 0){
                return 'No hay archivos seleccionados.';
            }
            var totalBytes = 0;
            for(var i=0;i<files.length;i++){ totalBytes += files[i].size || 0; }
            var totalMB = (totalBytes / 1024 / 1024).toFixed(2);
            return files.length + ' archivo(s) seleccionados, ' + totalMB + ' MB en total.';
        }

        function renderPendingSummary(files){
            var wrap = byId('studyPendingSummary');
            var body = byId('studyPendingSummaryBody');
            if(!wrap || !body){
                return;
            }

            body.innerHTML = '';
            if(!files || files.length === 0){
                wrap.style.display = 'none';
                return;
            }

            for(var i = 0; i < files.length; i++){
                var file = files[i];
                var name = file && file.name ? file.name : '-';
                var ext = '-';
                var dotPos = name.lastIndexOf('.');
                if(dotPos > -1 && dotPos < name.length - 1){
                    ext = name.substring(dotPos + 1).toUpperCase();
                }
                var sizeMb = (((file && file.size) ? file.size : 0) / 1024 / 1024).toFixed(2) + ' MB';

                var tr = document.createElement('tr');
                tr.innerHTML = '<td>' + name.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</td>' +
                    '<td>' + ext + '</td>' +
                    '<td>' + sizeMb + '</td>';
                body.appendChild(tr);
            }

            wrap.style.display = 'block';

            initPendingFilesPagination();
        }

        function initPendingFilesPagination(){
            var tbody = byId('studyPendingSummaryBody');
            var info = byId('studyPendingPaginationInfo');
            var prev = byId('studyPendingPrevPage');
            var next = byId('studyPendingNextPage');
            if(!tbody || !info || !prev || !next){
                return;
            }

            var rows = tbody.querySelectorAll('tr');
            var perPage = 10;
            var total = rows.length;
            var totalPages = Math.max(1, Math.ceil(total / perPage));
            var currentPage = 1;

            function renderPage(){
                var start = (currentPage - 1) * perPage;
                var end = start + perPage;

                for(var i=0; i<rows.length; i++){
                    rows[i].style.display = (i >= start && i < end) ? '' : 'none';
                }

                var from = total === 0 ? 0 : (start + 1);
                var to = total === 0 ? 0 : Math.min(end, total);
                info.textContent = from + '-' + to + ' / ' + total + ' (p. ' + currentPage + '/' + totalPages + ')';
                prev.disabled = (currentPage <= 1);
                next.disabled = (currentPage >= totalPages);
            }

            prev.onclick = function(){
                if(currentPage > 1){
                    currentPage--;
                    renderPage();
                }
            };

            next.onclick = function(){
                if(currentPage < totalPages){
                    currentPage++;
                    renderPage();
                }
            };

            renderPage();
        }

        function setInputFiles(fileInput, files){
            if(!fileInput){ return; }
            var dt = new DataTransfer();
            for(var i=0;i<files.length;i++){
                dt.items.add(files[i]);
            }
            fileInput.files = dt.files;
        }

        var CHUNK_THRESHOLD_BYTES = 25 * 1024 * 1024; // >25MB usa chunks
        var CHUNK_SIZE_BYTES = 10 * 1024 * 1024; // 10MB por chunk

        function splitFilesByStrategy(files){
            var normalFiles = [];
            var largeFiles = [];

            for(var i = 0; i < files.length; i++){
                var file = files[i];
                var size = (file && file.size) ? file.size : 0;
                if(size > CHUNK_THRESHOLD_BYTES){
                    largeFiles.push(file);
                }else{
                    normalFiles.push(file);
                }
            }

            return {
                normalFiles: normalFiles,
                largeFiles: largeFiles
            };
        }

        function splitFilesInBatches(files){
            var maxFilesPerBatch = 20;
            var maxBytesPerBatch = 200 * 1024 * 1024; // 200MB por lote
            var batches = [];
            var current = [];
            var currentBytes = 0;

            for(var i = 0; i < files.length; i++){
                var file = files[i];
                var size = (file && file.size) ? file.size : 0;

                var exceedsByCount = current.length >= maxFilesPerBatch;
                var exceedsByBytes = (currentBytes + size) > maxBytesPerBatch;
                if(current.length > 0 && (exceedsByCount || exceedsByBytes)){
                    batches.push(current);
                    current = [];
                    currentBytes = 0;
                }

                current.push(file);
                currentBytes += size;
            }

            if(current.length > 0){
                batches.push(current);
            }

            return batches;
        }

        function uploadBatch(batchFiles, onProgress){
            return new Promise(function(resolve, reject){
                var csrf = byId('studyUploadCsrf');
                var formData = new FormData();
                formData.append('ele_id', '<?php echo (int)$selectedSolicitud['id']; ?>');
                formData.append(csrf.getAttribute('name'), csrf.value);
                formData.append('expected_batch_count', String(batchFiles.length));

                for(var i = 0; i < batchFiles.length; i++){
                    formData.append('estudios[]', batchFiles[i]);
                }

                var xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo site_url('panel/estudio/subir'); ?>', true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                xhr.upload.onprogress = function(evt){
                    if(!evt.lengthComputable){ return; }
                    var pct = Math.round((evt.loaded / evt.total) * 100);
                    if(typeof onProgress === 'function'){
                        onProgress(pct);
                    }
                };

                xhr.onerror = function(){
                    reject({
                        userMsg: 'No se pudieron subir los archivos. Intentalo de nuevo.',
                        technicalMsg: 'Error de red durante la subida del lote.'
                    });
                };

                xhr.onreadystatechange = function(){
                    if(xhr.readyState !== 4){ return; }

                    var response = null;
                    try { response = JSON.parse(xhr.responseText); } catch (e) { response = null; }
                    refreshCsrf(response || {});

                    if(xhr.status >= 200 && xhr.status < 300 && response && response.status === 'success'){
                        resolve(response);
                        return;
                    }

                    reject(buildUploadClientError(response, 'No se pudieron subir los archivos. Intentalo de nuevo.'));
                };

                xhr.send(formData);
            });
        }

        function uploadBatchWithRetry(batchFiles, onProgress, retriesLeft){
            return uploadBatch(batchFiles, onProgress).catch(function(err){
                if(retriesLeft <= 0){
                    throw err;
                }
                return uploadBatchWithRetry(batchFiles, onProgress, retriesLeft - 1);
            });
        }

        function uploadChunkRequest(file, uploadToken, chunkIndex, totalChunks, chunkBlob, chunkOffset, chunkEnd, isLastChunk, onProgress){
            return new Promise(function(resolve, reject){
                var csrf = byId('studyUploadCsrf');
                var formData = new FormData();
                formData.append('ele_id', '<?php echo (int)$selectedSolicitud['id']; ?>');
                formData.append(csrf.getAttribute('name'), csrf.value);
                formData.append('upload_token', uploadToken);
                formData.append('original_name', file.name || 'archivo');
                formData.append('chunk_index', String(chunkIndex));
                formData.append('total_chunks', String(totalChunks));
                formData.append('chunk_offset', String(chunkOffset));
                formData.append('chunk_end', String(chunkEnd));
                formData.append('is_last_chunk', isLastChunk ? '1' : '0');
                formData.append('total_size', String(file.size || 0));
                formData.append('chunk', chunkBlob, file.name || 'chunk.bin');

                var xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo site_url('panel/estudio/subir_chunk'); ?>', true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                xhr.upload.onprogress = function(evt){
                    if(!evt.lengthComputable){ return; }
                    var loaded = chunkOffset + evt.loaded;
                    if(typeof onProgress === 'function'){
                        onProgress(loaded);
                    }
                };

                xhr.onerror = function(){
                    reject({
                        userMsg: 'No se pudo completar la subida del archivo grande. Intentalo de nuevo.',
                        technicalMsg: 'Error de red durante la subida por chunks.'
                    });
                };

                xhr.onreadystatechange = function(){
                    if(xhr.readyState !== 4){ return; }

                    var response = null;
                    try { response = JSON.parse(xhr.responseText); } catch (e) { response = null; }
                    refreshCsrf(response || {});

                    if(xhr.status >= 200 && xhr.status < 300 && response && (response.status === 'partial' || response.status === 'success' || response.status === 'resync')){
                        resolve(response);
                        return;
                    }

                    reject(buildUploadClientError(response, 'No se pudo completar la subida del archivo grande. Intentalo de nuevo.'));
                };

                xhr.send(formData);
            });
        }

        function uploadChunkRequestWithRetry(file, uploadToken, chunkIndex, totalChunks, chunkBlob, chunkOffset, chunkEnd, isLastChunk, onProgress, retriesLeft){
            return uploadChunkRequest(file, uploadToken, chunkIndex, totalChunks, chunkBlob, chunkOffset, chunkEnd, isLastChunk, onProgress).catch(function(err){
                if(retriesLeft <= 0){
                    throw err;
                }
                return uploadChunkRequestWithRetry(file, uploadToken, chunkIndex, totalChunks, chunkBlob, chunkOffset, chunkEnd, isLastChunk, onProgress, retriesLeft - 1);
            });
        }

        function uploadLargeFileInChunks(file, onProgress){
            return new Promise(function(resolve, reject){
                var size = (file && file.size) ? file.size : 0;
                if(size <= 0){
                    reject('Tamano de archivo invalido para subida por chunks.');
                    return;
                }

                var totalChunks = Math.max(1, Math.ceil(size / CHUNK_SIZE_BYTES));
                var uploadToken = String(Date.now()) + '_' + String(Math.floor(Math.random() * 1000000));
                var nextOffset = 0;
                var lastResyncOffset = -1;
                var repeatedResyncCount = 0;

                function processChunk(chunkIndex){
                    var start = nextOffset;
                    if(start >= size){
                        reject('No se pudo completar la subida por chunks: el servidor no confirmo el ultimo tramo.');
                        return;
                    }

                    var end = Math.min(start + CHUNK_SIZE_BYTES, size);
                    var computedChunkIndex = Math.floor(start / CHUNK_SIZE_BYTES);
                    var isLastChunk = end >= size;
                    var blob = file.slice(start, end);

                    uploadChunkRequestWithRetry(
                        file,
                        uploadToken,
                        computedChunkIndex,
                        totalChunks,
                        blob,
                        start,
                        end,
                        isLastChunk,
                        function(loaded){
                            if(typeof onProgress === 'function'){
                                onProgress(Math.min(size, loaded));
                            }
                        },
                        2
                    ).then(function(response){
                        if(response && response.status === 'resync'){
                            var serverOffset = parseInt(response.next_offset, 10);
                            if(isNaN(serverOffset) || serverOffset < 0 || serverOffset > size){
                                reject((response && response.msg) ? response.msg : 'No se pudo resincronizar la subida por chunks.');
                                return;
                            }

                            if(serverOffset === lastResyncOffset){
                                repeatedResyncCount++;
                            }else{
                                repeatedResyncCount = 0;
                                lastResyncOffset = serverOffset;
                            }

                            if(repeatedResyncCount >= 5){
                                reject('Resincronizacion repetida en offset ' + serverOffset + '.');
                                return;
                            }

                            nextOffset = serverOffset;
                            if(typeof onProgress === 'function'){
                                onProgress(nextOffset);
                            }

                            var restartIndex = Math.floor(nextOffset / CHUNK_SIZE_BYTES);
                            processChunk(restartIndex);
                            return;
                        }

                        var confirmedOffset = parseInt(response && response.next_offset, 10);
                        if(isNaN(confirmedOffset) || confirmedOffset < end){
                            confirmedOffset = end;
                        }
                        if(confirmedOffset > size){
                            confirmedOffset = size;
                        }
                        nextOffset = confirmedOffset;

                        if(typeof onProgress === 'function'){
                            onProgress(nextOffset);
                        }

                        if(response && response.status === 'success'){
                            resolve(response);
                            return;
                        }

                        processChunk(chunkIndex + 1);
                    }).catch(function(err){
                        reject(err);
                    });
                }

                processChunk(0);
            });
        }

        function appendUploadedRows(uploadedFiles){
            if(!uploadedFiles || !uploadedFiles.length){
                return;
            }

            var tbody = byId('studyUploadedFilesBody');
            var empty = byId('studyUploadedEmptyMessage');
            var wrap = byId('studyUploadedTableWrap');
            var paginationWrap = byId('studyUploadedPaginationWrap');
            if(!tbody || !empty || !wrap || !paginationWrap){
                return;
            }

            for(var i = 0; i < uploadedFiles.length; i++){
                var item = uploadedFiles[i] || {};
                var tr = document.createElement('tr');
                tr.innerHTML = '<td>' + escapeHtml(item.nombre_original || '-') + '</td>' +
                    '<td>' + escapeHtml(item.extension || '-') + '</td>' +
                    '<td>' + formatBytesToMb(item.tam_bytes || 0) + '</td>' +
                    '<td>' + escapeHtml(item.fecha || '-') + '</td>';
                tbody.appendChild(tr);
            }

            empty.style.display = 'none';
            wrap.style.display = '';
            paginationWrap.style.display = 'flex';
            initUploadedFilesPagination(true);
        }

        function loadUploadedFiles(){
            var loading = byId('studyUploadedLoadingMessage');
            var empty = byId('studyUploadedEmptyMessage');
            var wrap = byId('studyUploadedTableWrap');
            var paginationWrap = byId('studyUploadedPaginationWrap');
            var body = byId('studyUploadedFilesBody');
            var totalInfo = byId('studyPaginationInfo');
            var csrf = byId('studyUploadCsrf');
            if(!loading || !empty || !wrap || !paginationWrap || !body || !totalInfo){
                return;
            }

            loading.style.display = 'block';
            empty.style.display = 'none';
            wrap.style.display = 'none';
            paginationWrap.style.display = 'none';
            body.innerHTML = '';

            fetch('<?php echo site_url('panel/get_solicitud_files'); ?>?ele_id=<?php echo (int)$selectedSolicitud['id']; ?>', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            }).then(function(response){
                return response.json();
            }).then(function(result){
                if(result && result.token && result.hash){
                    refreshCsrf(result);
                }

                loading.style.display = 'none';

                if(!result || result.status !== 'success'){
                    empty.textContent = (result && result.msg) ? result.msg : 'No se pudieron cargar los archivos.';
                    empty.style.display = 'block';
                    return;
                }

                renderUploadedFiles(result.files || []);
            }).catch(function(){
                loading.style.display = 'none';
                empty.textContent = 'No se pudieron cargar los archivos.';
                empty.style.display = 'block';
            });
        }

        function renderUploadedFiles(uploadedFiles){
            var tbody = byId('studyUploadedFilesBody');
            var empty = byId('studyUploadedEmptyMessage');
            var wrap = byId('studyUploadedTableWrap');
            var paginationWrap = byId('studyUploadedPaginationWrap');
            if(!tbody || !empty || !wrap || !paginationWrap){
                return;
            }

            tbody.innerHTML = '';

            if(!uploadedFiles || uploadedFiles.length === 0){
                wrap.style.display = 'none';
                paginationWrap.style.display = 'none';
                empty.textContent = 'Todavia no hay archivos subidos.';
                empty.style.display = 'block';
                return;
            }

            for(var i = 0; i < uploadedFiles.length; i++){
                var item = uploadedFiles[i] || {};
                var tr = document.createElement('tr');
                tr.innerHTML = '<td>' + escapeHtml(item.nombre_original || '-') + '</td>' +
                    '<td>' + escapeHtml((item.extension || '-').toString().toUpperCase()) + '</td>' +
                    '<td>' + formatBytesToMb(item.tam_bytes || 0) + '</td>' +
                    '<td>' + escapeHtml(item.fecha || '-') + '</td>';
                tbody.appendChild(tr);
            }

            empty.style.display = 'none';
            wrap.style.display = '';
            paginationWrap.style.display = 'flex';
            initUploadedFilesPagination(true);
        }

        function uploadFiles(){
            var fileInput = byId('estudiosFiles');
            var button = byId('buttonSubirEstudios');
            var progressWrap = byId('studyUploadProgressWrap');
            var progressBar = byId('studyUploadProgressBar');

            if(!fileInput || !fileInput.files || fileInput.files.length === 0){
                showError('Debes seleccionar al menos un archivo.');
                return;
            }

            var files = [];
            var totalBytes = 0;
            for(var i=0;i<fileInput.files.length;i++){
                files.push(fileInput.files[i]);
                totalBytes += fileInput.files[i].size || 0;
            }

            var strategy = splitFilesByStrategy(files);
            var normalBatches = splitFilesInBatches(strategy.normalFiles);
            var largeFiles = strategy.largeFiles;

            if(normalBatches.length === 0 && largeFiles.length === 0){
                showError('No hay archivos validos para subir.');
                return;
            }

            button.disabled = true;
            button.textContent = 'Preparando subida...';
            if(progressWrap){ progressWrap.style.display = 'flex'; }
            if(progressBar){ progressBar.style.width = '0%'; progressBar.textContent = '0%'; }

            var uploadedBytesDone = 0;
            var aggregatedWarnings = [];

            function setOverallProgressByBytes(loadedBytes){
                if(!progressBar){ return; }
                var overallLoaded = Math.max(0, loadedBytes);
                var overallPct = totalBytes > 0 ? Math.round((overallLoaded / totalBytes) * 100) : 0;
                if(overallPct > 100){ overallPct = 100; }
                progressBar.style.width = overallPct + '%';
                progressBar.textContent = overallPct + '%';
            }

            function finishUpload(){
                button.disabled = false;
                button.textContent = 'Subir estudios';
                if(progressWrap){ progressWrap.style.display = 'none'; }
                if(progressBar){ progressBar.style.width = '0%'; progressBar.textContent = '0%'; }

                var successMsg = 'Subida completada. Lotes normales: ' + normalBatches.length + '. Ficheros por chunks: ' + largeFiles.length + '.';
                if(aggregatedWarnings.length > 0){
                    successMsg += '<br>Observaciones:<br>' + aggregatedWarnings.join('<br>');
                }
                showSuccess(successMsg);
                fileInput.value = '';
                renderPendingSummary([]);
                var selectedInfo = byId('studySelectedFiles');
                if(selectedInfo){ selectedInfo.textContent = 'No hay archivos seleccionados.'; }
            }

            function processLargeFile(index){
                if(index >= largeFiles.length){
                    finishUpload();
                    return;
                }

                var file = largeFiles[index];
                var fileBytes = (file && file.size) ? file.size : 0;
                var initialDone = uploadedBytesDone;
                button.textContent = 'Subiendo archivo grande ' + (index + 1) + '/' + largeFiles.length + ' por chunks...';

                uploadLargeFileInChunks(file, function(fileLoadedBytes){
                    setOverallProgressByBytes(initialDone + fileLoadedBytes);
                }).then(function(result){
                    uploadedBytesDone += fileBytes;
                    setOverallProgressByBytes(uploadedBytesDone);

                    if(result && result.uploaded_files){
                        appendUploadedRows(result.uploaded_files);
                    }

                    if(result && result.msg && result.msg.indexOf('no se pudieron procesar') !== -1){
                        aggregatedWarnings.push('Archivo por chunks ' + escapeHtml(file.name || ('#' + (index + 1))) + ': revisa incidencias en el servidor.');
                    }

                    processLargeFile(index + 1);
                }).catch(function(errorMsg){
                    button.disabled = false;
                    button.textContent = 'Subir estudios';
                    var userMsg = 'No se pudo completar la subida del archivo grande. Intentalo de nuevo.';
                    var technicalMsg = '';
                    if(errorMsg && typeof errorMsg === 'object'){
                        if(errorMsg.userMsg){ userMsg = errorMsg.userMsg; }
                        if(errorMsg.technicalMsg){ technicalMsg = errorMsg.technicalMsg; }
                    }else if(typeof errorMsg === 'string'){
                        technicalMsg = errorMsg;
                    }
                    maybeLogTechnical('chunks', technicalMsg);
                    showError(userMsg);
                });
            }

            function processBatch(index){
                if(index >= normalBatches.length){
                    processLargeFile(0);
                    return;
                }

                var batchFiles = normalBatches[index];
                var batchBytes = 0;
                for(var b = 0; b < batchFiles.length; b++){
                    batchBytes += batchFiles[b].size || 0;
                }

                button.textContent = 'Subiendo lote ' + (index + 1) + '/' + normalBatches.length + '...';

                uploadBatchWithRetry(batchFiles, function(batchPct){
                    setOverallProgressByBytes(uploadedBytesDone + Math.round((batchBytes * batchPct) / 100));
                }, 2).then(function(result){
                    uploadedBytesDone += batchBytes;
                    setOverallProgressByBytes(uploadedBytesDone);

                    if(result && result.uploaded_files){
                        appendUploadedRows(result.uploaded_files);
                    }

                    if(result && result.msg && result.msg.indexOf('Algunos archivos no se pudieron procesar') !== -1){
                        aggregatedWarnings.push('Lote ' + (index + 1) + ': revisa incidencias en el servidor.');
                    }

                    processBatch(index + 1);
                }).catch(function(errorMsg){
                    button.disabled = false;
                    button.textContent = 'Subir estudios';
                    var userMsg = 'No se pudieron subir los archivos. Intentalo de nuevo.';
                    var technicalMsg = '';
                    if(errorMsg && typeof errorMsg === 'object'){
                        if(errorMsg.userMsg){ userMsg = errorMsg.userMsg; }
                        if(errorMsg.technicalMsg){ technicalMsg = errorMsg.technicalMsg; }
                    }else if(typeof errorMsg === 'string'){
                        technicalMsg = errorMsg;
                    }
                    maybeLogTechnical('lotes', technicalMsg);
                    showError(userMsg);
                });
            }

            if(normalBatches.length > 0){
                processBatch(0);
            }else{
                processLargeFile(0);
            }
        }

        function initUploadedFilesPagination(){
            var tbody = byId('studyUploadedFilesBody');
            var info = byId('studyPaginationInfo');
            var prev = byId('studyPrevPage');
            var next = byId('studyNextPage');
            if(!tbody || !info || !prev || !next){
                return;
            }

            var rows = tbody.querySelectorAll('tr');
            var perPage = 10;
            var total = rows.length;
            var totalPages = Math.max(1, Math.ceil(total / perPage));
            var currentPage = parseInt(tbody.getAttribute('data-current-page') || '1', 10);
            if(isNaN(currentPage) || currentPage < 1){ currentPage = 1; }
            var goToLast = (arguments.length > 0 && arguments[0] === true);
            if(goToLast){ currentPage = totalPages; }
            if(currentPage > totalPages){ currentPage = totalPages; }

            function renderPage(){
                var start = (currentPage - 1) * perPage;
                var end = start + perPage;

                for(var i=0; i<rows.length; i++){
                    rows[i].style.display = (i >= start && i < end) ? '' : 'none';
                }

                var from = total === 0 ? 0 : (start + 1);
                var to = total === 0 ? 0 : Math.min(end, total);
                info.textContent = from + '-' + to + ' / ' + total + ' (p. ' + currentPage + '/' + totalPages + ')';
                tbody.setAttribute('data-current-page', String(currentPage));

                prev.disabled = (currentPage <= 1);
                next.disabled = (currentPage >= totalPages);
            }

            prev.onclick = function(){
                if(currentPage > 1){
                    currentPage--;
                    renderPage();
                }
            };

            next.onclick = function(){
                if(currentPage < totalPages){
                    currentPage++;
                    renderPage();
                }
            };

            renderPage();
        }

        document.addEventListener('DOMContentLoaded', function(){
            var dropzone = byId('studyDropzone');
            var fileInput = byId('estudiosFiles');
            var selectedInfo = byId('studySelectedFiles');
            var button = byId('buttonSubirEstudios');
            var isLocked = <?php echo $isLockedState ? 'true' : 'false'; ?>;

            if(fileInput && selectedInfo){
                fileInput.addEventListener('change', function(){
                    selectedInfo.textContent = filesSummary(fileInput.files);
                    renderPendingSummary(fileInput.files);
                });
            }

            if(dropzone && fileInput && !isLocked){
                dropzone.addEventListener('click', function(){ fileInput.click(); });

                ['dragenter', 'dragover'].forEach(function(evtName){
                    dropzone.addEventListener(evtName, function(evt){
                        evt.preventDefault();
                        evt.stopPropagation();
                        dropzone.classList.add('dropzone-hover');
                    });
                });

                ['dragleave', 'drop'].forEach(function(evtName){
                    dropzone.addEventListener(evtName, function(evt){
                        evt.preventDefault();
                        evt.stopPropagation();
                        dropzone.classList.remove('dropzone-hover');
                    });
                });

                dropzone.addEventListener('drop', function(evt){
                    var files = evt.dataTransfer ? evt.dataTransfer.files : null;
                    if(!files || files.length === 0){ return; }
                    setInputFiles(fileInput, files);
                    if(selectedInfo){ selectedInfo.textContent = filesSummary(fileInput.files); }
                    renderPendingSummary(fileInput.files);
                });
            }

            if(button && !isLocked){
                button.addEventListener('click', function(e){
                    e.preventDefault();
                    uploadFiles();
                });
            }

            loadUploadedFiles();
        });
    })();
</script>
<?php endif; ?>
