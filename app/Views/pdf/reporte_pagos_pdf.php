<?php

use App\Core\PdfAssets;
use App\Models\PagoCuota;

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

$escudo = PdfAssets::escudoDataUri();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1B1B1B;
            font-size: 12px;
            margin: 0;
            padding: 26px 30px;
        }

        /* ENCABEZADO ________________________________________________________ */
        .pdf-header {
            background-color: #2D4739;
            border-bottom: 4px solid #C8A033;
            margin: -26px -30px 20px -30px;
            padding: 18px 30px;
        }

        .pdf-header-tabla {
            width: 100%;
            border-collapse: collapse;
        }

        .pdf-header-escudo-celda {
            width: 56px;
            vertical-align: middle;
        }

        .pdf-header-escudo-celda img {
            width: 48px;
        }

        .pdf-header-texto-celda {
            vertical-align: middle;
            padding-left: 14px;
            color: #ffffff;
        }

        .pdf-header-texto-celda h1 {
            margin: 0;
            font-size: 20px;
            letter-spacing: .5px;
            color: #ffffff;
        }

        .pdf-header-subtitulo1 {
            margin: 2px 0 0;
            font-size: 12.5px;
            font-weight: bold;
            color: #ffffff;
        }

        .pdf-header-fecha-celda {
            vertical-align: middle;
            text-align: right;
            font-size: 9.5px;
            color: rgba(255, 255, 255, .8);
            white-space: nowrap;
        }

        .pdf-header-fecha-celda strong {
            display: block;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: rgba(255, 255, 255, .65);
            font-weight: normal;
        }

        /* DESCRIPCIÓN _________________________________________________________ */
        .descripcion {
            font-size: 11px;
            color: #555;
            margin: 0 0 14px;
        }

        /* DATOS DEL ASOCIADO _________________________________________________ */
        .datos-asociado {
            width: 100%;
            margin-bottom: 16px;
        }

        .datos-asociado td {
            padding: 3px 0;
            font-size: 12px;
        }

        .datos-asociado td.etiqueta {
            font-weight: bold;
            width: 120px;
            color: #444;
        }

        /* TARJETAS DE RESUMEN ________________________________________________ */
        .stats-fila {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        .stat-celda {
            width: 50%;
            vertical-align: top;
            padding: 4px;
        }

        .stat-card {
            border-left: 4px solid #2D4739;
            background: #F8F9FA;
            padding: 10px 14px;
        }

        .stat-card.debe {
            border-left-color: #B31E1B;
        }

        .stat-card .etiqueta {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #666;
            font-weight: bold;
        }

        .stat-card .valor {
            font-size: 18px;
            font-weight: bold;
            color: #2D4739;
            margin-top: 2px;
        }

        .stat-card.debe .valor {
            color: #B31E1B;
        }

        /* TABLA DE PAGOS ______________________________________________________ */
        table.pagos {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        table.pagos th {
            background: #2D4739;
            color: #ffffff;
            text-align: left;
            padding: 6px 8px;
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        table.pagos td {
            padding: 6px 8px;
            font-size: 12px;
            border-bottom: 1px solid #E0E0E0;
        }

        .estado-pagado {
            color: #2D4739;
            font-weight: bold;
        }

        .estado-moroso {
            color: #B31E1B;
            font-weight: bold;
        }

        .monto-pagado {
            color: #2D4739;
            font-weight: bold;
        }

        .monto-adeudado {
            color: #B31E1B;
            font-weight: bold;
        }

        .pie {
            margin-top: 24px;
            font-size: 9.5px;
            color: #888;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="pdf-header">
        <table class="pdf-header-tabla">
            <tr>
                <td class="pdf-header-escudo-celda">
                    <?php if ($escudo !== ''): ?>
                        <img src="<?= $escudo ?>" alt="Escudo ASOVEGU">
                    <?php endif; ?>
                </td>
                <td class="pdf-header-texto-celda">
                    <h1>ASOVEGU — Reporte de pagos</h1>
                    <p class="pdf-header-subtitulo1"><?= e($titulo) ?></p>
                </td>
                <td class="pdf-header-fecha-celda">
                    <strong>Fecha del reporte</strong>
                    <?= e((new DateTime())->format('d/m/Y H:i')) ?>
                </td>
            </tr>
        </table>
    </div>

    <p class="descripcion">
        Este documento presenta el detalle mes a mes de la cuota mensual del asociado para el período indicado,
        con el monto aportado en cada mes pagado y el monto pendiente en cada mes adeudado.
    </p>

    <table class="datos-asociado">
        <tr>
            <td class="etiqueta">Asociado</td>
            <td><?= e($asociado['nombres'] . ' ' . $asociado['apellidos']) ?></td>
        </tr>
        <tr>
            <td class="etiqueta">Cédula</td>
            <td><?= e($asociado['cedula']) ?></td>
        </tr>
    </table>

    <table class="pagos">
        <thead>
            <tr>
                <th>Mes</th>
                <th>Estado</th>
                <th>Fecha de pago</th>
                <th>Monto pagado</th>
                <th>Monto adeudado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($filas as $f): ?>
                <?php $pago = $f['pago']; ?>
                <tr>
                    <td><?= e(PagoCuota::nombreMes($f['mes'])) ?> <?= (int) $f['anio'] ?></td>
                    <td class="<?= $pago ? 'estado-pagado' : 'estado-moroso' ?>"><?= $pago ? 'Pagó' : 'No pagó' ?></td>
                    <td><?= $pago ? e((new DateTime($pago['fecha_pago']))->format('d/m/Y')) : '—' ?></td>
                    <td class="monto-pagado"><?= $pago ? PagoCuota::formatoPesos((float) $pago['monto']) : '—' ?></td>
                    <td class="monto-adeudado"><?= $pago ? '—' : PagoCuota::formatoPesos(PagoCuota::MONTO_CUOTA) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($filas === []): ?>
                <tr><td colspan="5">No hay meses que reportar en el período seleccionado.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <table class="stats-fila">
        <tr>
            <td class="stat-celda">
                <div class="stat-card">
                    <div class="etiqueta">Total aportado en este período</div>
                    <div class="valor"><?= PagoCuota::formatoPesos($totalPagado) ?></div>
                </div>
            </td>
            <td class="stat-celda">
                <div class="stat-card debe">
                    <div class="etiqueta">Total adeudado (<?= $mesesDebe ?> cuota<?= $mesesDebe === 1 ? '' : 's' ?>)</div>
                    <div class="valor"><?= PagoCuota::formatoPesos($totalDebe) ?></div>
                </div>
            </td>
        </tr>
    </table>

    <p class="pie">Asociación de Veteranos de las Fuerzas Militares y de Policía de la Región de Villeta y Gualivá — Documento generado automáticamente</p>
</body>

</html>
