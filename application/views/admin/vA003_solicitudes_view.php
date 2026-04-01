<?php
if(($this->session->userdata('logged'))==TRUE) {
	if(($this->session->userdata('acceso'))>=100){
?>
<div class="container" style="padding: 0px 5px;">
	<div class="card card-custom">
		<div class="card-header flex-wrap border-0 pt-6 pb-0">
			<div class="card-title">
				<h3 class="card-label">Solicitud #<?php echo (int)$solicitudId; ?></h3>
			</div>
			<div class="card-toolbar">
				<a href="<?php echo base_url(); ?>index.php/admin/<?php echo $controller; ?>" class="btn btn-light-primary font-weight-bolder mr-2">Volver al listado</a>
				<button type="button" class="btn btn-warning font-weight-bolder mr-2" id="buttonSolicitarPagoPage">Solicitar pago</button>
				<button type="button" class="btn btn-primary font-weight-bolder" id="buttonGuardarSolicitudPage">Guardar cambios</button>
			</div>
		</div>
		<div class="card-body">
			<div id="erroresFormPage" class="alert alert-danger" style="display:none;"></div>
			<div id="successFormPage" class="alert alert-success" style="display:none;"></div>
			<?php echo $formSolicitud; ?>
		</div>
	</div>
</div>

<script>
(function(){
	function getEl(id){
		return document.getElementById(id);
	}

	function scrollTopSafe(){
		try {
			window.scrollTo({ top: 0, behavior: 'smooth' });
		} catch (e) {
			window.scrollTo(0, 0);
		}
	}

	function showError(msg){
		var ok = getEl('successFormPage');
		var err = getEl('erroresFormPage');
		if(ok){ ok.style.display = 'none'; }
		if(err){
			err.innerHTML = msg || 'Se ha producido un error.';
			err.style.display = 'block';
		}
		scrollTopSafe();
	}

	function showSuccess(msg){
		var ok = getEl('successFormPage');
		var err = getEl('erroresFormPage');
		if(err){ err.style.display = 'none'; }
		if(ok){
			ok.innerHTML = msg || 'Operacion completada correctamente.';
			ok.style.display = 'block';
		}
		scrollTopSafe();
	}

	function refreshCsrf(response){
		if(!response || !response.token || !response.hash){
			return;
		}
		var tokenInput = document.querySelector('input[name="' + response.token + '"]');
		if(tokenInput){
			tokenInput.value = response.hash;
		}
	}

	function encodeForm(form){
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
		return pairs.join('&');
	}

	function sendForm(url, button, successMessage){
		var form = getEl('formModalElement');
		if(!form){
			showError('No se encontro el formulario de solicitud.');
			return;
		}

		if(button){
			button.disabled = true;
		}

		var xhr = new XMLHttpRequest();
		xhr.open('POST', url, true);
		xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
		xhr.onreadystatechange = function(){
			if(xhr.readyState !== 4){
				return;
			}

			if(button){
				button.disabled = false;
			}

			var response = null;
			try {
				response = JSON.parse(xhr.responseText);
			} catch (err) {
				response = null;
			}

			refreshCsrf(response);

			if(response && response.status === 'success'){
				showSuccess(response.msg ? response.msg : successMessage);
				return;
			}

			showError(response && response.msg ? response.msg : 'No se pudo completar la operacion.');
		};

		xhr.send(encodeForm(form));
	}

	document.addEventListener('click', function(e){
		var btnSave = e.target.closest('#buttonGuardarSolicitudPage');
		if(btnSave){
			e.preventDefault();
			var form = getEl('formModalElement');
			if(!form){
				showError('No se encontro el formulario de solicitud.');
				return false;
			}
			sendForm(form.getAttribute('action'), btnSave, 'Solicitud guardada correctamente.');
			return false;
		}

		var btnPagoPage = e.target.closest('#buttonSolicitarPagoPage');
		if(btnPagoPage){
			e.preventDefault();
			sendForm('<?php echo base_url(); ?>index.php/admin/<?php echo $controller; ?>/solicitarPago', btnPagoPage, 'Solicitud de pago enviada correctamente.');
			return false;
		}

		var btnPagoForm = e.target.closest('#buttonSolicitarPago');
		if(btnPagoForm){
			e.preventDefault();
			sendForm('<?php echo base_url(); ?>index.php/admin/<?php echo $controller; ?>/solicitarPago', btnPagoForm, 'Solicitud de pago enviada correctamente.');
			return false;
		}
	}, true);
})();
</script>
<?php
	}
}
?>
