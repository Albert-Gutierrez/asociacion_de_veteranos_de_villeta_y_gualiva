<?php
// Modal compartido para gestionar las cuotas mes a mes de un asociado.
// Requiere que la página que lo incluye ya tenga $csrf definido.
?>
<div class="modal fade" id="modal-cuotas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cuotas de <span id="modal-cuotas-nombre"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal-cuotas-csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" id="modal-cuotas-asociado-id">

                <p class="admin-texto-suave">Haz clic en un mes para marcarlo pagado o moroso.</p>
                <div id="modal-cuotas-grid" class="cuotas-grid"></div>

                <p id="modal-cuotas-mensaje" class="admin-mensaje-accion"></p>
            </div>
        </div>
    </div>
</div>
