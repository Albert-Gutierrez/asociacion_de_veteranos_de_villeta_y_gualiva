<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Mailer;
use App\Core\View;
use App\Models\UsuarioAdmin;
use DateTimeImmutable;

class AuthController
{
    private const MAX_INTENTOS = 5;
    private const BLOQUEO_MINUTOS = 15;
    private const MINUTOS_ENTRE_SOLICITUDES = 5;

    public function login(): void
    {
        Auth::iniciarSesionSegura();

        if (Auth::usuarioActual() !== null) {
            header('Location: dashboard.php');
            exit;
        }

        $modelo = new UsuarioAdmin();
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
                $error = 'Tu sesión expiró, intenta de nuevo.';
            } else {
                $email = trim((string) ($_POST['email'] ?? ''));
                $password = (string) ($_POST['password'] ?? '');

                $cuenta = $modelo->buscarPorEmail($email);
                $credencialesValidas = false;

                if ($cuenta && (int) $cuenta['activo'] === 1) {
                    $bloqueadoHasta = $cuenta['bloqueado_hasta'] ? new DateTimeImmutable($cuenta['bloqueado_hasta']) : null;
                    $ahora = new DateTimeImmutable('now');

                    if ($bloqueadoHasta !== null && $bloqueadoHasta > $ahora) {
                        $error = 'Esta cuenta está bloqueada temporalmente por varios intentos fallidos. Intenta en unos minutos.';
                    } elseif (password_verify($password, $cuenta['password_hash'])) {
                        $credencialesValidas = true;
                    }
                }

                if ($credencialesValidas) {
                    $modelo->registrarLoginExitoso((int) $cuenta['id']);

                    session_regenerate_id(true);
                    $_SESSION['admin_id'] = (int) $cuenta['id'];
                    $_SESSION['admin_nombre'] = $cuenta['nombre'];
                    $_SESSION['admin_email'] = $cuenta['email'];
                    $_SESSION['admin_rol'] = $cuenta['rol'];
                    $_SESSION['admin_foto'] = $cuenta['foto_ruta'] ?? null;

                    header('Location: dashboard.php');
                    exit;
                } elseif ($error === '') {
                    $error = 'Correo o contraseña incorrectos.';

                    if ($cuenta && (int) $cuenta['activo'] === 1) {
                        $intentos = (int) $cuenta['intentos_fallidos'] + 1;
                        if ($intentos >= self::MAX_INTENTOS) {
                            $bloqueo = (new DateTimeImmutable('now'))->modify('+' . self::BLOQUEO_MINUTOS . ' minutes');
                            $modelo->bloquear((int) $cuenta['id'], $intentos, $bloqueo->format('Y-m-d H:i:s'));
                            $error = 'Demasiados intentos fallidos. La cuenta quedó bloqueada ' . self::BLOQUEO_MINUTOS . ' minutos.';
                        } else {
                            $modelo->incrementarIntentos((int) $cuenta['id'], $intentos);
                        }
                    }
                }
            }
        }

        View::render('admin/login', [
            'error' => $error,
            'csrf' => Csrf::token(),
            'emailIngresado' => $_POST['email'] ?? '',
        ]);
    }

    public function logout(): void
    {
        Auth::iniciarSesionSegura();
        $_SESSION = [];
        session_destroy();

        header('Location: ../index.html');
        exit;
    }

    public function recuperar(): void
    {
        Auth::iniciarSesionSegura();

        if (Auth::usuarioActual() !== null) {
            header('Location: dashboard.php');
            exit;
        }

        $modelo = new UsuarioAdmin();
        $mensaje = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
                $mensaje = 'Tu sesión expiró, recarga la página e intenta de nuevo.';
            } else {
                $email = trim((string) ($_POST['email'] ?? ''));

                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $cuenta = $modelo->buscarActivoPorEmail($email);

                    if ($cuenta) {
                        // Se usa la hora de PHP en ambos lados de la comparación (en vez
                        // de NOW() de MySQL) porque el reloj de PHP y el del servidor
                        // MySQL pueden tener zonas horarias distintas configuradas.
                        $ahora = new DateTimeImmutable('now');
                        $ultimaSolicitud = $cuenta['ultimo_reset_solicitado']
                            ? new DateTimeImmutable($cuenta['ultimo_reset_solicitado'])
                            : null;
                        $puedeSolicitar = $ultimaSolicitud === null
                            || $ultimaSolicitud->modify('+' . self::MINUTOS_ENTRE_SOLICITUDES . ' minutes') <= $ahora;

                        if ($puedeSolicitar) {
                            $passwordTemporal = Auth::generarPasswordTemporal();
                            $hash = password_hash($passwordTemporal, PASSWORD_DEFAULT);

                            $modelo->resetearPasswordAutoServicio((int) $cuenta['id'], $hash, $ahora->format('Y-m-d H:i:s'));

                            $cuerpo = '<p>Hola ' . htmlspecialchars($cuenta['nombre'], ENT_QUOTES, 'UTF-8') . ',</p>'
                                . '<p>Recibimos una solicitud para restablecer tu contraseña del panel de administración de ASOVEGU.</p>'
                                . '<p>Tu nueva contraseña temporal es:</p>'
                                . '<p style="font-size:20px;font-weight:bold;letter-spacing:1px;">' . htmlspecialchars($passwordTemporal, ENT_QUOTES, 'UTF-8') . '</p>'
                                . '<p>Ingresa con ella y cámbiala de inmediato desde "Mi cuenta". Si no solicitaste esto, contacta al super administrador.</p>';

                            Mailer::enviar($cuenta['email'], 'Nueva contraseña temporal - Panel ASOVEGU', $cuerpo);
                        }
                    }
                }

                // Mensaje genérico siempre: no revela si el correo existe, está
                // inactivo, o si ya se había solicitado un reset hace poco.
                $mensaje = 'Si el correo está registrado, te enviamos una nueva contraseña temporal.';
            }
        }

        View::render('admin/recuperar', [
            'mensaje' => $mensaje,
            'csrf' => Csrf::token(),
        ]);
    }
}
