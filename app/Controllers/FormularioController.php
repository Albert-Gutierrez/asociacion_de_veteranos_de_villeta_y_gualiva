<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Mailer;
use App\Models\Asociado;
use DateTime;
use PDOException;
use Throwable;

class FormularioController
{
    public function store(): void
    {
        // Nunca mostrar errores de PHP al visitante, sin importar la
        // configuración del hosting (defensa adicional a display_errors en php.ini).
        ini_set('display_errors', '0');
        error_reporting(E_ALL);

        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responder(405, false, 'Método no permitido.');
        }

        $entrada = json_decode(file_get_contents('php://input'), true);
        if (!is_array($entrada)) {
            $this->responder(400, false, 'Solicitud inválida.');
        }

        // Honeypot: los bots suelen rellenar todos los campos, incluido este,
        // que un usuario real nunca ve ni completa.
        if (!empty($entrada['sitio_web'] ?? '')) {
            $this->responder(200, true, 'Registro recibido.');
        }

        $nombres = $this->limpiarTexto($entrada['nombres'] ?? '', 100);
        $apellidos = $this->limpiarTexto($entrada['apellidos'] ?? '', 100);
        $cedula = $this->limpiarTexto($entrada['cedula'] ?? '', 20);
        $fechaNacimientoRaw = trim((string) ($entrada['fecha_nacimiento'] ?? ''));
        $telefono = $this->limpiarTexto($entrada['telefono'] ?? '', 20);
        $email = trim((string) ($entrada['email'] ?? ''));
        $direccion = $this->limpiarTexto($entrada['direccion'] ?? '', 255);
        $fuerza = $this->limpiarTexto($entrada['fuerza'] ?? '', 100);
        $mensaje = $this->limpiarTexto($entrada['mensaje'] ?? '', 2000);

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
        if ($fechaNacimientoRaw === '') {
            $errores[] = 'La fecha de nacimiento es obligatoria.';
        } else {
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
        if ($direccion === '') {
            $errores[] = 'La dirección de residencia es obligatoria.';
        }
        if (!in_array($fuerza, Asociado::FUERZAS_VALIDAS, true)) {
            $errores[] = 'Selecciona una fuerza válida de la lista.';
        }

        if ($errores !== []) {
            $this->responder(422, false, implode(' ', $errores));
        }

        $modelo = new Asociado();

        // Verificar de una vez si la cédula o el correo ya están en el sistema,
        // para dar un mensaje claro en vez de un error genérico de base de datos.
        if ($modelo->buscarPorCedula($cedula) !== null) {
            $this->responder(409, false, 'Ya existe una solicitud registrada con ese número de cédula en el sistema.');
        }
        if ($modelo->buscarPorEmail($email) !== null) {
            $this->responder(409, false, 'Ya existe una solicitud registrada con ese correo electrónico en el sistema.');
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // Límite simple: máximo 3 envíos por IP en los últimos 10 minutos.
        try {
            if ($modelo->contarSolicitudesRecientesPorIp($ip) >= 3) {
                $this->responder(429, false, 'Has enviado varias solicitudes recientemente. Intenta de nuevo más tarde.');
            }
        } catch (PDOException $e) {
            error_log('Error verificando límite de envíos: ' . $e->getMessage());
        }

        try {
            $modelo->crear([
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'cedula' => $cedula,
                'fecha_nacimiento' => $fechaNacimiento,
                'telefono' => $telefono,
                'email' => $email,
                'direccion' => $direccion,
                'fuerza' => $fuerza,
                'mensaje' => $mensaje !== '' ? $mensaje : null,
                'ip' => $ip,
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->responder(409, false, 'Ya existe una solicitud registrada con ese número de cédula.');
            }
            error_log('Error insertando asociado: ' . $e->getMessage());
            $this->responder(500, false, 'No se pudo guardar tu solicitud. Intenta más tarde.');
        }

        // El correo es una notificación adicional: si falla, la solicitud ya
        // quedó guardada en la base de datos, así que no se reporta como error.
        try {
            $this->enviarCorreoNotificacion(compact(
                'nombres', 'apellidos', 'cedula', 'telefono', 'email', 'direccion', 'fuerza', 'mensaje'
            ));
        } catch (Throwable $e) {
            error_log('Error enviando correo de notificación: ' . $e->getMessage());
        }

        $this->responder(200, true, 'Solicitud registrada correctamente.');
    }

    /**
     * @param mixed $valor
     */
    private function limpiarTexto($valor, int $maxLength): string
    {
        $valor = trim((string) ($valor ?? ''));
        $valor = strip_tags($valor);
        return mb_substr($valor, 0, $maxLength);
    }

    /**
     * @param array<string, string|null> $datos
     */
    private function enviarCorreoNotificacion(array $datos): void
    {
        $destinatario = $_ENV['DESTINATARIO_ASOCIADOS'] ?? '';
        if ($destinatario === '') {
            return;
        }

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

        $filas = '';
        foreach ($etiquetas as $campo => $etiqueta) {
            $valor = htmlspecialchars((string) ($datos[$campo] ?? ''), ENT_QUOTES, 'UTF-8');
            $filas .= "<tr><td><strong>{$etiqueta}</strong></td><td>{$valor}</td></tr>";
        }

        $asunto = 'Nueva solicitud de afiliación - ' . $datos['nombres'] . ' ' . $datos['apellidos'];
        $cuerpo = "<h2>Nueva solicitud de afiliación</h2><table cellpadding='6'>{$filas}</table>";

        Mailer::enviar($destinatario, $asunto, $cuerpo, $datos['email'], $datos['nombres'] . ' ' . $datos['apellidos']);
    }

    private function responder(int $codigoHttp, bool $exito, string $mensaje): void
    {
        http_response_code($codigoHttp);
        echo json_encode(['exito' => $exito, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
