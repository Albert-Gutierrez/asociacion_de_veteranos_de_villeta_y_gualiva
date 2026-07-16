<?php

use App\Core\Auth;

require __DIR__ . '/layout_inicio.php';

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>

<div class="admin-panel admin-panel-angosto">
    <div class="admin-panel-header">
        <h2>Mi cuenta</h2>
    </div>

    <dl class="admin-datos">
        <dt>Nombre</dt><dd><?= e($usuario['nombre']) ?></dd>
        <dt>Correo</dt><dd><?= e($usuario['email']) ?></dd>
        <dt>Rol</dt><dd><?= Auth::etiquetaRol($usuario['rol']) ?></dd>
    </dl>

    <h3 class="admin-subtitulo">Foto de perfil</h3>
    <div class="admin-foto-perfil-editor">
        <?php if ($usuario['foto']): ?>
            <img src="../img/perfiles/<?= e($usuario['foto']) ?>" alt="Tu foto de perfil" class="admin-foto-perfil-preview">
        <?php else: ?>
            <i class="fas fa-circle-user admin-foto-perfil-preview-icono"></i>
        <?php endif; ?>

        <form id="form-foto-perfil" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <div class="campo">
                <label for="foto">Foto (opcional, JPG/PNG/WEBP, máx. 3 MB)</label>
                <input type="file" id="foto" name="foto" accept="image/png,image/jpeg,image/webp" required>
            </div>
            <button type="submit" class="btn-enviar">Subir foto</button>
        </form>
    </div>
    <p id="foto-perfil-mensaje" class="admin-mensaje-accion"></p>

    <h3 class="admin-subtitulo">Cambiar mi contraseña</h3>
    <form id="form-cambiar-password">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

        <div class="campo">
            <label for="password_actual">Contraseña actual</label>
            <input type="password" id="password_actual" name="password_actual" required>
        </div>
        <div class="campo">
            <label for="password_nueva">Nueva contraseña</label>
            <input type="password" id="password_nueva" name="password_nueva" required minlength="8">
        </div>
        <div class="campo">
            <label for="password_confirmar">Confirmar nueva contraseña</label>
            <input type="password" id="password_confirmar" name="password_confirmar" required minlength="8">
        </div>
        <button type="submit" class="btn-enviar">Actualizar contraseña</button>
    </form>
    <p id="password-mensaje" class="admin-mensaje-accion"></p>
</div>

<?php require __DIR__ . '/layout_fin.php'; ?>
