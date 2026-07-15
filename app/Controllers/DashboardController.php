<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Asociado;
use App\Models\PagoCuota;

class DashboardController
{
    public function index(): void
    {
        $usuario = Auth::requerirSesion();
        $csrf = Csrf::token();

        $ciclo = PagoCuota::obtenerCicloPago();
        $diaHoy = $ciclo['dia_hoy'];
        $meses12 = PagoCuota::obtenerUltimos12Meses();
        $anioMin = end($meses12)['anio'];

        $asociadoModelo = new Asociado();
        $pagoModelo = new PagoCuota();

        $asociados = $asociadoModelo->listarConPagoDelCiclo($ciclo['anio'], $ciclo['mes']);
        $pagados12 = $pagoModelo->obtenerMapaPagosDesde($anioMin);

        $totalAprobados = 0;
        $totalPendientesAprobacion = 0;
        $cuotasPagadas = 0;
        $cuotasVencidas = 0;

        foreach ($asociados as &$a) {
            if ($a['estado'] === 'aprobado') {
                $totalAprobados++;
                $a['cuota_estado'] = PagoCuota::estadoCuota($a['pago_fecha'] !== null, $diaHoy);
                if ($a['cuota_estado'] === 'pagado') {
                    $cuotasPagadas++;
                } elseif ($a['cuota_estado'] === 'vencido') {
                    $cuotasVencidas++;
                }

                // Solo desde su afiliación real hasta hoy (tope de 12 meses para
                // los asociados más antiguos): no tiene sentido pedirle cuota de
                // meses en los que todavía no era asociado.
                $primerMes = PagoCuota::primerMesElegible(PagoCuota::fechaBaseCuota($a));
                $mesesAsociado = [];
                $contadorPagados = 0;
                foreach ($meses12 as $m) {
                    if (!PagoCuota::mesEsElegible($m['anio'], $m['mes'], $primerMes)) {
                        continue;
                    }
                    $pagado = isset($pagados12[$a['id'] . '-' . $m['anio'] . '-' . $m['mes']]);
                    if ($pagado) {
                        $contadorPagados++;
                    }
                    $mesesAsociado[] = [
                        'anio' => $m['anio'],
                        'mes' => $m['mes'],
                        'label' => PagoCuota::nombreMes($m['mes']) . ' ' . $m['anio'],
                        'pagado' => $pagado,
                    ];
                }
                $a['meses_12'] = $mesesAsociado;
                $a['cuotas_pagadas_12'] = $contadorPagados;
            } elseif ($a['estado'] === 'pendiente') {
                $totalPendientesAprobacion++;
                $a['cuota_estado'] = 'no_aplica';
            } else {
                $a['cuota_estado'] = 'no_aplica';
            }
        }
        unset($a);

        $recaudoMes = $cuotasPagadas * PagoCuota::MONTO_CUOTA;

        View::render('admin/dashboard', [
            'usuario' => $usuario,
            'csrf' => $csrf,
            'tituloPagina' => 'Dashboard',
            'paginaActiva' => 'dashboard',
            'ciclo' => $ciclo,
            'asociados' => $asociados,
            'totalAprobados' => $totalAprobados,
            'totalPendientesAprobacion' => $totalPendientesAprobacion,
            'cuotasPagadas' => $cuotasPagadas,
            'cuotasVencidas' => $cuotasVencidas,
            'recaudoMes' => $recaudoMes,
        ]);
    }
}
