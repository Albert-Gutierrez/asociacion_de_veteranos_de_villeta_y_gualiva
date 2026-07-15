<?php

declare(strict_types=1);

require_once __DIR__ . '/../incluye/auth.php';
require_once __DIR__ . '/../incluye/csrf.php';

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

function responder(int $codigo, bool $exito, string $mensaje): void
{
    http_response_code($codigo);
    echo json_encode(['exito' => $exito, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, false, 'Método no permitido.');
}

$usuario = requerirSesionApi();

$entrada = json_decode(file_get_contents('php://input'), true);
if (!is_array($entrada)) {
    responder(400, false, 'Solicitud inválida.');
}

requerirCsrfApi($entrada['csrf_token'] ?? null);

$actual = (string) ($entrada['password_actual'] ?? '');
$nueva = (string) ($entrada['password_nueva'] ?? '');
$confirmar = (string) ($entrada['password_confirmar'] ?? '');

if (strlen($nueva) < 8) {
    responder(422, false, 'La nueva contraseña debe tener al menos 8 caracteres.');
}
if ($nueva !== $confirmar) {
    responder(422, false, 'La confirmación no coincide con la nueva contraseña.');
}

$pdo = obtenerConexionBD();
$stmt = $pdo->prepare('SELECT password_hash FROM usuarios_admin WHERE id = :id');
$stmt->execute(['id' => $usuario['id']]);
$cuenta = $stmt->fetch();

if (!$cuenta || !password_verify($actual, $cuenta['password_hash'])) {
    responder(422, false, 'La contraseña actual no es correcta.');
}

$hash = password_hash($nueva, PASSWORD_DEFAULT);
$upd = $pdo->prepare('UPDATE usuarios_admin SET password_hash = :hash WHERE id = :id');
$upd->execute(['hash' => $hash, 'id' => $usuario['id']]);

responder(200, true, 'Contraseña actualizada correctamente.');
