<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<p>Hola <?php echo html_escape(isset($nombre) ? $nombre : ''); ?>,</p>

<p>Hemos procesado tu solicitud correctamente.</p>

<p>Para continuar, debes abonar el pago desde tu perfil de cliente en la plataforma.</p>

<p>
    <strong>Codigo de solicitud:</strong> <?php echo html_escape(isset($request_code) ? $request_code : '-'); ?><br>
    <strong>Importe:</strong> <?php echo html_escape(isset($importe) ? $importe : '-'); ?>
</p>

<?php if (!empty($url_acceso)) : ?>
<p>
    Accede aqui a tu perfil de cliente:<br>
    <a href="<?php echo html_escape($url_acceso); ?>"><?php echo html_escape($url_acceso); ?></a>
</p>
<?php endif; ?>

