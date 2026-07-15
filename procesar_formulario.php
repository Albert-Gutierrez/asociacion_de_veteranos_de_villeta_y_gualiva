<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

use PHPMailer\PHPMailer\PHPMailer;

header('Content-Type: application/json; charset=utf-8');

function responder(int $codigoHttp, bool $exito, string $mensaje): void
{
    http_response_code($codigoHttp);
    echo json_encode(['exito' => $exito, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, false, 'Método no permitido.');
}

$entrada = json_decode(file_get_contents('php://input'), true);
if (!is_array($entrada)) {
    responder(400, false, 'Solicitud inválida.');
}

// Honeypot: los bots suelen rellenar todos los campos, incluido este,
// que un usuario real nunca ve ni completa.
if (!empty($entrada['sitio_web'] ?? '')) {
    responder(200, true, 'Registro recibido.');
}

function limpiarTexto($valor, int $maxLength): string
{
    $valor = trim((string) ($valor ?? ''));
    $valor = strip_tags($valor);
    return mb_substr($valor, 0, $maxLength);
}

$nombres = limpiarTexto($entrada['nombres'] ?? '', 100);
$apellidos = limpiarTexto($entrada['apellidos'] ?? '', 100);
$cedula = limpiarTexto($entrada['cedula'] ?? '', 20);
$fechaNacimientoRaw = trim((string) ($entrada['fecha_nacimiento'] ?? ''));
$telefono = limpiarTexto($entrada['telefono'] ?? '', 20);
$email = trim((string) ($entrada['email'] ?? ''));
$direccion = limpiarTexto($entrada['direccion'] ?? '', 255);
$fuerza = limpiarTexto($entrada['fuerza'] ?? '', 100);
$mensaje = limpiarTexto($entrada['mensaje'] ?? '', 2000);

$errores = [];

if ($nombres === '') {
    $errores[] = 'El nombre es obligatorio.';
}
if ($apellidos === '') {
    $errores[] = 'El apellido es obligatorio.';
}
if (!preg_match('/^[0-9]{5,20}$/', $cedula)) {
    $errores[] = 'La cédula debe contener solo números (5 a 20 dígitos).';
}
$fechaNacimiento = null;
if ($fechaNacimientoRaw !== '') {
    $fecha = DateTime::createFromFormat('Y-m-d', $fechaNacimientoRaw);
    if (!$fecha || $fecha->format('Y-m-d') !== $fechaNacimientoRaw) {
        $errores[] = 'La fecha de nacimiento no es válida.';
    } else {
        $fechaNacimiento = $fechaNacimientoRaw;
    }
}
if (!preg_match('/^[0-9+()\s-]{7,20}$/', $telefono)) {
    $errores[] = 'El teléfono no es válido.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errores[] = 'El correo electrónico no es válido.';
}
if ($fuerza === '') {
    $errores[] = 'La fuerza en la que sirvió es obligatoria.';
}

if ($errores !== []) {
    responder(422, false, implode(' ', $errores));
}

try {
    $pdo = obtenerConexionBD();
} catch (PDOException $e) {
    error_log('Error de conexión a BD: ' . $e->getMessage());
    responder(500, false, 'No se pudo procesar tu solicitud en este momento. Intenta más tarde.');
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Límite simple: máximo 3 envíos por IP en los últimos 10 minutos.
try {
    $stmtLimite = $pdo->prepare(
        'SELECT COUNT(*) FROM asociados WHERE ip_registro = :ip AND creado_en > (NOW() - INTERVAL 10 MINUTE)'
    );
    $stmtLimite->execute(['ip' => $ip]);
    if ((int) $stmtLimite->fetchColumn() >= 3) {
        responder(429, false, 'Has enviado varias solicitudes recientemente. Intenta de nuevo más tarde.');
    }
} catch (PDOException $e) {
    error_log('Error verificando límite de envíos: ' . $e->getMessage());
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO asociados
            (nombres, apellidos, cedula, fecha_nacimiento, telefono, email, direccion, fuerza, mensaje, ip_registro)
         VALUES
            (:nombres, :apellidos, :cedula, :fecha_nacimiento, :telefono, :email, :direccion, :fuerza, :mensaje, :ip)'
    );
    $stmt->execute([
        'nombres' => $nombres,
        'apellidos' => $apellidos,
        'cedula' => $cedula,
        'fecha_nacimiento' => $fechaNacimiento,
        'telefono' => $telefono,
        'email' => $email,
        'direccion' => $direccion !== '' ? $direccion : null,
        'fuerza' => $fuerza,
        'mensaje' => $mensaje !== '' ? $mensaje : null,
        'ip' => $ip,
    ]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        responder(409, false, 'Ya existe una solicitud registrada con ese número de cédula.');
    }
    error_log('Error insertando asociado: ' . $e->getMessage());
    responder(500, false, 'No se pudo guardar tu solicitud. Intenta más tarde.');
}

// El correo es una notificación adicional: si falla, la solicitud ya quedó
// guardada en la base de datos, así que no se reporta como error al usuario.
try {
    enviarCorreoNotificacion(compact(
        'nombres', 'apellidos', 'cedula', 'telefono', 'email', 'direccion', 'fuerza', 'mensaje'
    ));
} catch (Throwable $e) {
    error_log('Error enviando correo de notificación: ' . $e->getMessage());
}

responder(200, true, 'Solicitud registrada correctamente.');

/**
 * @param array<string, string|null> $datos
 */
function enviarCorreoNotificacion(array $datos): void
{
    $destinatario = $_ENV['DESTINATARIO_ASOCIADOS'] ?? '';
    $smtpUser = $_ENV['SMTP_USER'] ?? '';
    if ($destinatario === '' || $smtpUser === '') {
        return;
    }

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $_ENV['SMTP_PASS'] ?? '';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = (int) ($_ENV['SMTP_PORT'] ?? 587);
    $mail->CharSet = 'UTF-8';

    $mail->setFrom($_ENV['SMTP_FROM'] ?? $smtpUser, $_ENV['SMTP_FROM_NAME'] ?? 'Sitio web ASOVEGU');
    $mail->addAddress($destinatario);
    $mail->addReplyTo($datos['email'], $datos['nombres'] . ' ' . $datos['apellidos']);

    $mail->isHTML(true);
    $mail->Subject = 'Nueva solicitud de afiliación - ' . $datos['nombres'] . ' ' . $datos['apellidos'];

    $filas = '';
    $etiquetas = [
        'nombres' => 'Nombres',
        'apellidos' => 'Apellidos',
        'cedula' => 'Cédula',
        'telefono' => 'Teléfono',
        'email' => 'Email',
        'direccion' => 'Dirección',
        'fuerza' => 'Fuerza',
        'mensaje' => 'Mensaje',
    ];
    foreach ($etiquetas as $campo => $etiqueta) {
        $valor = htmlspecialchars((string) ($datos[$campo] ?? ''), ENT_QUOTES, 'UTF-8');
        $filas .= "<tr><td><strong>{$etiqueta}</strong></td><td>{$valor}</td></tr>";
    }

    $mail->Body = "<h2>Nueva solicitud de afiliación</h2><table cellpadding='6'>{$filas}</table>";
    $mail->AltBody = strip_tags(str_replace('</tr>', "\n", $filas));

    $mail->send();
}
