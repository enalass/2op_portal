<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Redirigiendo a Redsys</title>
</head>
<body>
    <p>Estamos redirigiendo a la pasarela de pago segura de Redsys...</p>
    <form id="redsysForm" action="<?php echo html_escape($gateway_url); ?>" method="post">
        <input type="hidden" name="Ds_SignatureVersion" value="<?php echo html_escape($signature_version); ?>">
        <input type="hidden" name="Ds_MerchantParameters" value="<?php echo html_escape($merchant_parameters); ?>">
        <input type="hidden" name="Ds_Signature" value="<?php echo html_escape($signature); ?>">
        <noscript>
            <button type="submit">Continuar al pago</button>
        </noscript>
    </form>
    <script>
        document.getElementById('redsysForm').submit();
    </script>
</body>
</html>
