<?php

declare(strict_types=1);

require_once __DIR__ . '/incluye/auth.php';
require_once __DIR__ . '/incluye/csrf.php';
require_once __DIR__ . '/incluye/cuotas.php';

$usuario = requerirSesion();
$csrf = tokenCsrf();

$pdo = obtenerConexionBD();

// Últimos 12 meses, el actual primero.
$meses = [];
$hoy = new DateTimeImmutable('first day of this month');
for ($i = 0; $i < 12; $i++) {
    $ref = $hoy->modify("-{$i} months");
    $meses[] = ['anio' => (int) $ref->format('Y'), 'mes' => (int) $ref->format('n')];
}
$anioMin = end($meses)['anio'];

$asociados = $pdo->query(
    "SELECT id, nombres, apellidos, cedula FROM asociados WHERE estado = 'aprobado' ORDER BY nombres, apellidos"
)->fetchAll();

$stmtPagos = $pdo->prepare(
    'SELECT asociado_id, anio, mes FROM pagos_cuota WHERE anio >= :anio_min'
);
$stmtPagos->execute(['anio_min' => $anioMin]);
$pagosRaw = $stmtPagos->fetchAll();

$pagados = [];
foreach ($pagosRaw as $p) {
    $pagados[$p['asociado_id'] . '-' . $p['anio'] . '-' . $p['mes']] = true;
}

$recaudoHistorico = (float) $pdo->query('SELECT COALESCE(SUM(monto), 0) FROM pagos_cuota')->fetchColumn();

$ciclo = obtenerCicloPago();
$recaudoMesActual = 0.0;
$alDia = 0;
$morosos = 0;
foreach ($asociados as $a) {
    $pagoActual = isset($pagados[$a['id'] . '-' . $ciclo['anio'] . '-' . $ciclo['mes']]);
    if ($pagoActual) {
        $recaudoMesActual += MONTO_CUOTA;
        $alDia++;
    } elseif (estadoCuota(false, $ciclo['dia_hoy']) === 'vencido') {
        $morosos++;
    }
}

$tituloPagina = 'Cuentas Totales';
$paginaActiva = 'cuentas';
require __DIR__ . '/incluye/layout_inicio.php';

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>

<div class="admin-stats-grid">
    <div class="stat-card">
        <div class="stat-icon icon-azul"><i class="fas fa-coins"></i></div>
        <div>
            <span class="stat-valor"><?= formatoPesos($recaudoHistorico) ?></span>
            <span class="stat-etiqueta">Recaudo histórico total</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-verde"><i class="fas fa-sack-dollar"></i></div>
        <div>
            <span class="stat-valor"><?= formatoPesos($recaudoMesActual) ?></span>
            <span class="stat-etiqueta">Recaudo de <?= nombreMes($ciclo['mes']) ?></span>
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
        <h2>Cuotas mes a mes (últimos 12 meses)</h2>
    </div>

    <div class="table-responsive">
        <table class="admin-tabla admin-tabla-matriz">
            <thead>
                <tr>
                    <th>Asociado</th>
                    <?php foreach ($meses as $m): ?>
                        <th><?= substr(nombreMes($m['mes']), 0, 3) ?> <?= $m['anio'] ?></th>
                    <?php endforeach; ?>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($asociados as $a): ?>
                    <tr>
                        <td>
                            <a href="asociado.php?id=<?= (int) $a['id'] ?>"><?= e($a['nombres'] . ' ' . $a['apellidos']) ?></a>
                        </td>
                        <?php foreach ($meses as $m): ?>
                            <?php $pago = isset($pagados[$a['id'] . '-' . $m['anio'] . '-' . $m['mes']]); ?>
                            <td class="celda-cuota">
                                <?php if ($pago): ?>
                                    <i class="fas fa-circle-check celda-pagado" title="Pagó"></i>
                                <?php else: ?>
                                    <i class="fas fa-circle-xmark celda-moroso" title="No pagó"></i>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <td>
                            <button type="button" class="btn-tabla btn-gestionar-pago"
                                data-id="<?= (int) $a['id'] ?>"
                                data-nombre="<?= e($a['nombres'] . ' ' . $a['apellidos']) ?>">
                                Gestionar pagos
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

<!-- Modal para marcar pagado/moroso de un mes puntual -->
<div class="modal fade" id="modal-pago" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gestionar pagos de <span id="modal-pago-nombre"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal-csrf" value="<?= e($csrf) ?>">
                <input type="hidden" id="modal-asociado-id">

                <label for="modal-mes-select">Mes</label>
                <select id="modal-mes-select">
                    <?php foreach ($meses as $m): ?>
                        <option value="<?= $m['anio'] ?>-<?= $m['mes'] ?>">
                            <?= nombreMes($m['mes']) ?> <?= $m['anio'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <p id="modal-pago-mensaje" class="admin-mensaje-accion"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-tabla" id="btn-marcar-moroso">Marcar moroso</button>
                <button type="button" class="btn-enviar" id="btn-marcar-pagado">Marcar pagado</button>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/incluye/layout_fin.php'; ?>
