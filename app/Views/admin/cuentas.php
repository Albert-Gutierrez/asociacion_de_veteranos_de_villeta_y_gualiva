<?php

use App\Models\PagoCuota;

require __DIR__ . '/layout_inicio.php';

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>

<div class="admin-stats-grid">
    <div class="stat-card">
        <div class="stat-icon icon-azul"><i class="fas fa-coins"></i></div>
        <div>
            <span class="stat-valor"><?= PagoCuota::formatoPesos($recaudoHistorico) ?></span>
            <span class="stat-etiqueta">Recaudo histórico total</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-verde"><i class="fas fa-sack-dollar"></i></div>
        <div>
            <span class="stat-valor"><?= PagoCuota::formatoPesos($recaudoMesActual) ?></span>
            <span class="stat-etiqueta">Recaudo de <?= PagoCuota::nombreMes($ciclo['mes']) ?></span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-verde"><i class="fas fa-circle-check"></i></div>
        <div>
            <span class="stat-valor"><?= $alDia ?></span>
            <span class="stat-etiqueta">Al día este ciclo</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-rojo"><i class="fas fa-circle-exclamation"></i></div>
        <div>
            <span class="stat-valor"><?= $morosos ?></span>
            <span class="stat-etiqueta">Morosos este ciclo</span>
        </div>
    </div>
</div>

<div class="admin-panel">
    <div class="admin-panel-header">
        <h2>Recaudo mensual de <?= $anioSeleccionado ?></h2>
        <form method="GET" class="admin-filtros">
            <select name="anio" onchange="this.form.submit()">
                <?php foreach ($aniosDisponibles as $a): ?>
                    <option value="<?= $a ?>" <?= $a === $anioSeleccionado ? 'selected' : '' ?>><?= $a ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="table-responsive">
        <table class="admin-tabla admin-tabla-matriz">
            <thead>
                <tr>
                    <?php for ($mes = 1; $mes <= 12; $mes++): ?>
                        <th><?= substr(PagoCuota::nombreMes($mes), 0, 3) ?></th>
                    <?php endfor; ?>
                    <th>Total <?= $anioSeleccionado ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <?php for ($mes = 1; $mes <= 12; $mes++): ?>
                        <td class="<?= $recaudoPorMes[$mes] > 0 ? 'celda-recaudo-con-datos' : '' ?>">
                            <?= PagoCuota::formatoPesos($recaudoPorMes[$mes]) ?>
                        </td>
                    <?php endfor; ?>
                    <td><strong><?= PagoCuota::formatoPesos($totalAnioSeleccionado) ?></strong></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="admin-panel">
    <div class="admin-panel-header">
        <h2>Cuotas mes a mes (últimos 12 meses)</h2>
        <div class="admin-filtros">
            <select id="reporte-general-anio">
                <?php foreach ($aniosDisponibles as $a): ?>
                    <option value="<?= $a ?>" <?= $a === $anioSeleccionado ? 'selected' : '' ?>><?= $a ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" id="btn-reporte-general" class="btn-tabla">
                <i class="fas fa-file-pdf"></i> Descargar PDF general
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="admin-tabla admin-tabla-matriz">
            <thead>
                <tr>
                    <th>Asociado</th>
                    <?php foreach ($meses as $m): ?>
                        <th><?= substr(PagoCuota::nombreMes($m['mes']), 0, 3) ?> <?= $m['anio'] ?></th>
                    <?php endforeach; ?>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($asociados as $a): ?>
                    <?php $primerMes = PagoCuota::primerMesElegible(PagoCuota::fechaBaseCuota($a)); ?>
                    <tr>
                        <td>
                            <a href="asociado.php?id=<?= (int) $a['id'] ?>"><?= e($a['nombres'] . ' ' . $a['apellidos']) ?></a>
                        </td>
                        <?php foreach ($meses as $m): ?>
                            <?php $elegible = PagoCuota::mesEsElegible($m['anio'], $m['mes'], $primerMes); ?>
                            <?php $pago = $elegible && isset($pagados[$a['id'] . '-' . $m['anio'] . '-' . $m['mes']]); ?>
                            <td class="celda-cuota">
                                <?php if (!$elegible): ?>
                                    <i class="fas fa-minus celda-no-aplica" title="Aún no era asociado"></i>
                                <?php elseif ($pago): ?>
                                    <i class="fas fa-circle-check celda-pagado" title="Pagó"></i>
                                <?php else: ?>
                                    <i class="fas fa-circle-xmark celda-moroso" title="No pagó"></i>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <td>
                            <?php
                            $mesesAsociado = [];
                            foreach ($meses as $m) {
                                if (!PagoCuota::mesEsElegible($m['anio'], $m['mes'], $primerMes)) {
                                    continue;
                                }
                                $mesesAsociado[] = [
                                    'anio' => $m['anio'],
                                    'mes' => $m['mes'],
                                    'label' => PagoCuota::nombreMes($m['mes']) . ' ' . $m['anio'],
                                    'pagado' => isset($pagados[$a['id'] . '-' . $m['anio'] . '-' . $m['mes']]),
                                ];
                            }
                            ?>
                            <button type="button" class="btn-tabla btn-ver-cuotas" title="Gestionar cuotas mes a mes"
                                data-id="<?= (int) $a['id'] ?>"
                                data-nombre="<?= e($a['nombres'] . ' ' . $a['apellidos']) ?>"
                                data-meses="<?= e(json_encode($mesesAsociado)) ?>">
                                <i class="fas fa-eye"></i> Gestionar
                            </button>
                            <button type="button" class="btn-tabla btn-descargar-cuenta" title="Descargar reporte de pagos en PDF"
                                data-id="<?= (int) $a['id'] ?>"
                                data-nombre="<?= e($a['nombres'] . ' ' . $a['apellidos']) ?>"
                                data-anio-min="<?= (int) $primerMes['anio'] ?>">
                                <i class="fas fa-file-pdf"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($asociados === []): ?>
                    <tr><td colspan="<?= count($meses) + 2 ?>" class="admin-tabla-vacia">Aún no hay asociados aprobados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/partials/modal_cuotas.php'; ?>
<?php require __DIR__ . '/partials/modal_descarga_cuenta.php'; ?>

<?php require __DIR__ . '/layout_fin.php'; ?>
