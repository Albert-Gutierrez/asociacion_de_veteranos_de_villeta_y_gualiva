<?php

use App\Models\PagoCuota;

require __DIR__ . '/layout_inicio.php';

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>

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
            <dt>Fecha de afiliación</dt><dd><?= e((new DateTime(PagoCuota::fechaBaseCuota($asociado)))->format('d/m/Y')) ?></dd>
        </dl>
        <p class="admin-texto-suave">
            Esta información es de solo lectura. Si algún dato está incorrecto, avísale a la asociación.
        </p>
    </div>

    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>Mi cuota mensual</h2>
        </div>

        <p class="admin-total-historico">
            Total aportado: <strong><?= PagoCuota::formatoPesos($totalPagadoHistorico) ?></strong>
            <span class="admin-texto-suave">(<?= count($pagos) ?> cuota<?= count($pagos) === 1 ? '' : 's' ?> pagada<?= count($pagos) === 1 ? '' : 's' ?>)</span>
            <br>
            Total que debo: <strong<?= $totalDebe > 0 ? ' style="color:var(--color-rojo-patrio);"' : '' ?>><?= PagoCuota::formatoPesos($totalDebe) ?></strong>
            <span class="admin-texto-suave">(<?= $mesesDebe ?> cuota<?= $mesesDebe === 1 ? '' : 's' ?> pendiente<?= $mesesDebe === 1 ? '' : 's' ?>)</span>
        </p>

        <?php if ($asociado['estado'] === 'aprobado'): ?>
            <p>Ciclo vigente: <strong><?= PagoCuota::nombreMes($ciclo['mes']) ?> <?= $ciclo['anio'] ?></strong> — <?= PagoCuota::formatoPesos(PagoCuota::MONTO_CUOTA) ?></p>
            <?php if ($yaPagoCicloActual): ?>
                <p class="badge-cuota badge-cuota-pagado">Ya pagaste la cuota de este ciclo</p>
            <?php else: ?>
                <p class="badge-cuota badge-cuota-vencido">Aún no se registra el pago de este ciclo</p>
            <?php endif; ?>
        <?php endif; ?>

        <h3 class="admin-subtitulo">Historial de pagos</h3>
        <table class="admin-tabla">
            <thead>
                <tr><th>Mes</th><th>Fecha de pago</th><th>Estado</th></tr>
            </thead>
            <tbody>
                <?php foreach ($historialMeses as $h): ?>
                    <?php $p = $h['pago']; ?>
                    <tr class="<?= $p ? 'fila-cuota-pagado' : 'fila-cuota-moroso' ?>">
                        <td><?= PagoCuota::nombreMes($h['mes']) ?> <?= $h['anio'] ?></td>
                        <td><?= $p ? e((new DateTime($p['fecha_pago']))->format('d/m/Y')) : '—' ?></td>
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
                    <tr><td colspan="3" class="admin-tabla-vacia">Sin historial de cuotas todavía.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/layout_fin.php'; ?>
