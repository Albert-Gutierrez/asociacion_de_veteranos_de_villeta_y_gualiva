<?php

declare(strict_types=1);

require_once __DIR__ . '/../incluye/auth.php';
require_once __DIR__ . '/../incluye/csrf.php';
require_once __DIR__ . '/../incluye/cuotas.php';

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

$asociadoId = (int) ($entrada['asociado_id'] ?? 0);
$anio = (int) ($entrada['anio'] ?? 0);
$mes = (int) ($entrada['mes'] ?? 0);

if ($asociadoId <= 0 || $anio < 2020 || $mes < 1 || $mes > 12) {
    responder(422, false, 'Datos inválidos.');
}

$pdo = obtenerConexionBD();

$stmt = $pdo->prepare('SELECT estado FROM asociados WHERE id = :id');
$stmt->execute(['id' => $asociadoId]);
$asociado = $stmt->fetch();

if (!$asociado) {
    responder(404, false, 'Asociado no encontrado.');
}
if ($asociado['estado'] !== 'aprobado') {
    responder(422, false, 'Solo los asociados aprobados pagan cuota mensual.');
}

try {
    $insert = $pdo->prepare(
        'INSERT INTO pagos_cuota (asociado_id, anio, mes, fecha_pago, monto, registrado_por)
         VALUES (:asociado_id, :anio, :mes, CURDATE(), :monto, :registrado_por)'
    );
    $insert->execute([
        'asociado_id' => $asociadoId,
        'anio' => $anio,
        'mes' => $mes,
        'monto' => MONTO_CUOTA,
        'registrado_por' => $usuario['id'],
    ]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        responder(409, false, 'Ese mes ya estaba marcado como pagado.');
    }
    error_log('Error registrando pago: ' . $e->getMessage());
    responder(500, false, 'No se pudo registrar el pago.');
}

responder(200, true, 'Pago registrado correctamente.');
