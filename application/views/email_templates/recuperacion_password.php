<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<p>Hola <?php echo html_escape(isset($nombre) ? $nombre : 'usuario'); ?>,</p>

<p>Hemos generado una nueva contrasena para que puedas volver a acceder a la plataforma.</p>

<p>
    <strong>Email:</strong> <?php echo html_escape(isset($email) ? $email : '-'); ?><br>
    <strong>Nueva contrasena:</strong> <?php echo html_escape(isset($password) ? $password : '-'); ?>
</p>

<?php if (!empty($url_acceso)) : ?>
<p>
    Puedes iniciar sesion desde aqui:<br>
    <a href="<?php echo html_escape($url_acceso); ?>"><?php echo html_escape($url_acceso); ?></a>
</p>
<?php endif; ?>

<p>Por seguridad, te recomendamos cambiar esta contrasena despues de iniciar sesion.</p>
