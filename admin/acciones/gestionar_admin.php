<?php

declare(strict_types=1);

require_once __DIR__ . '/../incluye/auth.php';
require_once __DIR__ . '/../incluye/csrf.php';

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

function responder(int $codigo, bool $exito, string $mensaje, array $extra = []): void
{
    http_response_code($codigo);
    echo json_encode(array_merge(['exito' => $exito, 'mensaje' => $mensaje], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, false, 'Método no permitido.');
}

$usuario = requerirRolApi('super_administrador');

$entrada = json_decode(file_get_contents('php://input'), true);
if (!is_array($entrada)) {
    responder(400, false, 'Solicitud inválida.');
}

requerirCsrfApi($entrada['csrf_token'] ?? null);

$accion = (string) ($entrada['accion'] ?? '');
$pdo = obtenerConexionBD();

if ($accion === 'crear') {
    $nombre = trim((string) ($entrada['nombre'] ?? ''));
    $email = trim((string) ($entrada['email'] ?? ''));
    $rol = (string) ($entrada['rol'] ?? 'administrador');

    if ($nombre === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($rol, ['administrador', 'super_administrador'], true)) {
        responder(422, false, 'Datos inválidos.');
    }

    $passwordTemporal = generarPasswordTemporal();
    $hash = password_hash($passwordTemporal, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO usuarios_admin (nombre, email, password_hash, rol) VALUES (:nombre, :email, :hash, :rol)'
        );
        $stmt->execute(['nombre' => $nombre, 'email' => $email, 'hash' => $hash, 'rol' => $rol]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            responder(409, false, 'Ya existe una cuenta con ese correo.');
        }
        error_log('Error creando admin: ' . $e->getMessage());
        responder(500, false, 'No se pudo crear la cuenta.');
    }

    responder(200, true, 'Cuenta creada correctamente.', ['password_temporal' => $passwordTemporal]);
}

if ($accion === 'toggle') {
    $id = (int) ($entrada['id'] ?? 0);
    if ($id <= 0) {
        responder(422, false, 'Datos inválidos.');
    }
    if ($id === $usuario['id']) {
        responder(422, false, 'No puedes desactivar tu propia cuenta.');
    }

    $stmt = $pdo->prepare('UPDATE usuarios_admin SET activo = NOT activo WHERE id = :id');
    $stmt->execute(['id' => $id]);

    $check = $pdo->prepare('SELECT activo FROM usuarios_admin WHERE id = :id');
    $check->execute(['id' => $id]);
    $fila = $check->fetch();
    if (!$fila) {
        responder(404, false, 'Cuenta no encontrada.');
    }

    responder(200, true, 'Estado actualizado.', ['activo' => (int) $fila['activo']]);
}

if ($accion === 'resetear') {
    $id = (int) ($entrada['id'] ?? 0);
    if ($id <= 0) {
        responder(422, false, 'Datos inválidos.');
    }

    $passwordTemporal = generarPasswordTemporal();
    $hash = password_hash($passwordTemporal, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        'UPDATE usuarios_admin SET password_hash = :hash, intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = :id'
    );
    $stmt->execute(['hash' => $hash, 'id' => $id]);

    if ($stmt->rowCount() === 0) {
        $check = $pdo->prepare('SELECT id FROM usuarios_admin WHERE id = :id');
        $check->execute(['id' => $id]);
        if (!$check->fetch()) {
            responder(404, false, 'Cuenta no encontrada.');
        }
    }

    responder(200, true, 'Contraseña restablecida.', ['password_temporal' => $passwordTemporal]);
}

responder(400, false, 'Acción no reconocida.');
