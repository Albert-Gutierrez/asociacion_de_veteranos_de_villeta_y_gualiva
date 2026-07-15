<?php

declare(strict_types=1);

namespace App\Core;

class Csrf
{
    public static function token(): string
    {
        Auth::iniciarSesionSegura();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validar(?string $token): bool
    {
        Auth::iniciarSesionSegura();
        if (empty($_SESSION['csrf_token']) || $token === null || $token === '') {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Para endpoints JSON: responde 403 y termina la ejecución si el token no es válido.
     */
    public static function requerirApi(?string $token): void
    {
        if (!self::validar($token)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['exito' => false, 'mensaje' => 'Tu sesión expiró, recarga la página e intenta de nuevo.']);
            exit;
        }
    }
}
