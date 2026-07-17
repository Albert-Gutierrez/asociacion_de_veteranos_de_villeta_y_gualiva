<?php

require __DIR__ . '/layout_inicio.php';

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>

<div class="admin-panel admin-panel-angosto">
    <div class="admin-panel-header">
        <h2>Subir documento público</h2>
    </div>
    <p class="admin-texto-suave">
        Se publica de inmediato en la sección "Documentos públicos" de la página Quiénes somos del sitio.
        Formatos permitidos: PDF, JPG, PNG (máx. 10 MB).
    </p>

    <form id="form-subir-documento" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <div class="campo">
            <label for="titulo">Título del documento</label>
            <input type="text" id="titulo" name="titulo" placeholder="Ej: Certificado Cámara de Comercio" required maxlength="150">
        </div>
        <div class="campo">
            <label for="archivo">Archivo (PDF, JPG o PNG)</label>
            <input type="file" id="archivo" name="archivo" accept="application/pdf,image/jpeg,image/png" required>
        </div>
        <button type="submit" class="btn-enviar">Subir documento</button>
    </form>
    <p id="documento-mensaje" class="admin-mensaje-accion"></p>
</div>

<div class="admin-panel">
    <div class="admin-panel-header">
        <h2>Documentos publicados</h2>
    </div>

    <div class="table-responsive">
        <table class="admin-tabla">
            <thead>
                <tr><th>Título</th><th>Archivo</th><th>Subido por</th><th>Fecha</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($documentos as $d): ?>
                    <tr>
                        <td><?= e($d['titulo']) ?></td>
                        <td><a href="../descargar_documento.php?id=<?= (int) $d['id'] ?>" target="_blank" class="btn-tabla">Ver</a></td>
                        <td><?= e($d['subido_por_nombre'] ?? '—') ?></td>
                        <td><?= e((new DateTime($d['creado_en']))->format('d/m/Y H:i')) ?></td>
                        <td>
                            <button type="button" class="btn-tabla btn-eliminar-documento" data-id="<?= (int) $d['id'] ?>">Eliminar</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($documentos === []): ?>
                    <tr><td colspan="5" class="admin-tabla-vacia">Aún no se ha subido ningún documento.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/layout_fin.php'; ?>
