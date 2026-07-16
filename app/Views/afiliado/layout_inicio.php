<?php
// Espera que la página incluida defina antes: $tituloPagina, $afiliado.
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina ?? 'Portal del afiliado', ENT_QUOTES, 'UTF-8') ?> | ASOVEGU</title>

    <link rel="icon" type="image/png" href="../img/escudo sin fondo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="../admin/css/admin.css">
</head>

<body class="admin-body">

    <div class="admin-layout">
        <aside class="admin-sidebar" id="admin-sidebar">
            <div class="admin-sidebar-logo">
                <img src="../img/logo_nav.png" alt="ASOVEGU">
            </div>

            <nav class="admin-nav">
                <a href="dashboard.php" class="admin-nav-link <?= ($paginaActiva ?? '') === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-gauge-high"></i> Mi información
                </a>
                <a href="mis-pagos.php" class="admin-nav-link <?= ($paginaActiva ?? '') === 'mis-pagos' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-check"></i> Mis pagos
                </a>
                <a href="../index.html" class="admin-nav-link" target="_blank">
                    <i class="fas fa-arrow-up-right-from-square"></i> Ver sitio público
                </a>
            </nav>

            <div class="admin-sidebar-footer">
                <div class="admin-user-chip">
                    <i class="fas fa-circle-user"></i>
                    <div>
                        <strong><?= htmlspecialchars($afiliado['nombre'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span>Afiliado</span>
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
