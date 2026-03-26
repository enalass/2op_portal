<blockquote id="erroresForm" class="alert alert-danger" role="alert" style="display: none;">
</blockquote>
@FIELD_ID
@FIELD_ACTION
<div class="row">
	<div class="col-md-12">
		<h5 class="mb-4">Formulario adquisición Leed</h5>
		<div class="row">
			<div class="col-md-8">
				<div class="form-group">
					<label for="ele_name" class="control-label">Nombre cliente</label>
					@FIELD_NAME
				</div>	
			</div>
			<div class="col-md-4">
				<div class="form-group">
					<label for="ele_leed_form" class="control-label">Estado</label>
					@FIELD_LEED_FORM
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group">
					<label for="ele_fso_id" class="control-label">Origen de la solicitud</label>
					@FIELD_ORIGEN_SOLICITUD
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group">
					<label for="ele_adq_mail" class="control-label">Mail</label>
					@FIELD_ADQ_MAIL
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group">
					<label for="ele_adq_phone" class="control-label">Teléfono</label>
					@FIELD_ADQ_PHONE
				</div>
			</div>
		</div>
		<div class="form-group">
			<label for="ele_adq_reason" class="control-label">Motivo de la consulta</label>
			@FIELD_ADQ_REASON
		</div>

		<hr>
		<h5 class="mb-4">Datos pedido</h5>
		<div class="row">
			<div class="col-md-4">
				<div class="form-group">
					<label for="ele_ped_importe" class="control-label">Importe</label>
					@FIELD_PED_IMPORTE
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group">
					<label for="ele_ped_idioma" class="control-label">Idioma preferido</label>
					@FIELD_PED_IDIOMA
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group">
					<label for="ele_pago_fecha_solicitud" class="control-label">Fecha solicitud pago</label>
					@FIELD_PAGO_FECHA_SOLICITUD
				</div>
			</div>
			<div class="col-md-12">
				@FIELD_SOLICITAR_PAGO
			</div>
		</div>

		<hr>
		<h5 class="mb-4">Formulario de contratación</h5>
		<div class="form-group">
			<label for="ele_solicitante_tipo" class="control-label">Paciente/Tutor</label>
			@FIELD_SOLICITANTE_TIPO
		</div>

		<hr>
		<h5 class="mb-4">Datos personales del paciente</h5>
		<div class="row">
			<div class="col-md-4">
				<div class="form-group">
					<label for="ele_pac_nombre" class="control-label">Nombre</label>
					@FIELD_PAC_NOMBRE
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group">
					<label for="ele_pac_apellido1" class="control-label">Primer apellido</label>
					@FIELD_PAC_APELLIDO1
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group">
					<label for="ele_pac_apellido2" class="control-label">Segundo apellido</label>
					@FIELD_PAC_APELLIDO2
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-4">
				<div class="form-group">
					<label for="ele_pac_fecha_nacimiento" class="control-label">Fecha de nacimiento</label>
					@FIELD_PAC_FECHA_NACIMIENTO
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group">
					<label for="ele_pac_sexo" class="control-label">Sexo</label>
					@FIELD_PAC_SEXO
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group">
					<label for="ele_pac_tipo_documento" class="control-label">Tipo de documento</label>
					@FIELD_PAC_TIPO_DOCUMENTO
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-4">
				<div class="form-group">
					<label for="ele_pac_documento" class="control-label"># Documento</label>
					@FIELD_PAC_DOCUMENTO
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group">
					<label for="ele_pac_pais" class="control-label">Pais</label>
					@FIELD_PAC_PAIS
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group">
					<label for="ele_pac_provincia" class="control-label">Provincia</label>
					@FIELD_PAC_PROVINCIA
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-4">
				<div class="form-group">
					<label for="ele_pac_poblacion" class="control-label">Población</label>
					@FIELD_PAC_POBLACION
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group">
					<label for="ele_pac_domicilio" class="control-label">Domicilio</label>
					@FIELD_PAC_DOMICILIO
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group">
					<label for="ele_pac_cp" class="control-label">Cod. postal</label>
					@FIELD_PAC_CP
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6">
				<div class="form-group">
					<label for="ele_pac_email" class="control-label">Correo electrónico</label>
					@FIELD_PAC_EMAIL
				</div>
			</div>
			<div class="col-md-6">
				<div class="form-group">
					<label for="ele_pac_telefono" class="control-label">Teléfono</label>
					@FIELD_PAC_TELEFONO
				</div>
			</div>
		</div>

		<div id="bloqueTutor" style="display:none;">
			<hr>
			<h5 class="mb-4">Datos personales del tutor</h5>
			<div class="row">
				<div class="col-md-4">
					<div class="form-group">
						<label for="ele_tut_nombre" class="control-label">Nombre</label>
						@FIELD_TUT_NOMBRE
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-group">
						<label for="ele_tut_apellido1" class="control-label">Primer apellido</label>
						@FIELD_TUT_APELLIDO1
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-group">
						<label for="ele_tut_apellido2" class="control-label">Segundo apellido</label>
						@FIELD_TUT_APELLIDO2
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-4">
					<div class="form-group">
						<label for="ele_tut_fecha_nacimiento" class="control-label">Fecha de nacimiento</label>
						@FIELD_TUT_FECHA_NACIMIENTO
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-group">
						<label for="ele_tut_sexo" class="control-label">Sexo</label>
						@FIELD_TUT_SEXO
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-group">
						<label for="ele_tut_tipo_documento" class="control-label">Tipo de documento</label>
						@FIELD_TUT_TIPO_DOCUMENTO
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-4">
					<div class="form-group">
						<label for="ele_tut_documento" class="control-label"># Documento</label>
						@FIELD_TUT_DOCUMENTO
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-group">
						<label for="ele_tut_pais" class="control-label">Pais</label>
						@FIELD_TUT_PAIS
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-group">
						<label for="ele_tut_provincia" class="control-label">Provincia</label>
						@FIELD_TUT_PROVINCIA
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-4">
					<div class="form-group">
						<label for="ele_tut_poblacion" class="control-label">Población</label>
						@FIELD_TUT_POBLACION
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-group">
						<label for="ele_tut_domicilio" class="control-label">Domicilio</label>
						@FIELD_TUT_DOMICILIO
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-group">
						<label for="ele_tut_cp" class="control-label">Cod. postal</label>
						@FIELD_TUT_CP
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label for="ele_tut_email" class="control-label">Correo electrónico</label>
						@FIELD_TUT_EMAIL
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group">
						<label for="ele_tut_telefono" class="control-label">Teléfono</label>
						@FIELD_TUT_TELEFONO
					</div>
				</div>
			</div>
		</div>

		<hr>
		<h5 class="mb-4">Gestión interna</h5>
		<div class="form-group">
			<label for="ele_notas" class="control-label">Notas</label>
			@FIELD_NOTAS
		</div>
	</div>
