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
            <dt>Fecha de afiliación</dt>
            <dd>
                <?= e((new DateTime(PagoCuota::fechaBaseCuota($asociado)))->format('d/m/Y')) ?>
                <?php if (!$asociado['fecha_afiliacion']): ?>
                    <span class="admin-texto-suave">(= fecha de inscripción, aún no se cargó una real)</span>
                <?php endif; ?>
            </dd>
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
                    <option value="inactivo" <?= $asociado['estado'] === 'inactivo' ? 'selected' : '' ?>>Inactivo (se salió de la asociación)</option>
                </select>
                <button type="submit" class="btn-tabla">Guardar</button>
            </div>
            <p id="estado-mensaje" class="admin-mensaje-accion"></p>
        </form>
        <?php endif; ?>

        <?php if ($asociado['estado'] === 'aprobado'): ?>
            <div class="admin-acceso-portal">
                <p class="admin-texto-suave">
                    Acceso al portal del afiliado:
                    <strong><?= $asociado['password_hash'] ? 'Activo' : 'Sin activar' ?></strong>
                    <?= $asociado['ultimo_acceso'] ? ' — último ingreso ' . e((new DateTime($asociado['ultimo_acceso']))->format('d/m/Y H:i')) : '' ?>
                </p>
                <button type="button" class="btn-tabla" id="btn-generar-acceso" data-id="<?= (int) $asociado['id'] ?>">
                    <?= $asociado['password_hash'] ? 'Restablecer contraseña del portal' : 'Enviar acceso al portal' ?>
                </button>
                <p id="acceso-afiliado-mensaje" class="admin-mensaje-accion"></p>
            </div>
        <?php endif; ?>
    </div>

    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>Cuota mensual</h2>
        </div>

        <form id="form-fecha-afiliacion" class="admin-form-inline">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="asociado_id" value="<?= (int) $asociado['id'] ?>">
            <label for="fecha_afiliacion">Fecha real de afiliación (si es distinta a cuando se registró en el sitio)</label>
            <div class="admin-form-inline-row">
                <input type="date" id="fecha_afiliacion" name="fecha_afiliacion"
                    value="<?= $asociado['fecha_afiliacion'] ? e($asociado['fecha_afiliacion']) : '' ?>">
                <button type="submit" class="btn-tabla">Guardar</button>
            </div>
            <p class="admin-texto-suave">
                Se usa para calcular desde cuándo debe cuota. Si la dejas vacía, se usa su fecha de registro
                (<?= e((new DateTime($asociado['creado_en']))->format('d/m/Y')) ?>).
            </p>
            <p id="fecha-afiliacion-mensaje" class="admin-mensaje-accion"></p>
        </form>

        <p class="admin-total-historico">
            Total aportado: <strong><?= PagoCuota::formatoPesos($totalPagadoHistorico) ?></strong>
            <span class="admin-texto-suave">(<?= count($pagos) ?> cuota<?= count($pagos) === 1 ? '' : 's' ?> pagada<?= count($pagos) === 1 ? '' : 's' ?>)</span>
            <br>
            Total que debe: <strong<?= $totalDebe > 0 ? ' style="color:var(--color-rojo-patrio);"' : '' ?>><?= PagoCuota::formatoPesos($totalDebe) ?></strong>
            <span class="admin-texto-suave">(<?= $mesesDebe ?> cuota<?= $mesesDebe === 1 ? '' : 's' ?> pendiente<?= $mesesDebe === 1 ? '' : 's' ?> desde que se afilió)</span>
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
