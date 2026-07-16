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

<?php require __DIR__ . '/layout_fin.php'; ?>
