<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

function iniciarSesionSegura(): void
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

function usuarioActual(): ?array
{
    iniciarSesionSegura();
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

function esSuperAdmin(): bool
{
    $u = usuarioActual();
    return $u !== null && $u['rol'] === 'super_administrador';
}

function requerirSesion(): array
{
    $u = usuarioActual();
    if ($u === null) {
        header('Location: login.php');
        exit;
    }
    return $u;
}

function requerirRol(string $rol): array
{
    $u = requerirSesion();
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
function requerirSesionApi(): array
{
    $u = usuarioActual();
    if ($u === null) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['exito' => false, 'mensaje' => 'Sesión no válida. Vuelve a iniciar sesión.']);
        exit;
    }
    return $u;
}

function generarPasswordTemporal(): string
{
    // 12 caracteres alfanuméricos, fáciles de transcribir a mano.
    $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    $password = '';
    for ($i = 0; $i < 12; $i++) {
        $password .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
    }
    return $password;
}

function requerirRolApi(string $rol): array
{
    $u = requerirSesionApi();
    if ($u['rol'] !== $rol) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['exito' => false, 'mensaje' => 'No tienes permisos para esta acción.']);
        exit;
    }
    return $u;
}
