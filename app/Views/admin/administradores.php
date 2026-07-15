<?php

use App\Core\Auth;

require __DIR__ . '/layout_inicio.php';

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>

<div class="admin-panel">
    <div class="admin-panel-header">
        <h2>Crear cuenta de administrador</h2>
    </div>

    <form id="form-crear-admin" class="admin-form-crear">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="accion" value="crear">

        <div class="campo">
            <label for="nombre">Nombre completo</label>
            <input type="text" id="nombre" name="nombre" required>
        </div>
        <div class="campo">
            <label for="email">Correo</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="campo">
            <label for="telefono">Teléfono (opcional)</label>
            <input type="tel" id="telefono" name="telefono">
        </div>
        <div class="campo">
            <label for="rol">Rol</label>
            <select id="rol" name="rol">
                <option value="administrador">Administrador</option>
                <option value="tesorero">Tesorero</option>
                <option value="super_administrador">Super administrador</option>
            </select>
        </div>
        <button type="submit" class="btn-enviar">Crear cuenta</button>
    </form>
    <p id="crear-admin-mensaje" class="admin-mensaje-accion"></p>
</div>

<div class="admin-panel">
    <div class="admin-panel-header">
        <h2>Cuentas existentes</h2>
    </div>

    <div class="table-responsive">
        <table class="admin-tabla" id="tabla-admins">
            <thead>
                <tr><th>Nombre</th><th>Correo</th><th>Teléfono</th><th>Rol</th><th>Estado</th><th>Último acceso</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $a): ?>
                    <tr data-admin-id="<?= (int) $a['id'] ?>">
                        <td><?= e($a['nombre']) ?></td>
                        <td><?= e($a['email']) ?></td>
                        <td><?= $a['telefono'] ? e($a['telefono']) : '—' ?></td>
                        <td><?= Auth::etiquetaRol($a['rol']) ?></td>
                        <td>
                            <span class="badge-estado <?= (int) $a['activo'] === 1 ? 'badge-aprobado' : 'badge-rechazado' ?>">
                                <?= (int) $a['activo'] === 1 ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td><?= $a['ultimo_acceso'] ? e((new DateTime($a['ultimo_acceso']))->format('d/m/Y H:i')) : 'Nunca' ?></td>
                        <td class="admin-acciones-cell">
                            <?php if ((int) $a['id'] !== $usuario['id']): ?>
                                <button type="button" class="btn-tabla btn-toggle-activo" data-id="<?= (int) $a['id'] ?>" data-activo="<?= (int) $a['activo'] ?>">
                                    <?= (int) $a['activo'] === 1 ? 'Desactivar' : 'Activar' ?>
                                </button>
                                <button type="button" class="btn-tabla btn-resetear" data-id="<?= (int) $a['id'] ?>">Nueva contraseña</button>
                            <?php else: ?>
                                <span class="admin-texto-suave">(tu cuenta)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/layout_fin.php'; ?>
