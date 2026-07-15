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
