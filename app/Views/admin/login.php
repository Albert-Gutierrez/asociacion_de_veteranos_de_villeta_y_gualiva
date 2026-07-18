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
                value="<?= htmlspecialchars($emailIngresado ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>

            <button type="submit" class="btn-enviar">Ingresar</button>
        </form>

        <p style="margin-top:18px;"><a href="recuperar.php">¿Olvidaste tu contraseña?</a></p>
    </div>

    <script src="js/admin.js?v=<?= @filemtime(__DIR__ . '/../../../admin/js/admin.js') ?: time() ?>"></script>
</body>

</html>
