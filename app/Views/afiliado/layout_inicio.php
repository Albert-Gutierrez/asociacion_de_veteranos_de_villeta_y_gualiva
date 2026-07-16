<?php
// Espera que la página incluida defina antes: $tituloPagina, $afiliado.
// $asociado es opcional (lo pasan dashboard.php, mis-pagos.php y soporte.php);
// cuando está disponible, su "fuerza" decide el acento visual del sidebar.
function datosFuerzaSidebar(?string $fuerza): ?array
{
    $f = strtr(mb_strtolower((string) $fuerza, 'UTF-8'), ['é' => 'e', 'á' => 'a', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);

    $catalogo = [
        'ejercito' => [
            'clase' => 'ejercito',
            'nombre' => 'Ejército Nacional',
            'mensaje' => 'Honor, lealtad y patria.',
            'imagen' => 'fondo_ejercito.svg',
        ],
        'policia' => [
            'clase' => 'policia',
            'nombre' => 'Policía Nacional',
            'mensaje' => 'Dios y patria, honor y disciplina.',
            'imagen' => 'fondo_policia.svg',
        ],
        'armada' => [
            'clase' => 'armada',
            'nombre' => 'Armada Nacional',
            'mensaje' => 'Vocación naval al servicio de Colombia.',
            'imagen' => 'fondo_armada.svg',
        ],
        'fuerza-aerea' => [
            'clase' => 'fuerza-aerea',
            'nombre' => 'Fuerza Aérea Colombiana',
            'mensaje' => 'Honor y gloria en la defensa del espacio aéreo.',
            'imagen' => 'fondo_fuerza_aerea.svg',
        ],
    ];

    if (str_contains($f, 'ejerc')) {
        return $catalogo['ejercito'];
    }
    if (str_contains($f, 'polic')) {
        return $catalogo['policia'];
    }
    if (str_contains($f, 'armada') || str_contains($f, 'naval') || str_contains($f, 'marina')) {
        return $catalogo['armada'];
    }
    if (str_contains($f, 'aerea') || str_contains($f, 'fac')) {
        return $catalogo['fuerza-aerea'];
    }
    return null;
}

$fuerzaInfo = isset($asociado) ? datosFuerzaSidebar($asociado['fuerza'] ?? null) : null;
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
        <aside class="admin-sidebar<?= $fuerzaInfo !== null ? ' sidebar-fuerza-' . $fuerzaInfo['clase'] : '' ?>" id="admin-sidebar">
            <div class="admin-sidebar-logo">
                <img src="../img/escudo sin fondo.png" alt="Escudo ASOVEGU">
                <span class="admin-sidebar-logo-texto">ASOVEGU</span>
            </div>

            <nav class="admin-nav">
                <a href="dashboard.php" class="admin-nav-link <?= ($paginaActiva ?? '') === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-gauge-high"></i> Mi información
                </a>
                <a href="mis-pagos.php" class="admin-nav-link <?= ($paginaActiva ?? '') === 'mis-pagos' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-check"></i> Mis pagos
                </a>
                <a href="soporte.php" class="admin-nav-link <?= ($paginaActiva ?? '') === 'soporte' ? 'active' : '' ?>">
                    <i class="fas fa-headset"></i> Soporte
                </a>
                <a href="../index.html" class="admin-nav-link" target="_blank">
                    <i class="fas fa-arrow-up-right-from-square"></i> Ver sitio público
                </a>

                <?php if ($fuerzaInfo !== null): ?>
                <div class="admin-sidebar-fuerza-destacado">
                    <img src="../img/<?= htmlspecialchars($fuerzaInfo['imagen'], ENT_QUOTES, 'UTF-8') ?>" alt="Escudo <?= htmlspecialchars($fuerzaInfo['nombre'], ENT_QUOTES, 'UTF-8') ?>">
                    <h3><?= htmlspecialchars($fuerzaInfo['nombre'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p><?= htmlspecialchars($fuerzaInfo['mensaje'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <?php endif; ?>
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
