<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuthAfiliado;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Asociado;
use App\Models\PagoCuota;
use App\Models\Ticket;

class AfiliadoDashboardController
{
    public function index(): void
    {
        $afiliado = AuthAfiliado::requerirSesion();
        $csrf = Csrf::token();

        $asociadoModelo = new Asociado();
        $pagoModelo = new PagoCuota();
        $ticketModelo = new Ticket();

        $asociado = $asociadoModelo->buscarPorId($afiliado['id']);
        if (!$asociado) {
            http_response_code(404);
            exit('No se encontró tu información.');
        }

        $pagos = $pagoModelo->historialPorAsociado($asociado['id']);

        $pagosPorMes = [];
        $totalPagadoHistorico = 0.0;
        foreach ($pagos as $p) {
            $pagosPorMes[$p['anio'] . '-' . $p['mes']] = $p;
            $totalPagadoHistorico += (float) $p['monto'];
        }

        $ciclo = PagoCuota::obtenerCicloPago();
        $yaPagoCicloActual = isset($pagosPorMes[$ciclo['anio'] . '-' . $ciclo['mes']]);

        $fechaBase = PagoCuota::fechaBaseCuota($asociado);
        $primerMes = PagoCuota::primerMesElegible($fechaBase);

        $historialMeses = [];
        foreach (PagoCuota::obtenerUltimos12Meses() as $m) {
            if (!PagoCuota::mesEsElegible($m['anio'], $m['mes'], $primerMes)) {
                continue;
            }
            $historialMeses[] = [
                'anio' => $m['anio'],
                'mes' => $m['mes'],
                'pago' => $pagosPorMes[$m['anio'] . '-' . $m['mes']] ?? null,
            ];
        }

        $mesesDebe = 0;
        foreach (PagoCuota::obtenerMesesDesde($primerMes) as $m) {
            if (!isset($pagosPorMes[$m['anio'] . '-' . $m['mes']])) {
                $mesesDebe++;
            }
        }
        $totalDebe = $mesesDebe * PagoCuota::MONTO_CUOTA;

        $tickets = $ticketModelo->listarPorAsociado($asociado['id']);

        View::render('afiliado/dashboard', [
            'afiliado' => $afiliado,
            'csrf' => $csrf,
            'tituloPagina' => 'Mi información',
            'asociado' => $asociado,
            'pagos' => $pagos,
            'totalPagadoHistorico' => $totalPagadoHistorico,
            'ciclo' => $ciclo,
            'yaPagoCicloActual' => $yaPagoCicloActual,
            'historialMeses' => $historialMeses,
            'mesesDebe' => $mesesDebe,
            'totalDebe' => $totalDebe,
            'tickets' => $tickets,
        ]);
    }
}
