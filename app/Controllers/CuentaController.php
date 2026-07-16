<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\UsuarioAdmin;

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

        View::render('admin/mi-cuenta', [
            'usuario' => $usuario,
            'csrf' => $csrf,
            'tituloPagina' => 'Mi cuenta',
            'paginaActiva' => 'mi-cuenta',
        ]);
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

        if (strlen($nueva) < 8) {
            $this->responder(422, false, 'La nueva contraseña debe tener al menos 8 caracteres.');
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

    private function responder(int $codigo, bool $exito, string $mensaje): void
    {
        http_response_code($codigo);
        echo json_encode(['exito' => $exito, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
