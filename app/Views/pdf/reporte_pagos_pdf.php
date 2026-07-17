<?php

use App\Models\PagoCuota;

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1B1B1B;
            font-size: 12px;
        }

        .encabezado {
            border-bottom: 2px solid #2D4739;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }

        .encabezado h1 {
            font-size: 18px;
            color: #2D4739;
            margin: 0 0 4px;
        }

        .encabezado p {
            margin: 0;
            font-size: 12px;
            color: #555;
        }

        .datos-asociado {
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

        table.pagos {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        table.pagos th {
            background: #F8F9FA;
            text-align: left;
            padding: 6px 8px;
            font-size: 11px;
            text-transform: uppercase;
            color: #555;
            border-bottom: 1px solid #E0E0E0;
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

        .resumen {
            border: 1px solid #E0E0E0;
            border-radius: 6px;
            padding: 12px 16px;
            background: #F8F9FA;
        }

        .resumen p {
            margin: 4px 0;
            font-size: 13px;
        }

        .resumen strong {
            font-size: 14px;
        }

        .total-debe {
            color: #B31E1B;
        }

        .pie {
            margin-top: 24px;
            font-size: 10px;
            color: #888;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="encabezado">
        <h1>ASOVEGU — Reporte de pagos</h1>
        <p><?= e($titulo) ?></p>
    </div>

    <table class="datos-asociado">
        <tr>
            <td class="etiqueta">Asociado</td>
            <td><?= e($asociado['nombres'] . ' ' . $asociado['apellidos']) ?></td>
        </tr>
        <tr>
            <td class="etiqueta">Cédula</td>
            <td><?= e($asociado['cedula']) ?></td>
        </tr>
        <tr>
            <td class="etiqueta">Fecha de generación</td>
            <td><?= e((new DateTime())->format('d/m/Y H:i')) ?></td>
        </tr>
    </table>

    <table class="pagos">
        <thead>
            <tr>
                <th>Mes</th>
                <th>Estado</th>
                <th>Fecha de pago</th>
                <th>Monto</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($filas as $f): ?>
                <?php $pago = $f['pago']; ?>
                <tr>
                    <td><?= e(PagoCuota::nombreMes($f['mes'])) ?> <?= (int) $f['anio'] ?></td>
                    <td class="<?= $pago ? 'estado-pagado' : 'estado-moroso' ?>"><?= $pago ? 'Pagó' : 'No pagó' ?></td>
                    <td><?= $pago ? e((new DateTime($pago['fecha_pago']))->format('d/m/Y')) : '—' ?></td>
                    <td><?= $pago ? PagoCuota::formatoPesos((float) $pago['monto']) : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($filas === []): ?>
                <tr><td colspan="4">No hay meses que reportar en el período seleccionado.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="resumen">
        <p>Total aportado en este período: <strong><?= PagoCuota::formatoPesos($totalPagado) ?></strong></p>
        <p class="total-debe">Total adeudado en este período: <strong><?= PagoCuota::formatoPesos($totalDebe) ?></strong>
            (<?= $mesesDebe ?> cuota<?= $mesesDebe === 1 ? '' : 's' ?> pendiente<?= $mesesDebe === 1 ? '' : 's' ?>)</p>
    </div>

    <p class="pie">Asociación de Veteranos de las Fuerzas Militares y de Policía de la Región de Villeta y Gualivá</p>
</body>

</html>
