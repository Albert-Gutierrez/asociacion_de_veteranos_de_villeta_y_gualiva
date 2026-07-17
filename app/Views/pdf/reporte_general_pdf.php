<?php

use App\Core\PdfAssets;
use App\Models\PagoCuota;

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

$escudo = PdfAssets::escudoDataUri();
$emblemas = PdfAssets::emblemasDataUri();
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
            font-size: 9px;
            margin: 0;
            padding: 22px 24px;
        }

        /* ENCABEZADO ________________________________________________________ */
        .pdf-header {
            position: relative;
            overflow: hidden;
            background-color: #2D4739;
            border-bottom: 4px solid #C8A033;
            margin: -22px -24px 16px -24px;
            padding: 16px 24px;
        }

        .pdf-header-emblema {
            position: absolute;
            top: 4px;
            width: 70px;
            opacity: .15;
        }

        .pdf-header-emblema-1 {
            left: 12%;
        }

        .pdf-header-emblema-2 {
            left: 38%;
        }

        .pdf-header-emblema-3 {
            left: 62%;
        }

        .pdf-header-emblema-4 {
            left: 86%;
        }

        .pdf-header-tabla {
            position: relative;
            width: 100%;
            border-collapse: collapse;
        }

        .pdf-header-escudo-celda {
            width: 50px;
            vertical-align: middle;
        }

        .pdf-header-escudo-celda img {
            width: 42px;
        }

        .pdf-header-texto-celda {
            vertical-align: middle;
            padding-left: 12px;
            color: #ffffff;
        }

        .pdf-header-texto-celda h1 {
            margin: 0;
            font-size: 17px;
            letter-spacing: .5px;
            color: #ffffff;
        }

        .pdf-header-subtitulo1 {
            margin: 2px 0 0;
            font-size: 10.5px;
            font-weight: bold;
            color: #ffffff;
        }

        .pdf-header-fecha-celda {
            vertical-align: middle;
            text-align: right;
            font-size: 8.5px;
            color: rgba(255, 255, 255, .8);
            white-space: nowrap;
        }

        /* TARJETAS DE RESUMEN ________________________________________________ */
        .stats-fila {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .stat-celda {
            width: 33.33%;
            vertical-align: top;
            padding: 3px;
        }

        .stat-card {
            border-left: 4px solid #2D4739;
            background: #F8F9FA;
            padding: 8px 12px;
        }

        .stat-card.debe {
            border-left-color: #B31E1B;
        }

        .stat-card.dorado {
            border-left-color: #C8A033;
        }

        .stat-card .etiqueta {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #666;
            font-weight: bold;
        }

        .stat-card .valor {
            font-size: 15px;
            font-weight: bold;
            color: #2D4739;
            margin-top: 2px;
        }

        .stat-card.debe .valor {
            color: #B31E1B;
        }

        .stat-card.dorado .valor {
            color: #8A6D00;
        }

        /* TABLA GENERAL _______________________________________________________ */
        table.general {
            width: 100%;
            border-collapse: collapse;
        }

        table.general th {
            background: #2D4739;
            color: #ffffff;
            padding: 4px 3px;
            font-size: 8px;
            text-transform: uppercase;
            text-align: center;
        }

        table.general th.col-nombre {
            text-align: left;
            width: 130px;
        }

        table.general td {
            padding: 4px 3px;
            font-size: 8.5px;
            border-bottom: 1px solid #E0E0E0;
            text-align: center;
        }

        table.general td.col-nombre {
            text-align: left;
        }

        table.general td.pagado {
            background: #DFF3E4;
            color: #2D4739;
            font-weight: bold;
        }

        table.general td.moroso {
            background: #FBE2E1;
            color: #B31E1B;
            font-weight: bold;
        }

        table.general td.na {
            color: #bbb;
        }

        table.general td.col-total-pagado {
            color: #2D4739;
            font-weight: bold;
            white-space: nowrap;
        }

        table.general td.col-total-debe {
            color: #B31E1B;
            font-weight: bold;
            white-space: nowrap;
        }

        .pie {
            margin-top: 16px;
            font-size: 8.5px;
            color: #888;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="pdf-header">
        <?php foreach ($emblemas as $i => $emblema): ?>
            <img src="<?= $emblema ?>" class="pdf-header-emblema pdf-header-emblema-<?= $i + 1 ?>" alt="">
        <?php endforeach; ?>
        <table class="pdf-header-tabla">
            <tr>
                <td class="pdf-header-escudo-celda">
                    <?php if ($escudo !== ''): ?>
                        <img src="<?= $escudo ?>" alt="Escudo ASOVEGU">
                    <?php endif; ?>
                </td>
                <td class="pdf-header-texto-celda">
                    <h1>ASOVEGU — Reporte general de pagos</h1>
                    <p class="pdf-header-subtitulo1">Año <?= (int) $anio ?></p>
                </td>
                <td class="pdf-header-fecha-celda">
                    <?= e((new DateTime())->format('d/m/Y H:i')) ?>
                </td>
            </tr>
        </table>
    </div>

    <table class="stats-fila">
        <tr>
            <td class="stat-celda">
                <div class="stat-card">
                    <div class="etiqueta">Asociados incluidos</div>
                    <div class="valor"><?= (int) $totalAsociados ?></div>
                </div>
            </td>
            <td class="stat-celda">
                <div class="stat-card dorado">
                    <div class="etiqueta">Total pagado en <?= (int) $anio ?></div>
                    <div class="valor"><?= PagoCuota::formatoPesos($totalPagadoGeneral) ?></div>
                </div>
            </td>
            <td class="stat-celda">
                <div class="stat-card debe">
                    <div class="etiqueta">Total adeudado en <?= (int) $anio ?></div>
                    <div class="valor"><?= PagoCuota::formatoPesos($totalDebeGeneral) ?></div>
                </div>
            </td>
        </tr>
    </table>

    <table class="general">
        <thead>
            <tr>
                <th class="col-nombre">Asociado</th>
                <th>Cédula</th>
                <?php foreach (range(1, 12) as $mes): ?>
                    <th><?= e(mb_substr(PagoCuota::nombreMes($mes), 0, 3)) ?></th>
                <?php endforeach; ?>
                <th>Total pagado</th>
                <th>Total adeudado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($filas as $f): ?>
                <tr>
                    <td class="col-nombre"><?= e($f['nombre']) ?></td>
                    <td><?= e($f['cedula']) ?></td>
                    <?php foreach ($f['meses'] as $estado): ?>
                        <td class="<?= e($estado) ?>"><?= $estado === 'pagado' ? 'SI' : ($estado === 'moroso' ? 'NO' : '—') ?></td>
                    <?php endforeach; ?>
                    <td class="col-total-pagado"><?= PagoCuota::formatoPesos($f['totalPagado']) ?></td>
                    <td class="col-total-debe"><?= PagoCuota::formatoPesos($f['totalDebe']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($filas === []): ?>
                <tr><td colspan="16">No hay asociados aprobados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <p class="pie">Asociación de Veteranos de las Fuerzas Militares y de Policía de la Región de Villeta y Gualivá — Verde: pagó · Rojo: no pagó · Gris: aún no era asociado</p>
</body>

</html>
