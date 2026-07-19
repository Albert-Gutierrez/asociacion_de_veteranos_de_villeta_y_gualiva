<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Asociado;
use App\Models\UsuarioAdmin;
use PDOException;

class CuentaController
{
    private const TAMANO_MAXIMO_FOTO = 3 * 1024 * 1024; // 3 MB

    private const TIPOS_PERMITIDOS_FOTO = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function index(): void
    {
        $usuario = Auth::requerirSesion();
        $csrf = Csrf::token();

        $modelo = new UsuarioAdmin();
        $perfil = $modelo->buscarPorId($usuario['id']);

        View::render('admin/mi-cuenta', [
            'usuario' => $usuario,
            'perfil' => $perfil,
            'csrf' => $csrf,
            'tituloPagina' => 'Mi cuenta',
            'paginaActiva' => 'mi-cuenta',
        ]);
    }

    /**
     * Datos personales que cualquier miembro del staff puede completar/editar
     * sobre sí mismo (a diferencia de asociado.php, donde es un
     * administrador editando a un tercero).
     */
    public function actualizarDatos(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        ini_set('display_errors', '0');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responder(405, false, 'Método no permitido.');
        }

        $usuario = Auth::requerirSesionApi();

        $entrada = json_decode(file_get_contents('php://input'), true);
        if (!is_array($entrada)) {
            $this->responder(400, false, 'Solicitud inválida.');
        }

        Csrf::requerirApi($entrada['csrf_token'] ?? null);

        $nombre = $this->limpiarTexto($entrada['nombre'] ?? '', 150);
        $email = trim((string) ($entrada['email'] ?? ''));
        $telefono = $this->limpiarTexto($entrada['telefono'] ?? '', 20);
        $cedula = $this->limpiarTexto($entrada['cedula'] ?? '', 20);
        $fechaNacimientoRaw = trim((string) ($entrada['fecha_nacimiento'] ?? ''));
        $direccion = $this->limpiarTexto($entrada['direccion'] ?? '', 255);
        $fuerza = $this->limpiarTexto($entrada['fuerza'] ?? '', 100);
        $fechaAfiliacionRaw = trim((string) ($entrada['fecha_afiliacion'] ?? ''));

        $errores = [];
        if ($nombre === '') {
            $errores[] = 'El nombre es obligatorio.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo electrónico no es válido.';
        }
        if ($telefono !== '' && !preg_match('/^[0-9+()\s-]{7,20}$/', $telefono)) {
            $errores[] = 'El teléfono no es válido.';
        }
        if ($cedula !== '' && !preg_match('/^[0-9]{5,20}$/', $cedula)) {
            $errores[] = 'La cédula debe contener solo números (5 a 20 dígitos).';
        }
        $fechaNacimiento = null;
        if ($fechaNacimientoRaw !== '') {
            $fecha = \DateTime::createFromFormat('Y-m-d', $fechaNacimientoRaw);
            if (!$fecha || $fecha->format('Y-m-d') !== $fechaNacimientoRaw) {
                $errores[] = 'La fecha de nacimiento no es válida.';
            } else {
                $fechaNacimiento = $fechaNacimientoRaw;
            }
        }
        if ($fuerza !== '' && !in_array($fuerza, Asociado::FUERZAS_VALIDAS, true)) {
            $errores[] = 'Selecciona una fuerza válida de la lista.';
        }
        $fechaAfiliacion = null;
        if ($fechaAfiliacionRaw !== '') {
            $fecha = \DateTime::createFromFormat('Y-m-d', $fechaAfiliacionRaw);
            if (!$fecha || $fecha->format('Y-m-d') !== $fechaAfiliacionRaw) {
                $errores[] = 'La fecha de afiliación no es válida.';
            } elseif ($fecha > new \DateTime('today')) {
                $errores[] = 'La fecha de afiliación no puede ser futura.';
            } else {
                $fechaAfiliacion = $fechaAfiliacionRaw;
            }
        }

        if ($errores !== []) {
            $this->responder(422, false, implode(' ', $errores));
        }

        $modelo = new UsuarioAdmin();

        $otroConEseEmail = $modelo->buscarPorEmail($email);
        if ($otroConEseEmail && (int) $otroConEseEmail['id'] !== $usuario['id']) {
            $this->responder(409, false, 'Ya existe otra cuenta con ese correo electrónico.');
        }
        if ($cedula !== '') {
            $otroConEsaCedula = $modelo->buscarPorCedula($cedula);
            if ($otroConEsaCedula && (int) $otroConEsaCedula['id'] !== $usuario['id']) {
                $this->responder(409, false, 'Ya existe otra cuenta registrada con ese número de cédula.');
            }
        }

