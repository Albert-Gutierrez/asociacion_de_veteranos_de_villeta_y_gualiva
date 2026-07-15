<?php

declare(strict_types=1);

namespace App\Core;

class Auth
{
    public static function iniciarSesionSegura(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => (($_SERVER['HTTPS'] ?? '') !== ''),
        ]);
        session_start();
    }

    public static function usuarioActual(): ?array
    {
        self::iniciarSesionSegura();
        if (empty($_SESSION['admin_id'])) {
            return null;
        }
        return [
            'id' => (int) $_SESSION['admin_id'],
            'nombre' => (string) $_SESSION['admin_nombre'],
            'email' => (string) $_SESSION['admin_email'],
            'rol' => (string) $_SESSION['admin_rol'],
        ];
    }

    public static function esSuperAdmin(): bool
    {
        $u = self::usuarioActual();
        return $u !== null && $u['rol'] === 'super_administrador';
    }

    public static function requerirSesion(): array
    {
        $u = self::usuarioActual();
        if ($u === null) {
            header('Location: login.php');
            exit;
        }
        return $u;
    }

    public static function requerirRol(string $rol): array
    {
        $u = self::requerirSesion();
        if ($u['rol'] !== $rol) {
            http_response_code(403);
            echo 'No tienes permisos para ver esta página.';
            exit;
        }
        return $u;
    }

    /**
     * Igual que requerirSesion() pero pensado para endpoints JSON: responde
     * 401/403 en JSON en vez de redirigir a login.php.
     */
    public static function requerirSesionApi(): array
    {
        $u = self::usuarioActual();
        if ($u === null) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['exito' => false, 'mensaje' => 'Sesión no válida. Vuelve a iniciar sesión.']);
            exit;
        }
        return $u;
    }

    public static function requerirRolApi(string $rol): array
    {
        $u = self::requerirSesionApi();
        if ($u['rol'] !== $rol) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['exito' => false, 'mensaje' => 'No tienes permisos para esta acción.']);
            exit;
        }
        return $u;
    }

    /**
     * @param string[] $roles
     */
    public static function requerirRoles(array $roles): array
    {
        $u = self::requerirSesion();
        if (!in_array($u['rol'], $roles, true)) {
            http_response_code(403);
            echo 'No tienes permisos para ver esta página.';
            exit;
        }
        return $u;
    }

    /**
     * @param string[] $roles
     */
    public static function requerirRolesApi(array $roles): array
    {
        $u = self::requerirSesionApi();
        if (!in_array($u['rol'], $roles, true)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['exito' => false, 'mensaje' => 'No tienes permisos para esta acción.']);
            exit;
        }
        return $u;
    }

    public static function puedeGestionarSolicitudes(): bool
    {
        $u = self::usuarioActual();
        return $u !== null && in_array($u['rol'], ['administrador', 'super_administrador'], true);
    }

    public static function etiquetaRol(string $rol): string
    {
        $etiquetas = [
            'super_administrador' => 'Super administrador',
            'tesorero' => 'Tesorero',
            'administrador' => 'Administrador',
        ];
        return $etiquetas[$rol] ?? 'Administrador';
    }

    public static function generarPasswordTemporal(): string
    {
        // 12 caracteres alfanuméricos, fáciles de transcribir a mano.
        $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $password = '';
        for ($i = 0; $i < 12; $i++) {
            $password .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }
        return $password;
    }
}
