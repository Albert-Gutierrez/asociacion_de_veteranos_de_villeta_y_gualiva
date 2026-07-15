<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\UsuarioAdmin;
use PDOException;

class AdministradorController
{
    public function index(): void
    {
        $usuario = Auth::requerirRol('super_administrador');
        $csrf = Csrf::token();

        $modelo = new UsuarioAdmin();
        $admins = $modelo->listarTodos();

        View::render('admin/administradores', [
            'usuario' => $usuario,
            'csrf' => $csrf,
            'tituloPagina' => 'Administradores',
            'paginaActiva' => 'administradores',
            'admins' => $admins,
        ]);
    }

    public function gestionar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        ini_set('display_errors', '0');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responder(405, false, 'Método no permitido.');
        }

        $usuario = Auth::requerirRolApi('super_administrador');

        $entrada = json_decode(file_get_contents('php://input'), true);
        if (!is_array($entrada)) {
            $this->responder(400, false, 'Solicitud inválida.');
        }

        Csrf::requerirApi($entrada['csrf_token'] ?? null);

        $accion = (string) ($entrada['accion'] ?? '');
        $modelo = new UsuarioAdmin();

        if ($accion === 'crear') {
            $this->crear($entrada, $modelo);
        }
        if ($accion === 'toggle') {
            $this->toggle($entrada, $modelo, $usuario);
        }
        if ($accion === 'resetear') {
            $this->resetear($entrada, $modelo);
        }

        $this->responder(400, false, 'Acción no reconocida.');
    }

    private function crear(array $entrada, UsuarioAdmin $modelo): void
    {
        $nombre = trim((string) ($entrada['nombre'] ?? ''));
        $email = trim((string) ($entrada['email'] ?? ''));
        $telefono = trim((string) ($entrada['telefono'] ?? ''));
        $rol = (string) ($entrada['rol'] ?? 'administrador');

        if ($nombre === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($rol, ['administrador', 'super_administrador', 'tesorero'], true)) {
            $this->responder(422, false, 'Datos inválidos.');
        }
        if ($telefono !== '' && !preg_match('/^[0-9+()\s-]{7,20}$/', $telefono)) {
            $this->responder(422, false, 'El teléfono no es válido.');
        }

        $passwordTemporal = Auth::generarPasswordTemporal();
        $hash = password_hash($passwordTemporal, PASSWORD_DEFAULT);

        try {
            $modelo->crear($nombre, $email, $telefono !== '' ? $telefono : null, $hash, $rol);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->responder(409, false, 'Ya existe una cuenta con ese correo.');
            }
            error_log('Error creando admin: ' . $e->getMessage());
            $this->responder(500, false, 'No se pudo crear la cuenta.');
        }

        $this->responder(200, true, 'Cuenta creada correctamente.', ['password_temporal' => $passwordTemporal]);
    }

    private function toggle(array $entrada, UsuarioAdmin $modelo, array $usuario): void
    {
        $id = (int) ($entrada['id'] ?? 0);
        if ($id <= 0) {
            $this->responder(422, false, 'Datos inválidos.');
        }
        if ($id === $usuario['id']) {
            $this->responder(422, false, 'No puedes desactivar tu propia cuenta.');
        }

        $modelo->alternarActivo($id);

        $activo = $modelo->obtenerActivo($id);
        if ($activo === null) {
            $this->responder(404, false, 'Cuenta no encontrada.');
        }

        $this->responder(200, true, 'Estado actualizado.', ['activo' => $activo]);
    }

    private function resetear(array $entrada, UsuarioAdmin $modelo): void
    {
        $id = (int) ($entrada['id'] ?? 0);
        if ($id <= 0) {
            $this->responder(422, false, 'Datos inválidos.');
        }

        $passwordTemporal = Auth::generarPasswordTemporal();
        $hash = password_hash($passwordTemporal, PASSWORD_DEFAULT);

        $filas = $modelo->resetearPasswordAdmin($id, $hash);

        if ($filas === 0 && $modelo->obtenerActivo($id) === null) {
            $this->responder(404, false, 'Cuenta no encontrada.');
        }

        $this->responder(200, true, 'Contraseña restablecida.', ['password_temporal' => $passwordTemporal]);
    }

    private function responder(int $codigo, bool $exito, string $mensaje, array $extra = []): void
    {
        http_response_code($codigo);
        echo json_encode(array_merge(['exito' => $exito, 'mensaje' => $mensaje], $extra), JSON_UNESCAPED_UNICODE);
        exit;
    }
}
