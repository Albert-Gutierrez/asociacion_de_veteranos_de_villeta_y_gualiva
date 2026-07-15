<?php
// Espera que la página incluida defina antes: $tituloPagina, $paginaActiva
// y haya llamado a requerirSesion()/requerirRol() para obtener $usuario.
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina ?? 'Panel de administración', ENT_QUOTES, 'UTF-8') ?> | ASOVEGU</title>

    <link rel="icon" type="image/png" href="../img/escudo sin fondo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="css/admin.css">
</head>

<body class="admin-body">

    <div class="admin-layout">
        <aside class="admin-sidebar" id="admin-sidebar">
            <div class="admin-sidebar-logo">
                <img src="../img/logo_nav.png" alt="ASOVEGU">
            </div>

            <nav class="admin-nav">
                <a href="dashboard.php" class="admin-nav-link <?= ($paginaActiva ?? '') === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-gauge-high"></i> Dashboard
                </a>
                <a href="cuentas.php" class="admin-nav-link <?= ($paginaActiva ?? '') === 'cuentas' ? 'active' : '' ?>">
                    <i class="fas fa-sack-dollar"></i> Cuentas Totales
                </a>
                <?php if (esSuperAdmin()): ?>
                <a href="administradores.php" class="admin-nav-link <?= ($paginaActiva ?? '') === 'administradores' ? 'active' : '' ?>">
                    <i class="fas fa-user-shield"></i> Administradores
                </a>
                <?php endif; ?>
                <a href="mi-cuenta.php" class="admin-nav-link <?= ($paginaActiva ?? '') === 'mi-cuenta' ? 'active' : '' ?>">
                    <i class="fas fa-user-gear"></i> Mi cuenta
                </a>
                <a href="../index.html" class="admin-nav-link" target="_blank">
                    <i class="fas fa-arrow-up-right-from-square"></i> Ver sitio público
                </a>
            </nav>

            <div class="admin-sidebar-footer">
                <div class="admin-user-chip">
                    <i class="fas fa-circle-user"></i>
                    <div>
                        <strong><?= htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= etiquetaRol($usuario['rol']) ?></span>
                    </div>
                </div>
                <a href="logout.php" class="admin-logout-link"><i class="fas fa-right-from-bracket"></i> Cerrar sesión</a>
            </div>
        </aside>

        <div class="admin-content-wrapper">
            <header class="admin-topbar">
                <button type="button" class="admin-menu-toggle" id="admin-menu-toggle" aria-label="Abrir menú">
                    <i class="fas fa-bars"></i>
                </button>
                <h1><?= htmlspecialchars($tituloPagina ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
            </header>

            <main class="admin-content">
