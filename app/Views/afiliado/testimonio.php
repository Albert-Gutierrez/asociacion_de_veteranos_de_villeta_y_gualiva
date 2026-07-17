<?php

require __DIR__ . '/layout_inicio.php';

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

$etiquetasEstado = [
    'pendiente' => ['texto' => 'Pendiente de revisión', 'clase' => 'badge-cuota-pendiente'],
    'aprobado' => ['texto' => 'Publicado en el sitio', 'clase' => 'badge-cuota-pagado'],
    'rechazado' => ['texto' => 'No fue aprobado', 'clase' => 'badge-cuota-vencido'],
];
?>

<div class="admin-panel admin-panel-angosto">
    <div class="admin-panel-header">
        <h2>Mi testimonio</h2>
        <?php if ($testimonio): ?>
            <span class="badge-cuota <?= e($etiquetasEstado[$testimonio['estado']]['clase']) ?>">
                <?= e($etiquetasEstado[$testimonio['estado']]['texto']) ?>
            </span>
        <?php endif; ?>
    </div>

    <p class="admin-texto-suave">
        Cuéntanos en pocas palabras qué significa para ti ser parte de ASOVEGU. Si un administrador lo aprueba,
        aparecerá en la sección "¿Qué opinan nuestros asociados?" del sitio público. Cada vez que lo edites,
        vuelve a quedar pendiente de revisión.
    </p>

    <?php if ($testimonio && $testimonio['foto_ruta']): ?>
        <img src="../uploads/<?= e($testimonio['foto_ruta']) ?>" alt="Tu foto" class="admin-foto-perfil-preview" style="margin-bottom:14px;">
    <?php endif; ?>

    <form id="form-testimonio" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <div class="campo">
            <label for="mensaje">Tu testimonio (máx. 500 caracteres)</label>
            <textarea id="mensaje" name="mensaje" rows="4" maxlength="500" required><?= $testimonio ? e($testimonio['mensaje']) : '' ?></textarea>
        </div>
        <div class="campo">
            <label for="foto">Tu foto (opcional, JPG/PNG/WEBP, máx. 5 MB)</label>
            <input type="file" id="foto" name="foto" accept="image/png,image/jpeg,image/webp">
        </div>
        <button type="submit" class="btn-enviar"><?= $testimonio ? 'Actualizar testimonio' : 'Enviar testimonio' ?></button>
    </form>
    <p id="testimonio-mensaje" class="admin-mensaje-accion"></p>
</div>

<?php require __DIR__ . '/layout_fin.php'; ?>
