<?php

declare(strict_types=1);

require_once __DIR__ . '/incluye/auth.php';
require_once __DIR__ . '/incluye/csrf.php';
require_once __DIR__ . '/../config/mailer.php';

iniciarSesionSegura();

if (usuarioActual() !== null) {
    header('Location: dashboard.php');
    exit;
}

const MINUTOS_ENTRE_SOLICITUDES = 5;

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCsrf($_POST['csrf_token'] ?? null)) {
        $mensaje = 'Tu sesión expiró, recarga la página e intenta de nuevo.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $pdo = obtenerConexionBD();
            $stmt = $pdo->prepare('SELECT * FROM usuarios_admin WHERE email = :email AND activo = 1 LIMIT 1');
            $stmt->execute(['email' => $email]);
            $cuenta = $stmt->fetch();

            if ($cuenta) {
                // Se usa la hora de PHP en ambos lados de la comparación (en vez de
                // NOW() de MySQL) porque el reloj de PHP y el del servidor MySQL
                // pueden tener zonas horarias distintas configuradas.
                $ahora = new DateTimeImmutable('now');
                $ultimaSolicitud = $cuenta['ultimo_reset_solicitado'] ? new DateTimeImmutable($cuenta['ultimo_reset_solicitado']) : null;
                $puedeSolicitar = $ultimaSolicitud === null
                    || $ultimaSolicitud->modify('+' . MINUTOS_ENTRE_SOLICITUDES . ' minutes') <= $ahora;

                if ($puedeSolicitar) {
                    $passwordTemporal = generarPasswordTemporal();
                    $hash = password_hash($passwordTemporal, PASSWORD_DEFAULT);

                    $upd = $pdo->prepare(
                        'UPDATE usuarios_admin
                         SET password_hash = :hash, intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_reset_solicitado = :ahora
                         WHERE id = :id'
                    );
                    $upd->execute(['hash' => $hash, 'ahora' => $ahora->format('Y-m-d H:i:s'), 'id' => $cuenta['id']]);

                    $cuerpo = '<p>Hola ' . htmlspecialchars($cuenta['nombre'], ENT_QUOTES, 'UTF-8') . ',</p>'
                        . '<p>Recibimos una solicitud para restablecer tu contraseña del panel de administración de ASOVEGU.</p>'
                        . '<p>Tu nueva contraseña temporal es:</p>'
                        . '<p style="font-size:20px;font-weight:bold;letter-spacing:1px;">' . htmlspecialchars($passwordTemporal, ENT_QUOTES, 'UTF-8') . '</p>'
                        . '<p>Ingresa con ella y cámbiala de inmediato desde "Mi cuenta". Si no solicitaste esto, contacta al super administrador.</p>';

                    enviarCorreo($cuenta['email'], 'Nueva contraseña temporal - Panel ASOVEGU', $cuerpo);
                }
            }
        }

        // Mensaje genérico siempre: no revela si el correo existe, está
        // inactivo, o si ya se había solicitado un reset hace poco.
        $mensaje = 'Si el correo está registrado, te enviamos una nueva contraseña temporal.';
    }
}

$csrf = tokenCsrf();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña | Panel ASOVEGU</title>
    <link rel="icon" type="image/png" href="../img/escudo sin fondo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="css/admin.css">
</head>

<body class="login-body">
    <div class="login-card">
        <img src="../img/escudo sin fondo.png" alt="Escudo ASOVEGU" class="login-logo">
        <h1>Recuperar contraseña</h1>
        <p class="login-subtitulo">Te enviaremos una nueva contraseña temporal por correo</p>

        <?php if ($mensaje !== ''): ?>
            <div class="form-error" role="status" style="background:#DFF3E4;color:var(--color-primary);border-color:var(--color-primary);">
                <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

            <label for="email">Correo de tu cuenta</label>
            <input type="email" id="email" name="email" required autofocus>

            <button type="submit" class="btn-enviar">Enviar nueva contraseña</button>
        </form>

        <p style="margin-top:18px;"><a href="login.php">Volver a iniciar sesión</a></p>
    </div>
</body>

</html>
