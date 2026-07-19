<?php

use App\Core\Auth;
use App\Models\Asociado;

require __DIR__ . '/layout_inicio.php';

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>

<div class="admin-detalle-grid">
    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>Mi cuenta</h2>
        </div>

        <button type="button" id="btn-editar-cuenta" class="btn-tabla" style="margin-bottom:12px;">
            <i class="fas fa-pen"></i> Editar datos
        </button>

        <dl class="admin-datos" id="datos-cuenta-vista">
            <dt>Nombre</dt><dd><?= e($perfil['nombre']) ?></dd>
            <dt>Correo</dt><dd><?= e($perfil['email']) ?></dd>
            <dt>Cédula</dt><dd><?= $perfil['cedula'] ? e($perfil['cedula']) : '—' ?></dd>
            <dt>Fecha de nacimiento</dt>
            <dd><?= $perfil['fecha_nacimiento'] ? e((new DateTime($perfil['fecha_nacimiento']))->format('d/m/Y')) : '—' ?></dd>
            <dt>Teléfono</dt><dd><?= $perfil['telefono'] ? e($perfil['telefono']) : '—' ?></dd>
            <dt>Dirección</dt><dd><?= $perfil['direccion'] ? e($perfil['direccion']) : '—' ?></dd>
            <dt>Fuerza</dt><dd><?= $perfil['fuerza'] ? e($perfil['fuerza']) : '—' ?></dd>
            <dt>Rol</dt><dd><?= Auth::etiquetaRol($usuario['rol']) ?></dd>
            <dt>Fecha de inscripción</dt><dd><?= e((new DateTime($perfil['creado_en']))->format('d/m/Y H:i')) ?></dd>
            <dt>Fecha de afiliación</dt>
            <dd><?= $perfil['fecha_afiliacion'] ? e((new DateTime($perfil['fecha_afiliacion']))->format('d/m/Y')) : '—' ?></dd>
        </dl>

        <form id="form-datos-cuenta" style="display:none;">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

            <div class="campo">
                <label for="cuenta_nombre">Nombre</label>
                <input type="text" id="cuenta_nombre" name="nombre" required value="<?= e($perfil['nombre']) ?>">
            </div>
            <div class="campo">
                <label for="cuenta_email">Correo</label>
                <input type="email" id="cuenta_email" name="email" required value="<?= e($perfil['email']) ?>">
            </div>
            <div class="campo">
                <label for="cuenta_cedula">Cédula</label>
                <input type="text" id="cuenta_cedula" name="cedula" value="<?= $perfil['cedula'] ? e($perfil['cedula']) : '' ?>">
            </div>
            <div class="campo">
                <label for="cuenta_fecha_nacimiento">Fecha de nacimiento</label>
                <input type="date" id="cuenta_fecha_nacimiento" name="fecha_nacimiento" value="<?= $perfil['fecha_nacimiento'] ? e($perfil['fecha_nacimiento']) : '' ?>">
            </div>
            <div class="campo">
                <label for="cuenta_telefono">Teléfono</label>
                <input type="text" id="cuenta_telefono" name="telefono" value="<?= $perfil['telefono'] ? e($perfil['telefono']) : '' ?>">
            </div>
            <div class="campo">
                <label for="cuenta_direccion">Dirección</label>
                <input type="text" id="cuenta_direccion" name="direccion" value="<?= $perfil['direccion'] ? e($perfil['direccion']) : '' ?>">
            </div>
            <div class="campo">
                <label for="cuenta_fuerza">Fuerza (si también eres veterano)</label>
                <select id="cuenta_fuerza" name="fuerza">
                    <option value="">Sin especificar</option>
                    <?php foreach (Asociado::FUERZAS_VALIDAS as $f): ?>
                        <option value="<?= e($f) ?>" <?= $perfil['fuerza'] === $f ? 'selected' : '' ?>><?= e($f) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="cuenta_fecha_afiliacion">Fecha de afiliación</label>
                <input type="date" id="cuenta_fecha_afiliacion" name="fecha_afiliacion" value="<?= $perfil['fecha_afiliacion'] ? e($perfil['fecha_afiliacion']) : '' ?>">
            </div>

            <div class="admin-form-inline-row">
                <button type="submit" class="btn-enviar">Guardar cambios</button>
                <button type="button" id="btn-cancelar-editar-cuenta" class="btn-tabla">Cancelar</button>
            </div>
            <p id="datos-cuenta-mensaje" class="admin-mensaje-accion"></p>
        </form>

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
    </div>

    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>Cambiar mi contraseña</h2>
        </div>

        <form id="form-cambiar-password">
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
        <p id="password-mensaje" class="admin-mensaje-accion"></p>
    </div>
</div>

<?php require __DIR__ . '/layout_fin.php'; ?>
