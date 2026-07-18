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

    /**
     * PDF con todos los asociados aprobados: sus 12 meses del año elegido
     * (pagó/no pagó/no aplica) y sus totales pagado/adeudado ese año.
     */
    public function descargarReporteGeneral(): void
    {
        Auth::requerirSesion();

        $asociadoModelo = new Asociado();
        $pagoModelo = new PagoCuota();

        $anioActual = (int) date('Y');
        $mesActual = (int) date('n');
        $anio = (int) ($_GET['anio'] ?? $anioActual);
        if ($anio < 2000 || $anio > $anioActual) {
            $anio = $anioActual;
        }

        $asociados = $asociadoModelo->listarAprobados();
        $pagados = $pagoModelo->obtenerMapaPagosDesde($anio);

        $filas = [];
        $totalPagadoGeneral = 0.0;
        $totalDebeGeneral = 0.0;
        foreach ($asociados as $a) {
            $primerMes = PagoCuota::primerMesElegible(PagoCuota::fechaBaseCuota($a));

            $mesesEstado = [];
            $mesesPagados = 0;
            $mesesDebe = 0;
            for ($mes = 1; $mes <= 12; $mes++) {
                $futuro = $anio === $anioActual && $mes > $mesActual;
                $elegible = !$futuro && PagoCuota::mesEsElegible($anio, $mes, $primerMes);

                if (!$elegible) {
                    $mesesEstado[] = 'na';
                    continue;
                }

                if (isset($pagados[$a['id'] . '-' . $anio . '-' . $mes])) {
                    $mesesEstado[] = 'pagado';
                    $mesesPagados++;
                } else {
                    $mesesEstado[] = 'moroso';
                    $mesesDebe++;
                }
            }

            $totalPagadoFila = $mesesPagados * PagoCuota::MONTO_CUOTA;
            $totalDebeFila = $mesesDebe * PagoCuota::MONTO_CUOTA;
            $totalPagadoGeneral += $totalPagadoFila;
            $totalDebeGeneral += $totalDebeFila;

            $filas[] = [
                'nombre' => $a['nombres'] . ' ' . $a['apellidos'],
                'cedula' => $a['cedula'],
                'meses' => $mesesEstado,
                'totalPagado' => $totalPagadoFila,
                'totalDebe' => $totalDebeFila,
            ];
        }

        $html = View::renderToString('pdf/reporte_general_pdf', [
            'anio' => $anio,
            'filas' => $filas,
            'totalAsociados' => count($asociados),
            'totalPagadoGeneral' => $totalPagadoGeneral,
            'totalDebeGeneral' => $totalDebeGeneral,
        ]);

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->setPaper('letter', 'landscape');
        $dompdf->loadHtml($html);
        $dompdf->render();
        $dompdf->stream('reporte_general_pagos_' . $anio . '.pdf', ['Attachment' => false]);
        exit;
    }

    /**
     * PDF con el historial de pagos de UN asociado (elegido por el staff
     * desde la tabla de Cuentas Totales), de un año específico o de todo
     * su historial desde que se afilió.
     */
    public function descargarReporteAsociado(): void
    {
        Auth::requerirSesion();

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            exit('Solicitud inválida.');
        }

        $asociadoModelo = new Asociado();
        $asociado = $asociadoModelo->buscarPorId($id);
        if (!$asociado) {
            http_response_code(404);
            exit('Asociado no encontrado.');
        }

        $pagoModelo = new PagoCuota();
        $pagos = $pagoModelo->historialPorAsociado($id);
        $pagosPorMes = [];
        foreach ($pagos as $p) {
            $pagosPorMes[$p['anio'] . '-' . $p['mes']] = $p;
        }

        $fechaBase = PagoCuota::fechaBaseCuota($asociado);
        $primerMes = PagoCuota::primerMesElegible($fechaBase);
        $anioActual = (int) date('Y');
        $mesActual = (int) date('n');

        $tipo = (string) ($_GET['tipo'] ?? 'todo');

        if ($tipo === 'anio') {
            $anio = min(max((int) ($_GET['anio'] ?? $anioActual), $primerMes['anio']), $anioActual);
            $ultimoMes = $anio < $anioActual ? 12 : $mesActual;
            $meses = [];
            for ($mes = 1; $mes <= $ultimoMes; $mes++) {
                $meses[] = ['anio' => $anio, 'mes' => $mes];
            }
            $titulo = 'Año ' . $anio;
        } else {
            $meses = PagoCuota::obtenerMesesDesde($primerMes);
            $titulo = 'Historial completo desde su afiliación';
        }

        $filas = [];
        $totalPagado = 0.0;
        $mesesDebe = 0;
        foreach ($meses as $m) {
            if (!PagoCuota::mesEsElegible($m['anio'], $m['mes'], $primerMes)) {
                continue;
            }
            $pago = $pagosPorMes[$m['anio'] . '-' . $m['mes']] ?? null;
            if ($pago) {
                $totalPagado += (float) $pago['monto'];
            } else {
                $mesesDebe++;
            }
            $filas[] = ['anio' => $m['anio'], 'mes' => $m['mes'], 'pago' => $pago];
        }
        $totalDebe = $mesesDebe * PagoCuota::MONTO_CUOTA;

        $html = View::renderToString('pdf/reporte_pagos_pdf', [
            'asociado' => $asociado,
            'titulo' => $titulo,
            'filas' => $filas,
            'totalPagado' => $totalPagado,
            'totalDebe' => $totalDebe,
            'mesesDebe' => $mesesDebe,
        ]);

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        $nombreArchivo = 'reporte_pagos_' . preg_replace('/[^a-z0-9]+/i', '_', strtolower($asociado['apellidos'] . '_' . $titulo)) . '.pdf';
        $dompdf->stream($nombreArchivo, ['Attachment' => false]);
        exit;
    }
}
