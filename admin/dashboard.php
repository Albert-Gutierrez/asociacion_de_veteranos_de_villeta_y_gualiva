<?php

declare(strict_types=1);

require_once __DIR__ . '/incluye/auth.php';
require_once __DIR__ . '/incluye/csrf.php';
require_once __DIR__ . '/incluye/cuotas.php';

$usuario = requerirSesion();
$csrf = tokenCsrf();

$ciclo = obtenerCicloPago();
$diaHoy = $ciclo['dia_hoy'];
$meses12 = obtenerUltimos12Meses();
$anioMin = end($meses12)['anio'];

$pdo = obtenerConexionBD();
$stmt = $pdo->prepare(
    'SELECT a.*, p.fecha_pago AS pago_fecha
     FROM asociados a
     LEFT JOIN pagos_cuota p ON p.asociado_id = a.id AND p.anio = :anio AND p.mes = :mes
     ORDER BY a.creado_en DESC'
);
$stmt->execute(['anio' => $ciclo['anio'], 'mes' => $ciclo['mes']]);
$asociados = $stmt->fetchAll();

$stmtPagos12 = $pdo->prepare('SELECT asociado_id, anio, mes FROM pagos_cuota WHERE anio >= :anio_min');
$stmtPagos12->execute(['anio_min' => $anioMin]);
$pagados12 = [];
foreach ($stmtPagos12->fetchAll() as $p) {
    $pagados12[$p['asociado_id'] . '-' . $p['anio'] . '-' . $p['mes']] = true;
}

$totalAprobados = 0;
$totalPendientesAprobacion = 0;
$cuotasPagadas = 0;
$cuotasVencidas = 0;

foreach ($asociados as &$a) {
    if ($a['estado'] === 'aprobado') {
        $totalAprobados++;
        $a['cuota_estado'] = estadoCuota($a['pago_fecha'] !== null, $diaHoy);
        if ($a['cuota_estado'] === 'pagado') {
            $cuotasPagadas++;
        } elseif ($a['cuota_estado'] === 'vencido') {
            $cuotasVencidas++;
        }

        $mesesAsociado = [];
        $contadorPagados = 0;
        foreach ($meses12 as $m) {
            $pagado = isset($pagados12[$a['id'] . '-' . $m['anio'] . '-' . $m['mes']]);
            if ($pagado) {
                $contadorPagados++;
            }
            $mesesAsociado[] = [
                'anio' => $m['anio'],
                'mes' => $m['mes'],
                'label' => nombreMes($m['mes']) . ' ' . $m['anio'],
                'pagado' => $pagado,
            ];
        }
        $a['meses_12'] = $mesesAsociado;
        $a['cuotas_pagadas_12'] = $contadorPagados;
    } elseif ($a['estado'] === 'pendiente') {
        $totalPendientesAprobacion++;
        $a['cuota_estado'] = 'no_aplica';
    } else {
        $a['cuota_estado'] = 'no_aplica';
    }
}
unset($a);

$recaudoMes = $cuotasPagadas * MONTO_CUOTA;

$tituloPagina = 'Dashboard';
$paginaActiva = 'dashboard';
require __DIR__ . '/incluye/layout_inicio.php';
?>

<div class="admin-stats-grid">
    <div class="stat-card">
        <div class="stat-icon icon-verde"><i class="fas fa-user-check"></i></div>
        <div>
            <span class="stat-valor"><?= $totalAprobados ?></span>
            <span class="stat-etiqueta">Asociados aprobados</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-dorado"><i class="fas fa-hourglass-half"></i></div>
        <div>
            <span class="stat-valor"><?= $totalPendientesAprobacion ?></span>
            <span class="stat-etiqueta">Pendientes de aprobación</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-verde"><i class="fas fa-circle-check"></i></div>
        <div>
            <span class="stat-valor"><?= $cuotasPagadas ?></span>
            <span class="stat-etiqueta">Cuotas pagadas (<?= nombreMes($ciclo['mes']) ?>)</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-rojo"><i class="fas fa-circle-exclamation"></i></div>
        <div>
            <span class="stat-valor"><?= $cuotasVencidas ?></span>
            <span class="stat-etiqueta">Cuotas vencidas</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-azul"><i class="fas fa-sack-dollar"></i></div>
        <div>
            <span class="stat-valor"><?= formatoPesos($recaudoMes) ?></span>
            <span class="stat-etiqueta">Recaudo de <?= nombreMes($ciclo['mes']) ?></span>
        </div>
    </div>
