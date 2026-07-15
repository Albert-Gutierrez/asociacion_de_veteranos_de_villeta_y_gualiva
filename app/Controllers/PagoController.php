<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\Asociado;
use App\Models\PagoCuota;
use PDOException;

class PagoController
{
    public function marcar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        ini_set('display_errors', '0');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responder(405, false, 'Método no permitido.');
        }

        $usuario = Auth::requerirSesionApi();

        $entrada = json_decode(file_get_contents('php://input'), true);
        if (!is_array($entrada)) {
            $this->responder(400, false, 'Solicitud inválida.');
        }

        Csrf::requerirApi($entrada['csrf_token'] ?? null);

        $asociadoId = (int) ($entrada['asociado_id'] ?? 0);
        $anio = (int) ($entrada['anio'] ?? 0);
        $mes = (int) ($entrada['mes'] ?? 0);
        $accion = (string) ($entrada['accion'] ?? 'pagado');

        if ($asociadoId <= 0 || $anio < 2020 || $mes < 1 || $mes > 12 || !in_array($accion, ['pagado', 'moroso'], true)) {
            $this->responder(422, false, 'Datos inválidos.');
        }

        $asociadoModelo = new Asociado();
        $asociado = $asociadoModelo->buscarPorId($asociadoId);

        if (!$asociado) {
            $this->responder(404, false, 'Asociado no encontrado.');
        }
        if ($asociado['estado'] !== 'aprobado') {
            $this->responder(422, false, 'Solo los asociados aprobados pagan cuota mensual.');
        }

        $pagoModelo = new PagoCuota();

        if ($accion === 'moroso') {
            try {
                $pagoModelo->eliminar($asociadoId, $anio, $mes);
            } catch (PDOException $e) {
                error_log('Error marcando moroso: ' . $e->getMessage());
                $this->responder(500, false, 'No se pudo actualizar el estado de la cuota.');
            }
            $this->responder(200, true, 'Mes marcado como moroso.');
        }

        try {
            $pagoModelo->registrar($asociadoId, $anio, $mes, $usuario['id']);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->responder(409, false, 'Ese mes ya estaba marcado como pagado.');
            }
            error_log('Error registrando pago: ' . $e->getMessage());
            $this->responder(500, false, 'No se pudo registrar el pago.');
        }

        $this->responder(200, true, 'Pago registrado correctamente.');
    }

    private function responder(int $codigo, bool $exito, string $mensaje): void
    {
        http_response_code($codigo);
        echo json_encode(['exito' => $exito, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
