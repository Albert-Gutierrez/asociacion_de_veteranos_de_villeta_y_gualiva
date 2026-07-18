<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuthAfiliado;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Asociado;
use App\Models\PagoCuota;
use App\Models\Testimonio;
use App\Models\Ticket;

class AfiliadoDashboardController
{
    public function index(): void
    {
        $afiliado = AuthAfiliado::requerirSesion();
        $csrf = Csrf::token();

        $asociado = $this->obtenerAsociadoOConEsRedireccion();
        $pagoModelo = new PagoCuota();

        $pagos = $pagoModelo->historialPorAsociado($asociado['id']);
        $pagosPorMes = $this->indexarPagosPorMes($pagos);

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

        [$mesesDebe, $totalDebe] = $this->calcularDeuda($pagosPorMes, $primerMes);
        $totalPagadoHistorico = array_sum(array_map(fn ($p) => (float) $p['monto'], $pagos));

        View::render('afiliado/dashboard', [
            'afiliado' => $afiliado,
            'csrf' => $csrf,
            'tituloPagina' => 'Mi información',
            'paginaActiva' => 'dashboard',
            'asociado' => $asociado,
            'pagos' => $pagos,
            'totalPagadoHistorico' => $totalPagadoHistorico,
            'ciclo' => $ciclo,
            'yaPagoCicloActual' => $yaPagoCicloActual,
            'historialMeses' => $historialMeses,
            'mesesDebe' => $mesesDebe,
            'totalDebe' => $totalDebe,
        ]);
    }

    /**
     * Pestaña "Soporte": reportar un pago no reflejado (todos los roles de
     * staff lo atienden) o pedir corrección de datos personales (solo
     * administrador/super administrador, ver TicketController).
     */
    public function soporte(): void
    {
        $afiliado = AuthAfiliado::requerirSesion();
        $csrf = Csrf::token();

        $asociado = $this->obtenerAsociadoOConEsRedireccion();
        $ticketModelo = new Ticket();
        $tickets = $ticketModelo->listarPorAsociado($asociado['id']);

        View::render('afiliado/soporte', [
            'afiliado' => $afiliado,
            'asociado' => $asociado,
            'csrf' => $csrf,
            'tituloPagina' => 'Soporte',
            'paginaActiva' => 'soporte',
            'tickets' => $tickets,
        ]);
    }

    /**
     * Pestaña "Mi testimonio": el afiliado escribe/edita el testimonio que
     * aparece (una vez aprobado) en el carrusel público "¿Qué opinan
     * nuestros asociados?" de quienes-somos.html.
     */
    public function testimonio(): void
    {
        $afiliado = AuthAfiliado::requerirSesion();
        $csrf = Csrf::token();

        $asociado = $this->obtenerAsociadoOConEsRedireccion();
        $testimonioModelo = new Testimonio();
        $testimonio = $testimonioModelo->buscarPorAsociado($asociado['id']);

        View::render('afiliado/testimonio', [
            'afiliado' => $afiliado,
            'asociado' => $asociado,
            'csrf' => $csrf,
            'tituloPagina' => 'Mi testimonio',
            'paginaActiva' => 'testimonio',
            'testimonio' => $testimonio,
        ]);
    }

    public function misPagos(): void
    {
        $afiliado = AuthAfiliado::requerirSesion();

        $asociado = $this->obtenerAsociadoOConEsRedireccion();
        $pagoModelo = new PagoCuota();

        $pagos = $pagoModelo->historialPorAsociado($asociado['id']);
        $pagosPorMes = $this->indexarPagosPorMes($pagos);

        $fechaBase = PagoCuota::fechaBaseCuota($asociado);
        $primerMes = PagoCuota::primerMesElegible($fechaBase);

        // A diferencia del historial del dashboard (que omite los meses
        // anteriores a la afiliación), aquí se muestran los 12 siempre, en
        // gris los que todavía no le correspondían, para que se vea el año
        // completo de un vistazo.
        $mesesGrid = [];
        foreach (PagoCuota::obtenerUltimos12Meses() as $m) {
            $elegible = PagoCuota::mesEsElegible($m['anio'], $m['mes'], $primerMes);
            $pagado = $elegible && isset($pagosPorMes[$m['anio'] . '-' . $m['mes']]);
            $mesesGrid[] = [
                'label' => PagoCuota::nombreMes($m['mes']) . ' ' . $m['anio'],
                'estado' => !$elegible ? 'no_aplica' : ($pagado ? 'pagado' : 'moroso'),
            ];
        }

        [$mesesDebe, $totalDebe] = $this->calcularDeuda($pagosPorMes, $primerMes);
        $totalPagadoHistorico = array_sum(array_map(fn ($p) => (float) $p['monto'], $pagos));

        View::render('afiliado/mis-pagos', [
            'afiliado' => $afiliado,
            'asociado' => $asociado,
            'tituloPagina' => 'Mis pagos',
            'paginaActiva' => 'mis-pagos',
            'mesesGrid' => $mesesGrid,
            'totalPagadoHistorico' => $totalPagadoHistorico,
            'totalPagos' => count($pagos),
            'mesesDebe' => $mesesDebe,
            'totalDebe' => $totalDebe,
            'anioAfiliacion' => $primerMes['anio'],
            'anioActual' => (int) date('Y'),
        ]);
    }

