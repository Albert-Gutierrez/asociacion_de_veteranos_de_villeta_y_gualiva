<?php

require __DIR__ . '/layout_inicio.php';

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>

<div class="admin-panel admin-panel-angosto">
    <div class="admin-panel-header">
        <h2>Cambiar tu contraseña</h2>
    </div>

    <p class="admin-texto-suave">
        Estás usando la contraseña temporal que te dieron. Por seguridad, elige una nueva antes de continuar.
    </p>

    <form id="form-cambiar-password-afiliado">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

        <div class="campo">
            <label for="password_actual">Contraseña actual (la temporal)</label>
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
        <button type="submit" class="btn-enviar">Guardar y continuar</button>
    </form>
    <p id="password-afiliado-mensaje" class="admin-mensaje-accion"></p>
</div>

<?php require __DIR__ . '/layout_fin.php'; ?>
