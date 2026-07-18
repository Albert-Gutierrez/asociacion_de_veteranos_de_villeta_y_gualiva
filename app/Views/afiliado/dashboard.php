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

        <div class="admin-foto-perfil-editor">
            <?php if ($asociado['foto_ruta']): ?>
                <img src="../img/perfiles/<?= e($asociado['foto_ruta']) ?>" alt="Tu foto de perfil" class="admin-foto-perfil-preview">
            <?php else: ?>
                <i class="fas fa-circle-user admin-foto-perfil-preview-icono"></i>
            <?php endif; ?>

            <form id="form-foto-perfil" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <div class="campo">
                    <label for="foto">Tu foto (opcional, JPG/PNG/WEBP, máx. 3 MB)</label>
                    <input type="file" id="foto" name="foto" accept="image/png,image/jpeg,image/webp" required>
                </div>
                <button type="submit" class="btn-enviar">Subir foto</button>
            </form>
        </div>
        <p id="foto-perfil-mensaje" class="admin-mensaje-accion"></p>
        <p class="admin-texto-suave">Esta es la única información que puedes cambiar tú mismo desde aquí.</p>

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
            <h2>Cambiar mi contraseña</h2>
        </div>

        <form id="form-cambiar-password-afiliado">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

            <div class="campo">
                <label for="password_actual">Contraseña actual</label>
                <input type="password" id="password_actual" name="password_actual" required>
            </div>
            <div class="campo">
                <label for="password_nueva">Nueva contraseña</label>
                <input type="password" id="password_nueva" name="password_nueva" required minlength="10">
                <span class="admin-texto-suave">Mínimo 10 caracteres, con al menos una mayúscula y un carácter especial. Sin números repetidos como 222.</span>
            </div>
            <div class="campo">
                <label for="password_confirmar">Confirmar nueva contraseña</label>
                <input type="password" id="password_confirmar" name="password_confirmar" required minlength="10">
            </div>
            <button type="submit" class="btn-enviar">Actualizar contraseña</button>
        </form>
        <p id="password-afiliado-mensaje" class="admin-mensaje-accion"></p>
    </div>
</div>

<?php require __DIR__ . '/layout_fin.php'; ?>
