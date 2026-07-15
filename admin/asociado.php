<?php

declare(strict_types=1);

require_once __DIR__ . '/incluye/auth.php';
require_once __DIR__ . '/incluye/csrf.php';
require_once __DIR__ . '/incluye/cuotas.php';

$usuario = requerirSesion();
$csrf = tokenCsrf();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    exit('Asociado no encontrado.');
}

$pdo = obtenerConexionBD();

$stmt = $pdo->prepare('SELECT * FROM asociados WHERE id = :id');
$stmt->execute(['id' => $id]);
$asociado = $stmt->fetch();

if (!$asociado) {
    http_response_code(404);
    exit('Asociado no encontrado.');
}

$stmtPagos = $pdo->prepare(
    'SELECT p.*, u.nombre AS registrado_por_nombre
     FROM pagos_cuota p
     LEFT JOIN usuarios_admin u ON u.id = p.registrado_por
     WHERE p.asociado_id = :id
     ORDER BY p.anio DESC, p.mes DESC'
);
$stmtPagos->execute(['id' => $id]);
$pagos = $stmtPagos->fetchAll();

$ciclo = obtenerCicloPago();
$yaPagoCicloActual = false;
foreach ($pagos as $p) {
    if ((int) $p['anio'] === $ciclo['anio'] && (int) $p['mes'] === $ciclo['mes']) {
        $yaPagoCicloActual = true;
        break;
    }
}

$tituloPagina = 'Detalle del asociado';
$paginaActiva = 'dashboard';
require __DIR__ . '/incluye/layout_inicio.php';

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
    </div>

    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>Cuota mensual</h2>
        </div>

        <?php if ($asociado['estado'] !== 'aprobado'): ?>
            <p class="admin-texto-suave">Solo los asociados aprobados quedan sujetos al cobro de la cuota mensual.</p>
        <?php else: ?>
            <p>Ciclo vigente: <strong><?= nombreMes($ciclo['mes']) ?> <?= $ciclo['anio'] ?></strong> — <?= formatoPesos(MONTO_CUOTA) ?></p>

            <?php if ($yaPagoCicloActual): ?>
                <p class="badge-cuota badge-cuota-pagado">Ya pagó la cuota de este ciclo</p>
            <?php else: ?>
                <form id="form-pago">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                    <input type="hidden" name="asociado_id" value="<?= (int) $asociado['id'] ?>">
                    <input type="hidden" name="anio" value="<?= $ciclo['anio'] ?>">
                    <input type="hidden" name="mes" value="<?= $ciclo['mes'] ?>">
                    <button type="submit" class="btn-enviar">Marcar cuota de <?= nombreMes($ciclo['mes']) ?> como pagada</button>
                </form>
                <p id="pago-mensaje" class="admin-mensaje-accion"></p>
            <?php endif; ?>
        <?php endif; ?>

        <h3 class="admin-subtitulo">Historial de pagos</h3>
        <table class="admin-tabla">
            <thead>
                <tr><th>Mes</th><th>Fecha de pago</th><th>Monto</th><th>Registrado por</th></tr>
            </thead>
            <tbody>
                <?php foreach ($pagos as $p): ?>
                    <tr>
                        <td><?= nombreMes((int) $p['mes']) ?> <?= (int) $p['anio'] ?></td>
                        <td><?= e((new DateTime($p['fecha_pago']))->format('d/m/Y')) ?></td>
                        <td><?= formatoPesos((float) $p['monto']) ?></td>
                        <td><?= $p['registrado_por_nombre'] ? e($p['registrado_por_nombre']) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($pagos === []): ?>
                    <tr><td colspan="4" class="admin-tabla-vacia">Sin pagos registrados todavía.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/incluye/layout_fin.php'; ?>
