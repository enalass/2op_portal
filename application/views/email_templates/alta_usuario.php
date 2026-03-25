<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<p>Hola <?php echo html_escape(isset($nombre) ? $nombre : 'usuario'); ?>,</p>

<p>Tu alta en la plataforma se ha completado correctamente.</p>

<p>
    <strong>Usuario:</strong> <?php echo html_escape(isset($usuario) ? $usuario : '-'); ?><br>
    <strong>Email:</strong> <?php echo html_escape(isset($email) ? $email : '-'); ?><br>
    <strong>Contrasena:</strong> <?php echo html_escape(isset($password) ? $password : '-'); ?>
</p>

<?php if (!empty($url_acceso)) : ?>
<p>
    Puedes acceder desde aqui:<br>
    <a href="<?php echo html_escape($url_acceso); ?>"><?php echo html_escape($url_acceso); ?></a>
</p>
<?php endif; ?>
