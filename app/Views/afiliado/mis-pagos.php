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
            <h2>Últimos 12 meses</h2>
        </div>
        <p class="admin-texto-suave">
            <span class="cuota-mes-chip cuota-mes-pagado cuota-mes-estatico" style="padding:4px 10px;">Pagado</span>
            <span class="cuota-mes-chip cuota-mes-moroso cuota-mes-estatico" style="padding:4px 10px;">No pagado</span>
            <span class="cuota-mes-chip cuota-mes-na cuota-mes-estatico" style="padding:4px 10px;">No aplica todavía</span>
        </p>

        <div class="cuotas-grid">
            <?php foreach ($mesesGrid as $m): ?>
                <div class="cuota-mes-chip cuota-mes-estatico cuota-mes-<?= $m['estado'] === 'no_aplica' ? 'na' : e($m['estado']) ?>">
                    <?= e($m['label']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>Resumen</h2>
        </div>

        <p class="admin-total-historico">
            Total aportado: <strong><?= PagoCuota::formatoPesos($totalPagadoHistorico) ?></strong>
            <span class="admin-texto-suave">(<?= $totalPagos ?> cuota<?= $totalPagos === 1 ? '' : 's' ?> pagada<?= $totalPagos === 1 ? '' : 's' ?>)</span>
            <br>
            Total que debo: <strong<?= $totalDebe > 0 ? ' style="color:var(--color-rojo-patrio);"' : '' ?>><?= PagoCuota::formatoPesos($totalDebe) ?></strong>
            <span class="admin-texto-suave">(<?= $mesesDebe ?> cuota<?= $mesesDebe === 1 ? '' : 's' ?> pendiente<?= $mesesDebe === 1 ? '' : 's' ?>)</span>
        </p>

        <p class="admin-texto-suave">
            Los meses en gris son anteriores a tu fecha de afiliación y no cuentan en tu deuda.
        </p>
    </div>
</div>

<div class="admin-panel">
    <div class="admin-panel-header">
        <h2>Historial de pagos</h2>
    </div>

    <?php if ($asociado['estado'] === 'aprobado'): ?>
        <p>Ciclo vigente: <strong><?= PagoCuota::nombreMes($ciclo['mes']) ?> <?= $ciclo['anio'] ?></strong> — <?= PagoCuota::formatoPesos(PagoCuota::MONTO_CUOTA) ?></p>
        <?php if ($yaPagoCicloActual): ?>
            <p class="badge-cuota badge-cuota-pagado">Ya pagaste la cuota de este ciclo</p>
        <?php else: ?>
            <p class="badge-cuota badge-cuota-vencido">Aún no se registra el pago de este ciclo</p>
        <?php endif; ?>
    <?php endif; ?>

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

<div class="admin-panel">
    <div class="admin-panel-header">
        <h2>Descargar reporte de pagos</h2>
    </div>
    <p class="admin-texto-suave">
        Genera un PDF con el detalle mes a mes, el total aportado y el total pendiente del período que elijas.
    </p>

    <div class="admin-form-inline-row" style="flex-wrap:wrap; align-items:flex-end; gap:14px;">
        <div class="campo" style="max-width:220px; margin-bottom:0;">
            <label for="reporte-tipo">Período</label>
            <select id="reporte-tipo">
                <option value="anio_actual">Año en curso (<?= (int) $anioActual ?>)</option>
                <option value="anio">Un año específico</option>
                <option value="mes">Un mes específico</option>
                <option value="todo">Desde que me afilié</option>
            </select>
        </div>

        <div class="campo" id="campo-reporte-anio" style="max-width:150px; margin-bottom:0; display:none;">
            <label for="reporte-anio">Año</label>
            <select id="reporte-anio">
                <?php for ($a = (int) $anioActual; $a >= (int) $anioAfiliacion; $a--): ?>
                    <option value="<?= $a ?>"><?= $a ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="campo" id="campo-reporte-mes" style="max-width:170px; margin-bottom:0; display:none;">
            <label for="reporte-mes">Mes</label>
            <select id="reporte-mes">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>"><?= e(PagoCuota::nombreMes($m)) ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <button type="button" id="btn-descargar-reporte" class="btn-enviar">
            <i class="fas fa-file-pdf"></i> Descargar PDF
        </button>
    </div>
</div>

<?php require __DIR__ . '/layout_fin.php'; ?>
