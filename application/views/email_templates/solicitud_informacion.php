<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<p>Hola <?php echo html_escape(isset($nombre) ? $nombre : ''); ?>,</p>

<p>Gracias por tu interes. Hemos recibido tu solicitud de informacion y te responderemos lo antes posible.</p>

<p>
    <strong>Asunto:</strong> <?php echo html_escape(isset($asunto) ? $asunto : '-'); ?><br>
    <strong>Canal:</strong> <?php echo html_escape(isset($canal) ? $canal : '-'); ?>
</p>

<?php if (!empty($mensaje)) : ?>
<p>
    <strong>Mensaje:</strong><br>
    <?php echo nl2br(html_escape($mensaje)); ?>
</p>
<?php endif; ?>
