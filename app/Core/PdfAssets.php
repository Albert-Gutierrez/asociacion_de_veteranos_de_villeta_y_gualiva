<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Imágenes reutilizadas en los PDF generados con Dompdf, incrustadas como
 * data URI base64 (la forma más confiable de que Dompdf las renderice, sin
 * depender de rutas de archivo ni de tener habilitado el acceso remoto).
 */
class PdfAssets
{
    public static function escudoDataUri(): string
    {
        return self::dataUri(__DIR__ . '/../../img/escudo sin fondo.png', 'image/png');
    }

    private static function dataUri(string $ruta, string $mime): string
    {
        if (!is_file($ruta)) {
            return '';
        }
        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($ruta));
    }
}
