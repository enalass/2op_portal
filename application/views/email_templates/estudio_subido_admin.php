<p>Se ha recibido una nueva subida de estudio por parte de un cliente.</p>

<p><strong>Solicitud:</strong> #<?php echo isset($request_code) ? html_escape($request_code) : '-'; ?></p>
<p><strong>Cliente:</strong> <?php echo isset($nombre) ? html_escape($nombre) : '-'; ?></p>
<p><strong>Archivos subidos:</strong> <?php echo isset($files_count) ? (int)$files_count : 0; ?></p>

<?php if (!empty($url_panel)): ?>
<p>
    <a href="<?php echo html_escape($url_panel); ?>" style="display:inline-block;padding:10px 16px;background:#0d6efd;color:#ffffff;text-decoration:none;border-radius:4px;">
        Revisar solicitud en panel
    </a>
</p>
<?php endif; ?>
