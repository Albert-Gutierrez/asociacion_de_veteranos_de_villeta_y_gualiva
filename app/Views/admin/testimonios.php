<?php

require __DIR__ . '/layout_inicio.php';

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>

<div class="admin-panel">
    <div class="admin-panel-header">
        <h2>Testimonios de asociados</h2>
        <div class="admin-filtros">
            <a href="testimonios.php" class="btn-tabla <?= $filtro === null ? 'btn-tabla-activo' : '' ?>">Todos</a>
            <a href="testimonios.php?estado=pendiente" class="btn-tabla <?= $filtro === 'pendiente' ? 'btn-tabla-activo' : '' ?>">Pendientes</a>
            <a href="testimonios.php?estado=aprobado" class="btn-tabla <?= $filtro === 'aprobado' ? 'btn-tabla-activo' : '' ?>">Aprobados</a>
            <a href="testimonios.php?estado=rechazado" class="btn-tabla <?= $filtro === 'rechazado' ? 'btn-tabla-activo' : '' ?>">Rechazados</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="admin-tabla">
            <thead>
                <tr><th>Fecha</th><th>Asociado</th><th>Foto</th><th>Mensaje</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($testimonios as $t): ?>
                    <tr>
                        <td><?= e((new DateTime($t['creado_en']))->format('d/m/Y H:i')) ?></td>
                        <td>
                            <a href="asociado.php?id=<?= (int) $t['asociado_id'] ?>">
                                <?= e($t['nombres'] . ' ' . $t['apellidos']) ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($t['foto_ruta']): ?>
                                <a href="../ver_foto_testimonio.php?ruta=<?= urlencode($t['foto_ruta']) ?>" target="_blank" class="btn-tabla">Ver foto</a>
                            <?php else: ?>
                                <span class="admin-texto-suave">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="max-width:320px;"><?= nl2br(e($t['mensaje'])) ?></td>
                        <td>
                            <span class="badge-cuota <?= $t['estado'] === 'aprobado' ? 'badge-cuota-pagado' : ($t['estado'] === 'rechazado' ? 'badge-cuota-vencido' : 'badge-cuota-pendiente') ?>">
                                <?= ucfirst(e($t['estado'])) ?>
                            </span>
                            <?php if ($t['aprobado_por_nombre']): ?>
                                <div class="admin-texto-suave">— <?= e($t['aprobado_por_nombre']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="admin-acciones-cell">
                            <?php if ($t['estado'] !== 'aprobado'): ?>
                                <button type="button" class="btn-tabla btn-testimonio-aprobar" data-id="<?= (int) $t['id'] ?>">Aprobar</button>
                            <?php endif; ?>
                            <?php if ($t['estado'] !== 'rechazado'): ?>
                                <button type="button" class="btn-tabla btn-testimonio-rechazar" data-id="<?= (int) $t['id'] ?>">Rechazar</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($testimonios === []): ?>
                    <tr><td colspan="6" class="admin-tabla-vacia">No hay testimonios.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<input type="hidden" id="testimonios-csrf" value="<?= e($csrf) ?>">

<?php require __DIR__ . '/layout_fin.php'; ?>
