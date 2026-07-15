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

requerirRolesApi(['administrador', 'super_administrador']);

$entrada = json_decode(file_get_contents('php://input'), true);
if (!is_array($entrada)) {
    responder(400, false, 'Solicitud inválida.');
}

requerirCsrfApi($entrada['csrf_token'] ?? null);

$asociadoId = (int) ($entrada['asociado_id'] ?? 0);
$estado = (string) ($entrada['estado'] ?? '');

$estadosValidos = ['pendiente', 'aprobado', 'rechazado'];
if ($asociadoId <= 0 || !in_array($estado, $estadosValidos, true)) {
    responder(422, false, 'Datos inválidos.');
}

$pdo = obtenerConexionBD();

try {
    $stmt = $pdo->prepare('UPDATE asociados SET estado = :estado WHERE id = :id');
    $stmt->execute(['estado' => $estado, 'id' => $asociadoId]);
} catch (PDOException $e) {
    error_log('Error actualizando estado: ' . $e->getMessage());
    responder(500, false, 'No se pudo actualizar el estado.');
}

if ($stmt->rowCount() === 0) {
    $existe = $pdo->prepare('SELECT id FROM asociados WHERE id = :id');
    $existe->execute(['id' => $asociadoId]);
    if (!$existe->fetch()) {
        responder(404, false, 'Asociado no encontrado.');
    }
}

responder(200, true, 'Estado actualizado.');
