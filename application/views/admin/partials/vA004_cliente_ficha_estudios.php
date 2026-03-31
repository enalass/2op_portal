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
                <th>Nombre</th>
                <th>Estado</th>
                <th>Fecha</th>
                <th class="text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($estudios['items'])): ?>
                <tr><td colspan="5" class="text-center text-muted">No hay estudios asociados.</td></tr>
            <?php else: ?>
                <?php foreach ($estudios['items'] as $est): ?>
                    <tr>
                        <td><?php echo html_escape($est['codigo_solicitud']); ?></td>
                        <td><?php echo html_escape($est['nombre']); ?></td>
                        <td><?php echo html_escape($est['estado']); ?></td>
                        <td><?php echo html_escape($est['fecha']); ?></td>
                        <td class="text-right">
                            <a href="javascript:;" class="btn btn-sm btn-light-primary buttonOpenSolicitud" data-sol-id="<?php echo (int)$est['solicitud_id']; ?>">Abrir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<div class="p-4 d-flex justify-content-between align-items-center">
    <span class="text-muted">Total: <?php echo (int)$estudios['totalItems']; ?></span>
    <ul class="pagination pagination-sm mb-0">
        <li class="page-item <?php echo ($estudios['page'] <= 1) ? 'disabled' : ''; ?>">
            <button type="button" class="page-link js-ficha-page" data-section="estudios" data-url="<?php echo ($estudios['page'] <= 1) ? '' : $buildPageUrl($estudios['page'] - 1, $pagos['page']); ?>" <?php echo ($estudios['page'] <= 1) ? 'disabled="disabled"' : ''; ?>>Anterior</button>
        </li>
        <li class="page-item disabled"><span class="page-link"><?php echo (int)$estudios['page']; ?> / <?php echo (int)$estudios['totalPages']; ?></span></li>
        <li class="page-item <?php echo ($estudios['page'] >= $estudios['totalPages']) ? 'disabled' : ''; ?>">
            <button type="button" class="page-link js-ficha-page" data-section="estudios" data-url="<?php echo ($estudios['page'] >= $estudios['totalPages']) ? '' : $buildPageUrl($estudios['page'] + 1, $pagos['page']); ?>" <?php echo ($estudios['page'] >= $estudios['totalPages']) ? 'disabled="disabled"' : ''; ?>>Siguiente</button>
        </li>
    </ul>
</div>
