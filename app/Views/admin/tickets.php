<?php

require __DIR__ . '/layout_inicio.php';

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>

<div class="admin-panel">
    <div class="admin-panel-header">
        <h2>Tickets de asociados</h2>
        <div class="admin-filtros">
            <a href="tickets.php" class="btn-tabla <?= $filtro === null ? 'btn-tabla-activo' : '' ?>">Todos</a>
            <a href="tickets.php?estado=abierto" class="btn-tabla <?= $filtro === 'abierto' ? 'btn-tabla-activo' : '' ?>">Abiertos</a>
            <a href="tickets.php?estado=resuelto" class="btn-tabla <?= $filtro === 'resuelto' ? 'btn-tabla-activo' : '' ?>">Resueltos</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="admin-tabla">
            <thead>
                <tr><th>Fecha</th><th>Asociado</th><th>Mensaje</th><th>Imagen</th><th>Estado</th><th>Respuesta</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $t): ?>
                    <tr>
                        <td><?= e((new DateTime($t['creado_en']))->format('d/m/Y H:i')) ?></td>
                        <td>
                            <a href="asociado.php?id=<?= (int) $t['asociado_id'] ?>">
                                <?= e($t['nombres'] . ' ' . $t['apellidos']) ?>
                            </a>
                            <div class="admin-texto-suave"><?= e($t['cedula']) ?></div>
                        </td>
                        <td style="max-width:280px;"><?= nl2br(e($t['mensaje'])) ?></td>
                        <td>
                            <?php if ($t['imagen_ruta']): ?>
                                <a href="../ver_imagen_ticket.php?ticket_id=<?= (int) $t['id'] ?>" target="_blank" class="btn-tabla">Ver imagen</a>
                            <?php else: ?>
                                <span class="admin-texto-suave">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge-cuota <?= $t['estado'] === 'resuelto' ? 'badge-cuota-pagado' : 'badge-cuota-pendiente' ?>">
                                <?= $t['estado'] === 'resuelto' ? 'Resuelto' : 'Abierto' ?>
                            </span>
                        </td>
                        <td style="max-width:220px;">
                            <?= $t['respuesta'] ? nl2br(e($t['respuesta'])) : '—' ?>
                            <?php if ($t['respondido_por_nombre']): ?>
                                <div class="admin-texto-suave">— <?= e($t['respondido_por_nombre']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($t['estado'] === 'abierto'): ?>
                                <button type="button" class="btn-tabla btn-resolver-ticket" data-id="<?= (int) $t['id'] ?>">Resolver</button>
                            <?php else: ?>
                                <span class="admin-texto-suave">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($tickets === []): ?>
                    <tr><td colspan="7" class="admin-tabla-vacia">No hay tickets.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modal-ticket" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Resolver ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal-ticket-csrf" value="<?= e($csrf) ?>">
                <input type="hidden" id="modal-ticket-id">
                <label for="modal-ticket-respuesta">Respuesta para el afiliado (opcional)</label>
                <textarea id="modal-ticket-respuesta" rows="3" style="width:100%;padding:10px;border:1px solid var(--color-border);border-radius:8px;"></textarea>
                <p id="modal-ticket-mensaje" class="admin-mensaje-accion"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-enviar" id="btn-confirmar-resolver">Marcar como resuelto</button>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/layout_fin.php'; ?>
