<?php

require __DIR__ . '/layout_inicio.php';

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>

<div class="admin-detalle-grid">
    <div class="admin-panel">
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
            <h2>Publicar actividad</h2>
        </div>
        <p class="admin-texto-suave">
            Se publica de inmediato en la página pública "Actividades". La imagen de portada es la que se ve en la
            tarjeta; las imágenes de galería (opcionales, máx. 20) se ven al darle "Ver más".
        </p>

        <form id="form-subir-actividad" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <div class="campo">
                <label for="actividad_titulo">Título</label>
                <input type="text" id="actividad_titulo" name="titulo" placeholder="Ej: Celebración del 20 de Julio" required maxlength="150">
            </div>
            <div class="campo">
                <label for="actividad_imagen_portada">Imagen de portada (JPG, PNG o WEBP)</label>
                <input type="file" id="actividad_imagen_portada" name="imagen_portada" accept="image/jpeg,image/png,image/webp" required>
            </div>
            <div class="campo">
                <label for="actividad_descripcion">Descripción</label>
                <textarea id="actividad_descripcion" name="descripcion" rows="3" required maxlength="2000"></textarea>
            </div>
            <div class="campo">
                <label for="actividad_imagenes">Imágenes de la galería (opcional, hasta 20)</label>
                <input type="file" id="actividad_imagenes" name="imagenes[]" accept="image/jpeg,image/png,image/webp" multiple>
            </div>
            <button type="submit" class="btn-enviar">Publicar actividad</button>
        </form>
        <p id="actividad-mensaje" class="admin-mensaje-accion"></p>
    </div>

    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>Documentos publicados</h2>
        </div>

        <div class="table-responsive">
            <table class="admin-tabla">
                <thead>
                    <tr><th>Título</th><th>Subido por</th><th>Fecha</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($documentos as $d): ?>
                        <tr>
                            <td><?= e($d['titulo']) ?></td>
                            <td><?= e($d['subido_por_nombre'] ?? '—') ?></td>
                            <td><?= e((new DateTime($d['creado_en']))->format('d/m/Y H:i')) ?></td>
                            <td class="admin-acciones-cell">
                                <a href="../descargar_documento.php?id=<?= (int) $d['id'] ?>" target="_blank" class="btn-tabla">Ver</a>
                                <button type="button" class="btn-tabla btn-eliminar-documento" data-id="<?= (int) $d['id'] ?>">Eliminar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($documentos === []): ?>
                        <tr><td colspan="4" class="admin-tabla-vacia">Aún no se ha subido ningún documento.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>Actividades publicadas</h2>
        </div>

        <div class="table-responsive">
            <table class="admin-tabla">
                <thead>
                    <tr><th>Título</th><th>Imágenes</th><th>Publicado por</th><th>Fecha</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($actividades as $a): ?>
                        <tr>
                            <td><?= e($a['titulo']) ?></td>
                            <td>Portada + <?= (int) $a['total_imagenes_galeria'] ?> en galería</td>
                            <td><?= e($a['creado_por_nombre'] ?? '—') ?></td>
                            <td><?= e((new DateTime($a['creado_en']))->format('d/m/Y H:i')) ?></td>
                            <td class="admin-acciones-cell">
                                <button type="button" class="btn-tabla btn-eliminar-actividad" data-id="<?= (int) $a['id'] ?>">Eliminar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($actividades === []): ?>
                        <tr><td colspan="5" class="admin-tabla-vacia">Aún no se ha publicado ninguna actividad.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/layout_fin.php'; ?>
