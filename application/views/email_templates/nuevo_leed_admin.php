<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $panel_url = function_exists('site_url') ? site_url('administradores') : rtrim((string) config_item('base_url'), '/') . '/administradores'; ?>
<p>Se ha registrado un nuevo leed desde el formulario publico.</p>

<p>
    <strong>ID solicitud:</strong> <?php echo html_escape(isset($lead_id) ? $lead_id : '-'); ?><br>
    <strong>Nombre:</strong> <?php echo html_escape(isset($nombre) ? $nombre : '-'); ?><br>
    <strong>Telefono:</strong> <?php echo html_escape(isset($telefono) ? $telefono : '-'); ?><br>
    <strong>Email:</strong> <?php echo html_escape(isset($email) ? $email : '-'); ?><br>
</p>

<?php if (!empty($mensaje)) : ?>
<p>
    <strong>Mensaje:</strong><br>
    <?php echo nl2br(html_escape($mensaje)); ?>
</p>
<?php endif; ?>

<p style="margin-top:24px;">
    <a href="<?php echo html_escape($panel_url); ?>" style="display:inline-block;padding:12px 20px;background:#1f3b6d;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;">Ir al panel</a>
</p>
