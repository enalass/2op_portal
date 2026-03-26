<div class="card card-custom bg-light">
    <div class="card-body">
        <h4 class="mb-4">Mi perfil</h4>

        <?php if (!empty($perfilError)): ?>
            <div class="alert alert-danger"><?php echo html_escape($perfilError); ?></div>
        <?php endif; ?>

        <?php if (!empty($perfilSuccess)): ?>
            <div class="alert alert-success"><?php echo html_escape($perfilSuccess); ?></div>
        <?php endif; ?>

        <div class="card border bg-white">
            <div class="card-body">
                <form method="post" action="<?php echo site_url('panel/perfil/guardar'); ?>" autocomplete="off">
                    <input type="hidden" name="<?php echo html_escape($csrfTokenName); ?>" value="<?php echo html_escape($csrfTokenHash); ?>">

                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" class="form-control" name="perfil_nombre" value="<?php echo html_escape($perfilNombre); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" value="<?php echo html_escape($perfilEmail); ?>" disabled="disabled">
                    </div>

                    <div class="form-group">
                        <label>Nueva contraseña</label>
                        <input type="password" class="form-control" name="perfil_password" placeholder="Nueva contraseña">
                    </div>

                    <div class="form-group">
                        <label>Repetir contraseña</label>
                        <input type="password" class="form-control" name="perfil_repassword" placeholder="Repetir contraseña">
                    </div>

                    <div class="small text-muted mb-4">Si no quieres cambiar la contraseña, deja ambas casillas vacías.</div>

                    <button type="submit" class="btn btn-primary">Guardar perfil</button>
                </form>
            </div>
        </div>
    </div>
</div>
