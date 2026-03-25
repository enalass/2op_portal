<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo html_escape($subject); ?></title>
</head>
<body style="margin:0;padding:0;background:#f5f7fb;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f5f7fb;padding:24px 0;">
        <tr>
            <td align="center">
                <table width="640" cellpadding="0" cellspacing="0" border="0" style="max-width:640px;background:#ffffff;border-radius:8px;overflow:hidden;">
                    <tr>
                        <td style="background:#1f3b6d;padding:20px 24px;color:#ffffff;">
                            <h1 style="margin:0;font-size:20px;line-height:1.2;"><?php echo html_escape(isset($app_name) ? $app_name : 'Portal'); ?></h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;line-height:1.6;font-size:14px;">
                            <?php echo $body_content; ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 24px;background:#f1f5f9;color:#64748b;font-size:12px;">
                            Este email ha sido generado automaticamente. Si necesitas ayuda, responde a este correo.
                            <br>
                            <?php echo html_escape(isset($current_year) ? $current_year : date('Y')); ?> - <?php echo html_escape(isset($app_name) ? $app_name : 'Portal'); ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
