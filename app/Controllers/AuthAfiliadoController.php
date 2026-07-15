<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuthAfiliado;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Asociado;
use DateTimeImmutable;

class AuthAfiliadoController
{
    private const MAX_INTENTOS = 5;
    private const BLOQUEO_MINUTOS = 15;

    public function login(): void
    {
        Auth::iniciarSesionSegura();

        if (AuthAfiliado::afiliadoActual() !== null) {
            header('Location: dashboard.php');
            exit;
        }

        $modelo = new Asociado();
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
                $error = 'Tu sesión expiró, intenta de nuevo.';
            } else {
                $email = trim((string) ($_POST['email'] ?? ''));
                $password = (string) ($_POST['password'] ?? '');

                $asociado = $modelo->buscarPorEmail($email);
                $credencialesValidas = false;

                // Solo entra quien está aprobado y ya tiene acceso activado; el
                // mensaje es siempre el mismo genérico para no revelar el motivo.
                if ($asociado && $asociado['estado'] === 'aprobado' && !empty($asociado['password_hash'])) {
                    $bloqueadoHasta = $asociado['bloqueado_hasta'] ? new DateTimeImmutable($asociado['bloqueado_hasta']) : null;
                    $ahora = new DateTimeImmutable('now');

                    if ($bloqueadoHasta !== null && $bloqueadoHasta > $ahora) {
                        $error = 'Tu cuenta está bloqueada temporalmente por varios intentos fallidos. Intenta en unos minutos.';
                    } elseif (password_verify($password, $asociado['password_hash'])) {
                        $credencialesValidas = true;
                    }
                }

                if ($credencialesValidas) {
                    $modelo->registrarLoginExitoso((int) $asociado['id']);

                    session_regenerate_id(true);
                    $_SESSION['afiliado_id'] = (int) $asociado['id'];
                    $_SESSION['afiliado_nombre'] = $asociado['nombres'] . ' ' . $asociado['apellidos'];
                    $_SESSION['afiliado_email'] = $asociado['email'];

                    header('Location: dashboard.php');
                    exit;
                } elseif ($error === '') {
                    $error = 'Correo o contraseña incorrectos.';

                    if ($asociado && $asociado['estado'] === 'aprobado' && !empty($asociado['password_hash'])) {
                        $intentos = (int) $asociado['intentos_fallidos'] + 1;
                        if ($intentos >= self::MAX_INTENTOS) {
                            $bloqueo = (new DateTimeImmutable('now'))->modify('+' . self::BLOQUEO_MINUTOS . ' minutes');
                            $modelo->bloquear((int) $asociado['id'], $intentos, $bloqueo->format('Y-m-d H:i:s'));
                            $error = 'Demasiados intentos fallidos. Tu cuenta quedó bloqueada ' . self::BLOQUEO_MINUTOS . ' minutos.';
                        } else {
                            $modelo->incrementarIntentos((int) $asociado['id'], $intentos);
                        }
                    }
                }
            }
        }

        View::render('afiliado/login', [
            'error' => $error,
            'csrf' => Csrf::token(),
            'emailIngresado' => $_POST['email'] ?? '',
        ]);
    }

    public function logout(): void
    {
        Auth::iniciarSesionSegura();
        unset($_SESSION['afiliado_id'], $_SESSION['afiliado_nombre'], $_SESSION['afiliado_email']);

        header('Location: login.php');
        exit;
    }
}