</div>

<script>
(function() {
	function normalizeImporte(rawValue) {
		var value = (rawValue || '').toString().trim();
		if (value === '') {
			return '';
		}

		value = value.replace(/\s+/g, '').replace(',', '.');

		if (!/^\d+(\.\d+)?$/.test(value)) {
			return rawValue;
		}

		if (value.indexOf('.') === -1) {
			return value + '.00';
		}

		var parts = value.split('.');
		if (parts[1].length === 0) {
			return parts[0] + '.00';
		}
		if (parts[1].length === 1) {
			return parts[0] + '.' + parts[1] + '0';
		}

		return parts[0] + '.' + parts[1].substring(0, 2);
	}

	function applyNormalizeImporte() {
		var $importe = jQuery('#ele_ped_importe');
		if ($importe.length === 0) {
			return;
		}
		$importe.val(normalizeImporte($importe.val()));
	}

	function toggleTutor() {
		var tipo = jQuery('#ele_solicitante_tipo').val();
		if (tipo === 'TUTOR') {
			jQuery('#bloqueTutor').show();
		} else {
			jQuery('#bloqueTutor').hide();
		}
	}

	jQuery(document).on('blur', '#ele_ped_importe', applyNormalizeImporte);
	jQuery(document).on('submit', '#formModalElement', applyNormalizeImporte);
	jQuery(document).on('change', '#ele_solicitante_tipo', toggleTutor);
	toggleTutor();
})();
</script>
