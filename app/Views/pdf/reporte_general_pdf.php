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
            font-size: 9px;
        }

        .encabezado {
            border-bottom: 2px solid #2D4739;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }

        .encabezado h1 {
            font-size: 16px;
            color: #2D4739;
            margin: 0 0 4px;
        }

        .encabezado p {
            margin: 0;
            font-size: 10px;
            color: #555;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #F8F9FA;
            padding: 4px 3px;
            font-size: 8px;
            text-transform: uppercase;
            color: #555;
            border-bottom: 1px solid #E0E0E0;
            text-align: center;
        }

        th.col-nombre {
            text-align: left;
            width: 130px;
        }

        td {
            padding: 4px 3px;
            font-size: 8.5px;
            border-bottom: 1px solid #E0E0E0;
            text-align: center;
        }

        td.col-nombre {
            text-align: left;
        }

        td.pagado {
            background: #DFF3E4;
            color: #2D4739;
            font-weight: bold;
        }

        td.moroso {
            background: #FBE2E1;
            color: #B31E1B;
            font-weight: bold;
        }

        td.na {
            color: #bbb;
        }

        td.col-total-pagado {
            color: #2D4739;
            font-weight: bold;
            white-space: nowrap;
        }

        td.col-total-debe {
            color: #B31E1B;
            font-weight: bold;
            white-space: nowrap;
        }

        .pie {
            margin-top: 18px;
            font-size: 9px;
            color: #888;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="encabezado">
        <h1>ASOVEGU — Reporte general de pagos</h1>
        <p>Año <?= (int) $anio ?> — Generado el <?= e((new DateTime())->format('d/m/Y H:i')) ?></p>
    </div>

    <table>
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
