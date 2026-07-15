<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Asociado;
use App\Models\PagoCuota;

class CuentasController
{
    public function index(): void
    {
        $usuario = Auth::requerirSesion();
        $csrf = Csrf::token();

        $asociadoModelo = new Asociado();
        $pagoModelo = new PagoCuota();

        $meses = PagoCuota::obtenerUltimos12Meses();
        $anioMin = end($meses)['anio'];

        $asociados = $asociadoModelo->listarAprobados();
        $pagados = $pagoModelo->obtenerMapaPagosDesde($anioMin);
        $recaudoHistorico = $pagoModelo->recaudoHistoricoTotal();

        $ciclo = PagoCuota::obtenerCicloPago();
        $recaudoMesActual = 0.0;
        $alDia = 0;
        $morosos = 0;
        foreach ($asociados as $a) {
            $pagoActual = isset($pagados[$a['id'] . '-' . $ciclo['anio'] . '-' . $ciclo['mes']]);
            if ($pagoActual) {
                $recaudoMesActual += PagoCuota::MONTO_CUOTA;
                $alDia++;
            } elseif (PagoCuota::estadoCuota(false, $ciclo['dia_hoy']) === 'vencido') {
                $morosos++;
            }
        }

        // Recaudo mes a mes (enero a diciembre) de un año calendario específico.
        $anioActual = (int) date('Y');
        $aniosDisponibles = array_unique(array_merge([$anioActual], $pagoModelo->aniosConDatos()));
        rsort($aniosDisponibles);

        $anioSeleccionado = (int) ($_GET['anio'] ?? $anioActual);
        if ($anioSeleccionado < 2000 || $anioSeleccionado > $anioActual + 1) {
            $anioSeleccionado = $anioActual;
        }

        $recaudoPorMes = $pagoModelo->recaudoPorMesDeAnio($anioSeleccionado);
        $totalAnioSeleccionado = array_sum($recaudoPorMes);

        View::render('admin/cuentas', [
            'usuario' => $usuario,
            'csrf' => $csrf,
            'tituloPagina' => 'Cuentas Totales',
            'paginaActiva' => 'cuentas',
            'meses' => $meses,
            'asociados' => $asociados,
            'pagados' => $pagados,
            'recaudoHistorico' => $recaudoHistorico,
            'ciclo' => $ciclo,
            'recaudoMesActual' => $recaudoMesActual,
            'alDia' => $alDia,
            'morosos' => $morosos,
            'aniosDisponibles' => $aniosDisponibles,
            'anioSeleccionado' => $anioSeleccionado,
            'recaudoPorMes' => $recaudoPorMes,
            'totalAnioSeleccionado' => $totalAnioSeleccionado,
        ]);
    }
}
