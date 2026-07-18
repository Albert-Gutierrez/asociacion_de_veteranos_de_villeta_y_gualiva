<?php
// Modal compartido para descargar el reporte PDF de pagos de un asociado
// (año específico o todo su historial desde que se afilió).
?>
<div class="modal fade" id="modal-descarga-cuenta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Descargar reporte de <span id="modal-descarga-nombre"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal-descarga-asociado-id">

                <div class="campo">
                    <label for="modal-descarga-tipo">Período</label>
                    <select id="modal-descarga-tipo">
                        <option value="todo">Total desde que se afilió</option>
                        <option value="anio">Un año específico</option>
                    </select>
                </div>

                <div class="campo" id="modal-descarga-campo-anio" style="display:none;">
                    <label for="modal-descarga-anio">Año</label>
                    <select id="modal-descarga-anio"></select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-enviar" id="btn-confirmar-descarga-cuenta">
                    <i class="fas fa-file-pdf"></i> Descargar PDF
                </button>
            </div>
        </div>
    </div>
</div>
