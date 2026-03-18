<blockquote id="erroresForm" class="alert alert-danger" role="alert" style="display: none;">
</blockquote>
@FIELD_ID
<div class="row">
	<div class="col-md-4">
		
		<div class="form-group">
			<label for="user_mail" class="control-label">E-Mail</label>
			@FIELD_MAIL
		</div>
		
	</div>
	
	<div class="col-md-4">
		
		<div class="form-group">
			<label for="user_password" class="control-label">Contraseña</label>
			@FIELD_PASSWORD
		</div>	
	
	</div>

	<div class="col-md-4">
		
		<div class="form-group">
			<label for="user_repassword" class="control-label">Reescribir contraseña</label>
			@FIELD_REPASSWORD
		</div>	
	
	</div>
</div>

<div class="row">
	<div class="col-md-6">
		
		<div class="form-group">
			<label for="user_perfil" class="control-label">Perfil</label>
			@FIELD_PERFIL
		</div>	
		
	</div>
</div>

<div class="row">
	<div class="col-md-6">
		
		<div class="form-group">
			<label for="user_name" class="control-label">Nombre</label>
			@FIELD_NAME
		</div>	
		
	</div>
	
	<div class="col-md-6">
		
		<div class="form-group">
			<label for="user_surname" class="control-label">Apellidos</label>
			@FIELD_SURNAME
		</div>	
	
	</div>
</div>

<div class="row">
	<div class="col-md-12">
		
		<div class="form-group">
			@FIELD_DEPARTAMENTO
		</div>	
		
	</div>
</div>

<script type="text/javascript">
	jQuery(document).ready(function($)
	{
		$("form").submit(function(e){
            e.preventDefault();
            // Iniciar peticion AJAX
            $.ajax({
                url: $(this).attr("action"),  // ruta del controlador y accion
                method: 'POST',
				dataType: 'json',
                data: $(this).serialize(),     // Formulario
                error: function()
				{
					alert("¡Ha ocurrido un error!");
				},
                success: function(response)
				{          // Funcion que recibe response
                    var status = response.status;

                    if(status=="success"){
                    	//alert("CORRECTO");
                    	$("#erroresForm").html("");
                    	$("#erroresForm").hide();
                    	jQuery('#modal-7 .modal-body').html("");
                    	jQuery('#modal-7').modal('hide');
                    	window.location.reload();
                    }else{
                    	//alert("INCORRECTO");
                    	$("input[name=csrf_tok]").val(response.hash);
                    	$("#erroresForm").show();
                    	$("#erroresForm").html(response.msg);
                    }
                }
            });
        });
	});
</script>