    /**
     * Genera un PDF con el historial de pagos del afiliado según el período
     * elegido: un año, un mes puntual, el año en curso, o todo el historial
     * desde que se afilió. Se descarga directo, sin CSRF (es GET, de solo
     * lectura, y solo devuelve datos del propio afiliado autenticado).
     */
    public function descargarReportePagos(): void
    {
        AuthAfiliado::requerirSesion();

        $asociado = $this->obtenerAsociadoOConEsRedireccion();
        $pagoModelo = new PagoCuota();

        $pagos = $pagoModelo->historialPorAsociado($asociado['id']);
        $pagosPorMes = $this->indexarPagosPorMes($pagos);

        $fechaBase = PagoCuota::fechaBaseCuota($asociado);
        $primerMes = PagoCuota::primerMesElegible($fechaBase);
        $anioActual = (int) date('Y');
        $mesActual = (int) date('n');

        $tipo = (string) ($_GET['tipo'] ?? 'anio_actual');

        switch ($tipo) {
            case 'mes':
                $anio = max((int) ($_GET['anio'] ?? $anioActual), $primerMes['anio']);
                $mes = min(max((int) ($_GET['mes'] ?? $mesActual), 1), 12);
                $meses = [['anio' => $anio, 'mes' => $mes]];
                $titulo = 'Mes: ' . PagoCuota::nombreMes($mes) . ' ' . $anio;
                break;

            case 'anio':
                $anio = min(max((int) ($_GET['anio'] ?? $anioActual), $primerMes['anio']), $anioActual);
                $meses = $this->mesesDelAnio($anio, $anioActual, $mesActual);
                $titulo = 'Año ' . $anio;
                break;

            case 'todo':
                $meses = PagoCuota::obtenerMesesDesde($primerMes);
                $titulo = 'Historial completo desde tu afiliación';
                break;

            case 'anio_actual':
            default:
                $meses = $this->mesesDelAnio($anioActual, $anioActual, $mesActual);
                $titulo = 'Año en curso (' . $anioActual . ')';
                break;
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

    /**
     * Meses de un año calendario: todos (1-12) si es un año anterior al
     * actual, o solo hasta el mes en curso si es el año actual (no tiene
     * sentido mostrar como "debe" meses que ni siquiera han llegado).
     */
    private function mesesDelAnio(int $anio, int $anioActual, int $mesActual): array
    {
        $ultimoMes = $anio < $anioActual ? 12 : ($anio === $anioActual ? $mesActual : 0);

        $meses = [];
        for ($mes = 1; $mes <= $ultimoMes; $mes++) {
            $meses[] = ['anio' => $anio, 'mes' => $mes];
        }
        return $meses;
    }

    /**
     * Se llama después de requerirSesion(); usa la sesión ya validada para
     * traer al asociado, y lo manda a cambiar su contraseña si aún la debe.
     */
    private function obtenerAsociadoOConEsRedireccion(): array
    {
        $afiliado = AuthAfiliado::afiliadoActual();

        $asociadoModelo = new Asociado();
        $asociado = $asociadoModelo->buscarPorId($afiliado['id']);
        if (!$asociado) {
            http_response_code(404);
            exit('No se encontró tu información.');
        }

        // Por si entra directo a esta URL sin pasar por el redireccionamiento
        // del login (pestaña vieja, marcador, etc.).
        if ((int) $asociado['debe_cambiar_password'] === 1) {
            header('Location: cambiar-password.php');
            exit;
        }

        return $asociado;
    }

    private function indexarPagosPorMes(array $pagos): array
    {
        $pagosPorMes = [];
        foreach ($pagos as $p) {
            $pagosPorMes[$p['anio'] . '-' . $p['mes']] = $p;
        }
        return $pagosPorMes;
    }

    /**
     * @return array{0: int, 1: float} [meses_sin_pagar, total_que_debe]
     */
    private function calcularDeuda(array $pagosPorMes, array $primerMes): array
    {
        $mesesDebe = 0;
        foreach (PagoCuota::obtenerMesesDesde($primerMes) as $m) {
            if (!isset($pagosPorMes[$m['anio'] . '-' . $m['mes']])) {
                $mesesDebe++;
            }
        }
        return [$mesesDebe, $mesesDebe * PagoCuota::MONTO_CUOTA];
    }
}
