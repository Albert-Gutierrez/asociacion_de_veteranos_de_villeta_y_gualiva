<?php

require __DIR__ . '/layout_inicio.php';

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>

<div class="admin-detalle-grid">
    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>¿Hiciste un pago que no aparece aquí?</h2>
        </div>
        <p class="admin-texto-suave">Cuéntanos qué pagaste y adjunta una captura o foto del comprobante; el tesorero o un administrador lo revisará.</p>

        <form id="form-ticket-cuota" class="form-ticket" data-tipo="cuota" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="tipo" value="cuota">
            <div class="campo">
                <label for="mensaje-cuota">¿Qué pago hiciste?</label>
                <textarea id="mensaje-cuota" name="mensaje" rows="3" required placeholder="Ej: Pagué la cuota de julio por Nequi el día 15..."></textarea>
            </div>
            <div class="campo">
                <label for="imagen-cuota">Captura o foto del pago (opcional, JPG/PNG, máx. 5 MB)</label>
                <input type="file" id="imagen-cuota" name="imagen" accept="image/png,image/jpeg,image/webp">
            </div>
            <button type="submit" class="btn-enviar">Enviar reporte</button>
        </form>
        <p id="ticket-cuota-mensaje" class="admin-mensaje-accion"></p>
    </div>

    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>¿Tus datos personales están mal escritos?</h2>
        </div>
        <p class="admin-texto-suave">Cuéntanos qué dato está incorrecto (nombre, cédula, teléfono, etc.); un administrador lo corregirá.</p>

        <form id="form-ticket-datos" class="form-ticket" data-tipo="datos" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="tipo" value="datos">
            <div class="campo">
                <label for="mensaje-datos">¿Qué dato hay que corregir?</label>
                <textarea id="mensaje-datos" name="mensaje" rows="3" required placeholder="Ej: Mi cédula quedó mal digitada, es 123..."></textarea>
            </div>
            <div class="campo">
                <label for="imagen-datos">Foto de tu cédula u otro soporte (opcional, JPG/PNG, máx. 5 MB)</label>
                <input type="file" id="imagen-datos" name="imagen" accept="image/png,image/jpeg,image/webp">
            </div>
            <button type="submit" class="btn-enviar">Enviar reporte</button>
        </form>
        <p id="ticket-datos-mensaje" class="admin-mensaje-accion"></p>
    </div>
</div>

<div class="admin-panel">
    <div class="admin-panel-header">
        <h2>Mis reportes</h2>
    </div>
    <table class="admin-tabla">
        <thead>
            <tr><th>Fecha</th><th>Tipo</th><th>Mensaje</th><th>Estado</th><th>Respuesta</th></tr>
        </thead>
        <tbody>
            <?php foreach ($tickets as $t): ?>
                <tr>
                    <td><?= e((new DateTime($t['creado_en']))->format('d/m/Y H:i')) ?></td>
                    <td>
                        <span class="badge-cuota <?= $t['tipo'] === 'datos' ? 'badge-cuota-pendiente' : 'badge-cuota-pagado' ?>">
                            <?= $t['tipo'] === 'datos' ? 'Corrección de datos' : 'Cuota / pago' ?>
                        </span>
                    </td>
                    <td><?= nl2br(e($t['mensaje'])) ?></td>
                    <td>
                        <span class="badge-cuota <?= $t['estado'] === 'resuelto' ? 'badge-cuota-pagado' : 'badge-cuota-pendiente' ?>">
                            <?= $t['estado'] === 'resuelto' ? 'Resuelto' : 'Abierto' ?>
                        </span>
                    </td>
                    <td><?= $t['respuesta'] ? nl2br(e($t['respuesta'])) : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($tickets === []): ?>
                <tr><td colspan="5" class="admin-tabla-vacia">No has enviado ningún reporte todavía.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/layout_fin.php'; ?>
