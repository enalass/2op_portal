<?php
$clienteId = isset($clienteId) ? (int)$clienteId : 0;
$estudios = isset($estudios) && is_array($estudios) ? $estudios : array('items'=>array(),'page'=>1,'totalPages'=>1,'totalItems'=>0);
$pagos = isset($pagos) && is_array($pagos) ? $pagos : array('items'=>array(),'page'=>1,'totalPages'=>1,'totalItems'=>0);

$buildPageUrl = function($estPage, $payPage) use ($clienteId) {
    return site_url('admin/cA004_cliente/ficha/' . $clienteId) . '?est_page=' . (int)$estPage . '&pay_page=' . (int)$payPage;
};
?>
<div class="table-responsive">
    <table class="table table-head-custom table-vertical-center mb-0">
        <thead>
            <tr>
                <th>ID Solicitud</th>
                <th>Codigo Redsys</th>
                <th>Estado pago</th>
                <th>Canal</th>
                <th>Respuesta</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pagos['items'])): ?>
                <tr><td colspan="6" class="text-center text-muted">No hay pagos asociados.</td></tr>
            <?php else: ?>
                <?php foreach ($pagos['items'] as $pay): ?>
                    <tr>
                        <td><?php echo html_escape($pay['codigo_solicitud']); ?></td>
                        <td><?php echo html_escape($pay['codigo_redsys']); ?></td>
                        <td><?php echo html_escape($pay['estado_pago']); ?></td>
                        <td><?php echo html_escape($pay['canal']); ?></td>
                        <td><?php echo html_escape($pay['respuesta']); ?></td>
                        <td><?php echo html_escape($pay['fecha']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<div class="p-4 d-flex justify-content-between align-items-center">
    <span class="text-muted">Total: <?php echo (int)$pagos['totalItems']; ?></span>
    <ul class="pagination pagination-sm mb-0">
        <li class="page-item <?php echo ($pagos['page'] <= 1) ? 'disabled' : ''; ?>">
            <button type="button" class="page-link js-ficha-page" data-section="pagos" data-url="<?php echo ($pagos['page'] <= 1) ? '' : $buildPageUrl($estudios['page'], $pagos['page'] - 1); ?>" <?php echo ($pagos['page'] <= 1) ? 'disabled="disabled"' : ''; ?>>Anterior</button>
        </li>
        <li class="page-item disabled"><span class="page-link"><?php echo (int)$pagos['page']; ?> / <?php echo (int)$pagos['totalPages']; ?></span></li>
        <li class="page-item <?php echo ($pagos['page'] >= $pagos['totalPages']) ? 'disabled' : ''; ?>">
            <button type="button" class="page-link js-ficha-page" data-section="pagos" data-url="<?php echo ($pagos['page'] >= $pagos['totalPages']) ? '' : $buildPageUrl($estudios['page'], $pagos['page'] + 1); ?>" <?php echo ($pagos['page'] >= $pagos['totalPages']) ? 'disabled="disabled"' : ''; ?>>Siguiente</button>
        </li>
    </ul>
</div>
