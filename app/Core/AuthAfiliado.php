<?php

declare(strict_types=1);

namespace App\Core;

class AuthAfiliado
{
    public static function afiliadoActual(): ?array
    {
        Auth::iniciarSesionSegura();
        if (empty($_SESSION['afiliado_id'])) {
            return null;
        }
        return [
            'id' => (int) $_SESSION['afiliado_id'],
            'nombre' => (string) $_SESSION['afiliado_nombre'],
            'email' => (string) $_SESSION['afiliado_email'],
        ];
    }

    public static function requerirSesion(): array
    {
        $a = self::afiliadoActual();
        if ($a === null) {
            header('Location: login.php');
            exit;
        }
        return $a;
    }

    /**
     * Igual que requerirSesion() pero pensado para endpoints JSON: responde
     * 401 en JSON en vez de redirigir a login.php.
     */
    public static function requerirSesionApi(): array
    {
        $a = self::afiliadoActual();
        if ($a === null) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['exito' => false, 'mensaje' => 'Sesión no válida. Vuelve a iniciar sesión.']);
            exit;
        }
        return $a;
    }
}