</div>

<div class="admin-panel">
    <div class="admin-panel-header">
        <h2>Asociados</h2>
        <div class="admin-filtros">
            <input type="text" id="filtro-busqueda" placeholder="Buscar por nombre o cédula...">
            <select id="filtro-estado">
                <option value="">Todos los estados</option>
                <option value="pendiente">Pendientes</option>
                <option value="aprobado">Aprobados</option>
                <option value="rechazado">Rechazados</option>
            </select>
            <select id="filtro-cuota">
                <option value="">Toda cuota</option>
                <option value="pagado">Pagado</option>
                <option value="pendiente">Pendiente</option>
                <option value="vencido">Vencido</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="admin-tabla" id="tabla-asociados">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Cédula</th>
                    <th>Teléfono</th>
                    <th>Fecha de inscripción</th>
                    <th>Estado</th>
                    <th>Cuotas pagadas</th>
                    <th>Cuota (<?= nombreMes($ciclo['mes']) ?>)</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($asociados as $a): ?>
                    <tr data-estado="<?= htmlspecialchars($a['estado'], ENT_QUOTES, 'UTF-8') ?>"
                        data-cuota="<?= htmlspecialchars($a['cuota_estado'], ENT_QUOTES, 'UTF-8') ?>"
                        data-busqueda="<?= htmlspecialchars(mb_strtolower($a['nombres'] . ' ' . $a['apellidos'] . ' ' . $a['cedula']), ENT_QUOTES, 'UTF-8') ?>">
                        <td>
                            <a href="asociado.php?id=<?= (int) $a['id'] ?>">
                                <?= htmlspecialchars($a['nombres'] . ' ' . $a['apellidos'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($a['cedula'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($a['telefono'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((new DateTime($a['creado_en']))->format('d/m/Y'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="badge-estado badge-<?= htmlspecialchars($a['estado'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= ucfirst(htmlspecialchars($a['estado'], ENT_QUOTES, 'UTF-8')) ?>
                            </span>
                        </td>
                        <td>
                            <?= $a['estado'] === 'aprobado' ? (int) $a['cuotas_pagadas_12'] . ' / 12' : '—' ?>
                        </td>
                        <td>
                            <?php if ($a['cuota_estado'] === 'no_aplica'): ?>
                                <span class="badge-cuota badge-cuota-na">No aplica</span>
                            <?php elseif ($a['cuota_estado'] === 'pagado'): ?>
                                <span class="badge-cuota badge-cuota-pagado">Pagó</span>
                            <?php elseif ($a['cuota_estado'] === 'pendiente'): ?>
                                <span class="badge-cuota badge-cuota-pendiente">Dentro del plazo</span>
                            <?php else: ?>
                                <span class="badge-cuota badge-cuota-vencido">Vencido</span>
                            <?php endif; ?>
                        </td>
                        <td class="admin-acciones-cell">
                            <a href="asociado.php?id=<?= (int) $a['id'] ?>" class="btn-tabla">Ver</a>
                            <?php if ($a['estado'] === 'aprobado'): ?>
                                <button type="button" class="btn-tabla btn-ver-cuotas" title="Ver cuotas mes a mes"
                                    data-id="<?= (int) $a['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($a['nombres'] . ' ' . $a['apellidos'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-meses="<?= htmlspecialchars(json_encode($a['meses_12']), ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($asociados === []): ?>
                    <tr><td colspan="8" class="admin-tabla-vacia">Aún no hay asociados registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/incluye/modal_cuotas.php'; ?>

<?php require __DIR__ . '/incluye/layout_fin.php'; ?>
