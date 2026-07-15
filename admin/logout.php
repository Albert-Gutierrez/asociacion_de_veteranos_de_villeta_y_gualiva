<?php

declare(strict_types=1);

require_once __DIR__ . '/incluye/auth.php';

iniciarSesionSegura();
$_SESSION = [];
session_destroy();

header('Location: login.php');
exit;