        try {
            $modelo->actualizarDatos($usuario['id'], [
                'nombre' => $nombre,
                'email' => $email,
                'telefono' => $telefono !== '' ? $telefono : null,
                'cedula' => $cedula !== '' ? $cedula : null,
                'fecha_nacimiento' => $fechaNacimiento,
                'direccion' => $direccion !== '' ? $direccion : null,
                'fuerza' => $fuerza !== '' ? $fuerza : null,
                'fecha_afiliacion' => $fechaAfiliacion,
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->responder(409, false, 'Ya existe otra cuenta registrada con esos datos.');
            }
            error_log('Error actualizando datos de la cuenta: ' . $e->getMessage());
            $this->responder(500, false, 'No se pudo guardar los cambios.');
        }

        $_SESSION['admin_nombre'] = $nombre;
        $_SESSION['admin_email'] = $email;

        $this->responder(200, true, 'Datos actualizados correctamente.');
    }

    public function cambiarPassword(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        ini_set('display_errors', '0');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responder(405, false, 'Método no permitido.');
        }

        $usuario = Auth::requerirSesionApi();

        $entrada = json_decode(file_get_contents('php://input'), true);
        if (!is_array($entrada)) {
            $this->responder(400, false, 'Solicitud inválida.');
        }

        Csrf::requerirApi($entrada['csrf_token'] ?? null);

        $actual = (string) ($entrada['password_actual'] ?? '');
        $nueva = (string) ($entrada['password_nueva'] ?? '');
        $confirmar = (string) ($entrada['password_confirmar'] ?? '');

        $errorPassword = Auth::validarPassword($nueva);
        if ($errorPassword !== null) {
            $this->responder(422, false, $errorPassword);
        }
        if ($nueva !== $confirmar) {
            $this->responder(422, false, 'La confirmación no coincide con la nueva contraseña.');
        }

        $modelo = new UsuarioAdmin();
        $hashActual = $modelo->obtenerHashPassword($usuario['id']);

        if ($hashActual === null || !password_verify($actual, $hashActual)) {
            $this->responder(422, false, 'La contraseña actual no es correcta.');
        }

        $modelo->actualizarPassword($usuario['id'], password_hash($nueva, PASSWORD_DEFAULT));

        $this->responder(200, true, 'Contraseña actualizada correctamente.');
    }

    /**
     * Foto de perfil opcional, mostrada en el sidebar en vez del ícono
     * genérico. Se guarda en img/perfiles/ (no es información sensible,
     * a diferencia de los soportes de pago de los tickets).
     */
    public function subirFoto(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        ini_set('display_errors', '0');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responder(405, false, 'Método no permitido.');
        }

        $usuario = Auth::requerirSesionApi();
        Csrf::requerirApi($_POST['csrf_token'] ?? null);

        if (empty($_FILES['foto']['tmp_name']) || $_FILES['foto']['error'] === UPLOAD_ERR_NO_FILE) {
            $this->responder(422, false, 'Selecciona una foto.');
        }
        if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $this->responder(422, false, 'No se pudo subir la foto. Intenta de nuevo.');
        }
        if ($_FILES['foto']['size'] > self::TAMANO_MAXIMO_FOTO) {
            $this->responder(422, false, 'La foto no puede pesar más de 3 MB.');
        }

        $info = @getimagesize($_FILES['foto']['tmp_name']);
        if ($info === false || !isset(self::TIPOS_PERMITIDOS_FOTO[$info['mime']])) {
            $this->responder(422, false, 'La foto debe ser una imagen JPG, PNG o WEBP.');
        }

        $extension = self::TIPOS_PERMITIDOS_FOTO[$info['mime']];
        $nombreArchivo = bin2hex(random_bytes(16)) . '.' . $extension;
        $carpetaDestino = __DIR__ . '/../../img/perfiles/';
        if (!is_dir($carpetaDestino)) {
            mkdir($carpetaDestino, 0755, true);
        }

        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $carpetaDestino . $nombreArchivo)) {
            $this->responder(500, false, 'No se pudo guardar la foto.');
        }

        $modelo = new UsuarioAdmin();

        $fotoAnterior = $modelo->obtenerFoto($usuario['id']);
        if ($fotoAnterior && is_file($carpetaDestino . $fotoAnterior)) {
            unlink($carpetaDestino . $fotoAnterior);
        }

        $modelo->actualizarFoto($usuario['id'], $nombreArchivo);
        $_SESSION['admin_foto'] = $nombreArchivo;

        $this->responder(200, true, 'Foto de perfil actualizada.');
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

    private function responder(int $codigo, bool $exito, string $mensaje): void
    {
        http_response_code($codigo);
        echo json_encode(['exito' => $exito, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
