<?php

use App\Models\PagoCuota;

require __DIR__ . '/layout_inicio.php';
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
            <span class="stat-etiqueta">Cuotas pagadas (<?= PagoCuota::nombreMes($ciclo['mes']) ?>)</span>
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
            <span class="stat-valor"><?= PagoCuota::formatoPesos($recaudoMes) ?></span>
            <span class="stat-etiqueta">Recaudo de <?= PagoCuota::nombreMes($ciclo['mes']) ?></span>
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
                    <th>Cuota (<?= PagoCuota::nombreMes($ciclo['mes']) ?>)</th>
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
                            <?= $a['estado'] === 'aprobado' ? (int) $a['cuotas_pagadas_12'] . ' / ' . count($a['meses_12']) : '—' ?>
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

<?php require __DIR__ . '/partials/modal_cuotas.php'; ?>

<?php require __DIR__ . '/layout_fin.php'; ?>
