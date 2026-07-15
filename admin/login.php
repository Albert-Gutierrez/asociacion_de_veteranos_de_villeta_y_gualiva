<?php

declare(strict_types=1);

require_once __DIR__ . '/incluye/auth.php';
require_once __DIR__ . '/incluye/csrf.php';

iniciarSesionSegura();

if (usuarioActual() !== null) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
const MAX_INTENTOS = 5;
const BLOQUEO_MINUTOS = 15;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Tu sesión expiró, intenta de nuevo.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $pdo = obtenerConexionBD();
        $stmt = $pdo->prepare('SELECT * FROM usuarios_admin WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $cuenta = $stmt->fetch();

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
            $upd = $pdo->prepare(
                'UPDATE usuarios_admin SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_acceso = NOW() WHERE id = :id'
            );
            $upd->execute(['id' => $cuenta['id']]);

            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $cuenta['id'];
            $_SESSION['admin_nombre'] = $cuenta['nombre'];
            $_SESSION['admin_email'] = $cuenta['email'];
            $_SESSION['admin_rol'] = $cuenta['rol'];

            header('Location: dashboard.php');
            exit;
        } elseif ($error === '') {
            $error = 'Correo o contraseña incorrectos.';

            if ($cuenta && (int) $cuenta['activo'] === 1) {
                $intentos = (int) $cuenta['intentos_fallidos'] + 1;
                if ($intentos >= MAX_INTENTOS) {
                    $bloqueo = (new DateTimeImmutable('now'))->modify('+' . BLOQUEO_MINUTOS . ' minutes');
                    $upd = $pdo->prepare(
                        'UPDATE usuarios_admin SET intentos_fallidos = :intentos, bloqueado_hasta = :bloqueo WHERE id = :id'
                    );
                    $upd->execute(['intentos' => $intentos, 'bloqueo' => $bloqueo->format('Y-m-d H:i:s'), 'id' => $cuenta['id']]);
                    $error = 'Demasiados intentos fallidos. La cuenta quedó bloqueada ' . BLOQUEO_MINUTOS . ' minutos.';
                } else {
                    $upd = $pdo->prepare('UPDATE usuarios_admin SET intentos_fallidos = :intentos WHERE id = :id');
                    $upd->execute(['intentos' => $intentos, 'id' => $cuenta['id']]);
                }
            }
        }
    }
}

$csrf = tokenCsrf();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión | Panel ASOVEGU</title>
    <link rel="icon" type="image/png" href="../img/escudo sin fondo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="css/admin.css">
</head>

<body class="login-body">
    <div class="login-card">
        <img src="../img/escudo sin fondo.png" alt="Escudo ASOVEGU" class="login-logo">
        <h1>Panel de administración</h1>
        <p class="login-subtitulo">Asociación de Veteranos de Villeta y Gualivá</p>

        <?php if ($error !== ''): ?>
            <div class="form-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

            <label for="email">Correo</label>
            <input type="email" id="email" name="email" required autofocus
                value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>

            <button type="submit" class="btn-enviar">Ingresar</button>
        </form>
    </div>
</body>

</html>
