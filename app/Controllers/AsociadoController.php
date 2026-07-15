<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Asociado;
use App\Models\PagoCuota;
use PDOException;

class AsociadoController
{
    public function show(): void
    {
        $usuario = Auth::requerirSesion();
        $csrf = Csrf::token();

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(404);
            exit('Asociado no encontrado.');
        }

        $asociadoModelo = new Asociado();
        $pagoModelo = new PagoCuota();

        $asociado = $asociadoModelo->buscarPorId($id);
        if (!$asociado) {
            http_response_code(404);
            exit('Asociado no encontrado.');
        }

        $pagos = $pagoModelo->historialPorAsociado($id);

        $pagosPorMes = [];
        $totalPagadoHistorico = 0.0;
        foreach ($pagos as $p) {
            $pagosPorMes[$p['anio'] . '-' . $p['mes']] = $p;
            $totalPagadoHistorico += (float) $p['monto'];
        }

        $ciclo = PagoCuota::obtenerCicloPago();
        $yaPagoCicloActual = isset($pagosPorMes[$ciclo['anio'] . '-' . $ciclo['mes']]);

        // Historial mes a mes (últimos 12 meses, igual que en el dashboard y en
        // Cuentas Totales, reflejando exactamente lo que hay guardado en pagos_cuota).
        $historialMeses = [];
        foreach (PagoCuota::obtenerUltimos12Meses() as $m) {
            $pago = $pagosPorMes[$m['anio'] . '-' . $m['mes']] ?? null;
            $historialMeses[] = [
                'anio' => $m['anio'],
                'mes' => $m['mes'],
                'pago' => $pago,
            ];
        }

        View::render('admin/asociado', [
            'usuario' => $usuario,
            'csrf' => $csrf,
            'tituloPagina' => 'Detalle del asociado',
            'paginaActiva' => 'dashboard',
            'asociado' => $asociado,
            'pagos' => $pagos,
            'totalPagadoHistorico' => $totalPagadoHistorico,
            'ciclo' => $ciclo,
            'yaPagoCicloActual' => $yaPagoCicloActual,
            'historialMeses' => $historialMeses,
        ]);
    }

    public function actualizarEstado(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        ini_set('display_errors', '0');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responder(405, false, 'Método no permitido.');
        }

        Auth::requerirRolesApi(['administrador', 'super_administrador']);

        $entrada = json_decode(file_get_contents('php://input'), true);
        if (!is_array($entrada)) {
            $this->responder(400, false, 'Solicitud inválida.');
        }

        Csrf::requerirApi($entrada['csrf_token'] ?? null);

        $asociadoId = (int) ($entrada['asociado_id'] ?? 0);
        $estado = (string) ($entrada['estado'] ?? '');

        $estadosValidos = ['pendiente', 'aprobado', 'rechazado'];
        if ($asociadoId <= 0 || !in_array($estado, $estadosValidos, true)) {
            $this->responder(422, false, 'Datos inválidos.');
        }

        $modelo = new Asociado();

        try {
            $filas = $modelo->actualizarEstado($asociadoId, $estado);
        } catch (PDOException $e) {
            error_log('Error actualizando estado: ' . $e->getMessage());
            $this->responder(500, false, 'No se pudo actualizar el estado.');
        }

        if ($filas === 0 && !$modelo->existe($asociadoId)) {
            $this->responder(404, false, 'Asociado no encontrado.');
        }

        $this->responder(200, true, 'Estado actualizado.');
    }

    private function responder(int $codigo, bool $exito, string $mensaje): void
    {
        http_response_code($codigo);
        echo json_encode(['exito' => $exito, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
