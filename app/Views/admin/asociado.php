<?php

use App\Core\Auth;
use App\Models\PagoCuota;

require __DIR__ . '/layout_inicio.php';

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>

<a href="dashboard.php" class="admin-volver"><i class="fas fa-arrow-left"></i> Volver al dashboard</a>

<div class="admin-detalle-grid">
    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2><?= e($asociado['nombres'] . ' ' . $asociado['apellidos']) ?></h2>
            <span class="badge-estado badge-<?= e($asociado['estado']) ?>"><?= ucfirst(e($asociado['estado'])) ?></span>
        </div>

        <dl class="admin-datos">
            <dt>Cédula</dt><dd><?= e($asociado['cedula']) ?></dd>
            <dt>Fecha de nacimiento</dt>
            <dd><?= $asociado['fecha_nacimiento'] ? e((new DateTime($asociado['fecha_nacimiento']))->format('d/m/Y')) : '—' ?></dd>
            <dt>Teléfono</dt><dd><?= e($asociado['telefono']) ?></dd>
            <dt>Email</dt><dd><?= e($asociado['email']) ?></dd>
            <dt>Dirección</dt><dd><?= $asociado['direccion'] ? e($asociado['direccion']) : '—' ?></dd>
            <dt>Fuerza</dt><dd><?= e($asociado['fuerza']) ?></dd>
            <dt>Mensaje</dt><dd><?= $asociado['mensaje'] ? nl2br(e($asociado['mensaje'])) : '—' ?></dd>
            <dt>Fecha de inscripción</dt><dd><?= e((new DateTime($asociado['creado_en']))->format('d/m/Y H:i')) ?></dd>
        </dl>

        <?php if (Auth::puedeGestionarSolicitudes()): ?>
        <form id="form-estado" class="admin-form-inline">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="asociado_id" value="<?= (int) $asociado['id'] ?>">
            <label for="estado">Cambiar estado de la solicitud</label>
            <div class="admin-form-inline-row">
                <select id="estado" name="estado">
                    <option value="pendiente" <?= $asociado['estado'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                    <option value="aprobado" <?= $asociado['estado'] === 'aprobado' ? 'selected' : '' ?>>Aprobado</option>
                    <option value="rechazado" <?= $asociado['estado'] === 'rechazado' ? 'selected' : '' ?>>Rechazado</option>
                </select>
                <button type="submit" class="btn-tabla">Guardar</button>
            </div>
            <p id="estado-mensaje" class="admin-mensaje-accion"></p>
        </form>
        <?php endif; ?>
    </div>

    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>Cuota mensual</h2>
        </div>

        <p class="admin-total-historico">
            Total aportado desde su inscripción: <strong><?= PagoCuota::formatoPesos($totalPagadoHistorico) ?></strong>
            <span class="admin-texto-suave">(<?= count($pagos) ?> cuota<?= count($pagos) === 1 ? '' : 's' ?> pagada<?= count($pagos) === 1 ? '' : 's' ?>)</span>
        </p>

        <?php if ($asociado['estado'] !== 'aprobado'): ?>
            <p class="admin-texto-suave">Solo los asociados aprobados quedan sujetos al cobro de la cuota mensual.</p>
        <?php else: ?>
            <p>Ciclo vigente: <strong><?= PagoCuota::nombreMes($ciclo['mes']) ?> <?= $ciclo['anio'] ?></strong> — <?= PagoCuota::formatoPesos(PagoCuota::MONTO_CUOTA) ?></p>

            <?php if ($yaPagoCicloActual): ?>
                <p class="badge-cuota badge-cuota-pagado">Ya pagó la cuota de este ciclo</p>
            <?php else: ?>
                <form id="form-pago">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                    <input type="hidden" name="asociado_id" value="<?= (int) $asociado['id'] ?>">
                    <input type="hidden" name="anio" value="<?= $ciclo['anio'] ?>">
                    <input type="hidden" name="mes" value="<?= $ciclo['mes'] ?>">
                    <button type="submit" class="btn-enviar">Marcar cuota de <?= PagoCuota::nombreMes($ciclo['mes']) ?> como pagada</button>
                </form>
                <p id="pago-mensaje" class="admin-mensaje-accion"></p>
            <?php endif; ?>
        <?php endif; ?>

        <h3 class="admin-subtitulo">Historial de pagos</h3>
        <table class="admin-tabla">
            <thead>
                <tr><th>Mes</th><th>Fecha de pago</th><th>Monto</th><th>Registrado por</th><th>Estado</th></tr>
            </thead>
            <tbody>
                <?php foreach ($historialMeses as $h): ?>
                    <?php $p = $h['pago']; ?>
                    <tr class="<?= $p ? 'fila-cuota-pagado' : 'fila-cuota-moroso' ?>">
                        <td><?= PagoCuota::nombreMes($h['mes']) ?> <?= $h['anio'] ?></td>
                        <td><?= $p ? e((new DateTime($p['fecha_pago']))->format('d/m/Y')) : '—' ?></td>
                        <td><?= $p ? PagoCuota::formatoPesos((float) $p['monto']) : '—' ?></td>
                        <td><?= $p && $p['registrado_por_nombre'] ? e($p['registrado_por_nombre']) : '—' ?></td>
                        <td>
                            <?php if ($p): ?>
                                <span class="badge-cuota badge-cuota-pagado">Pagó</span>
                            <?php else: ?>
                                <span class="badge-cuota badge-cuota-vencido">No pagó</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($historialMeses === []): ?>
                    <tr><td colspan="5" class="admin-tabla-vacia">Sin historial de cuotas todavía.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/layout_fin.php'; ?>
