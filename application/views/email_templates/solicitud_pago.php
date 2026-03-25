<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<p>Hola <?php echo html_escape(isset($nombre) ? $nombre : ''); ?>,</p>

<p>Hemos recibido tu solicitud de pago y ya esta en gestion interna.</p>

<p>
    <strong>Codigo de solicitud:</strong> <?php echo html_escape(isset($request_code) ? $request_code : '-'); ?><br>
    <strong>Importe:</strong> <?php echo html_escape(isset($importe) ? $importe : '-'); ?>
</p>

<?php if (!empty($observaciones)) : ?>
<p>
    <strong>Observaciones:</strong><br>
    <?php echo nl2br(html_escape($observaciones)); ?>
</p>
<?php endif; ?>
