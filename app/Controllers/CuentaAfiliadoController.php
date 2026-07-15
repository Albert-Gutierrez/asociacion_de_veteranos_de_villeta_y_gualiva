<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuthAfiliado;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Asociado;

class CuentaAfiliadoController
{
    public function index(): void
    {
        $afiliado = AuthAfiliado::requerirSesion();
        $csrf = Csrf::token();

        View::render('afiliado/cambiar-password', [
            'afiliado' => $afiliado,
            'csrf' => $csrf,
            'tituloPagina' => 'Cambiar contraseña',
        ]);
    }

    public function actualizar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        ini_set('display_errors', '0');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responder(405, false, 'Método no permitido.');
        }

        $afiliado = AuthAfiliado::requerirSesionApi();

        $entrada = json_decode(file_get_contents('php://input'), true);
        if (!is_array($entrada)) {
            $this->responder(400, false, 'Solicitud inválida.');
        }

        Csrf::requerirApi($entrada['csrf_token'] ?? null);

        $actual = (string) ($entrada['password_actual'] ?? '');
        $nueva = (string) ($entrada['password_nueva'] ?? '');
        $confirmar = (string) ($entrada['password_confirmar'] ?? '');

        if (strlen($nueva) < 8) {
            $this->responder(422, false, 'La nueva contraseña debe tener al menos 8 caracteres.');
        }
        if ($nueva !== $confirmar) {
            $this->responder(422, false, 'La confirmación no coincide con la nueva contraseña.');
        }

        $modelo = new Asociado();
        $hashActual = $modelo->obtenerHashPassword($afiliado['id']);

        if ($hashActual === null || !password_verify($actual, $hashActual)) {
            $this->responder(422, false, 'La contraseña actual no es correcta.');
        }

        $modelo->actualizarPassword($afiliado['id'], password_hash($nueva, PASSWORD_DEFAULT));

        $this->responder(200, true, 'Contraseña actualizada correctamente.');
    }

    private function responder(int $codigo, bool $exito, string $mensaje): void
    {
        http_response_code($codigo);
        echo json_encode(['exito' => $exito, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
