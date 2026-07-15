<?php

declare(strict_types=1);

const MONTO_CUOTA = 20000.00;

const NOMBRES_MESES = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
];

/**
 * La cuota de un mes se paga entre el día 27 del mes anterior y el día 1
 * del mes en curso. Devuelve el ciclo (año/mes) que corresponde evaluar hoy:
 * si ya estamos en la ventana de pago anticipado (día >= 27), el ciclo
 * vigente es el del mes siguiente; si no, es el mes en curso.
 */
function obtenerCicloPago(?DateTimeImmutable $hoy = null): array
{
    $hoy = $hoy ?? new DateTimeImmutable('today');
    $dia = (int) $hoy->format('j');

    $referencia = $dia >= 27
        ? $hoy->modify('first day of next month')
        : $hoy->modify('first day of this month');

    return [
        'anio' => (int) $referencia->format('Y'),
        'mes' => (int) $referencia->format('n'),
        'dia_hoy' => $dia,
    ];
}

/**
 * 'pagado'   -> ya existe un registro de pago para el ciclo vigente.
 * 'pendiente'-> aún dentro del plazo (día 27-31 o día 1) y no ha pagado.
 * 'vencido'  -> el plazo ya cerró (día 2-26) y no ha pagado.
 */
function estadoCuota(bool $pagado, int $diaHoy): string
{
    if ($pagado) {
        return 'pagado';
    }
    return ($diaHoy >= 27 || $diaHoy === 1) ? 'pendiente' : 'vencido';
}

function nombreMes(int $mes): string
{
    return NOMBRES_MESES[$mes] ?? (string) $mes;
}

function formatoPesos(float $valor): string
{
    return '$' . number_format($valor, 0, ',', '.');
}
